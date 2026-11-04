# ShopSync — Phase 2 Analytics Methodology

**Companion:** [PHASE_2_UI_AUDIT.md](PHASE_2_UI_AUDIT.md) (design system and plan), [PHASE_2_IMPLEMENTATION_REPORT.md](PHASE_2_IMPLEMENTATION_REPORT.md) (delivery summary)

This document states, for every advanced report, the exact mathematical method used, the
minimum-data guard that must pass before the technique is applied, and where it is implemented.
All computation happens server-side against real database records; nothing is sampled, mocked or
hardcoded. Every statistical output is labelled in the UI as an estimate or a fact — projections
are visually separated (dotted + labelled) from historical facts (solid), and correlation-style
comparisons carry an explicit "does not prove causation" footnote.

Implementation files:

- `app/Services/Analytics/AnalyticsMath.php` — pure statistical functions (unit-tested)
- `app/Services/Analytics/AnalyticsService.php` — SQL aggregation + orchestration
- `app/Services/Analytics/WindowResolver.php` — reporting window parsing/validation
- `app/Services/Analytics/AnalyticsCsv.php` — CSV twin of every report
- `resources/views/components/*` — zero-dependency SVG/HTML chart components

---

## 1. Reporting window and granularity

`WindowResolver` validates `from`/`to` (format `Y-m-d`, `to ≥ from`, no future start) and a preset
(`7d`, `30d`, `90d`, `mtd`, `ytd`, `all`; default month-to-date). Granularity ∈ {daily, weekly,
monthly}. All filters are URL parameters, so every view is shareable and works without JavaScript.

- **Daily buckets**: calendar days between `from` and `to`.
- **Weekly buckets**: Monday-keyed weeks — SQLite `DATE(x,'weekday 0','-6 days')`, MySQL
  `DATE_SUB(DATE(x), INTERVAL WEEKDAY(x) DAY)` — so both drivers produce identical bucket keys.
- **Monthly buckets**: `strftime('%Y-%m')` / `DATE_FORMAT('%Y-%m')`.
- A range longer than **366 days is forced to monthly** buckets to bound query cost.
- Every bucket in the window is enumerated first and **zero-filled** — gaps in the data render as
  true zeros, never as missing points that a regression would silently skip.

## 2. Net sales (the one authoritative metric)

**net sales = SUM(order_idems.subtotal) WHERE item status ∈ {processing, shipped, delivered}**
over orders with `created_at` inside the window.

- `orders.status` is dead (stays `pending` forever); item-level statuses are the only lifecycle.
- Cancelled and pending items are excluded from sales, units and order counts.
- Discounts: `subtotal` is gross of voucher discounts; discount analysis reads per-item
  `voucher_discount_amount` and per-order `voucher_discount_total`.
- Owner scope = items whose `product.user_id` is the owner (via join / `whereHas`), which also
  scopes every derived report.

## 3. Trend series (`AnalyticsService::salesSeries`)

For each bucket, from the item-level query: net sales, units, distinct orders with sales.
Derived layers:

| Layer | Method | Guard | Implementation |
|---|---|---|---|
| Cumulative | running sum (discrete integral) of bucket sales | none | `AnalyticsMath::cumulative` |
| Moving average | centred-free trailing MA, window 7 (daily) or 3 (weekly/monthly); windows shorter than the period yield `null`s | bucket count ≥ window | `AnalyticsMath::movingAverage` |
| Linear regression | least squares `y = a + bx` over bucket index → sales; R² = slope²·Sxx / Syy, clamped to [0, 1] | n ≥ 3 and variance > 0 | `AnalyticsMath::linearRegression` |
| Projection | regression extrapolated 7 daily / 2 weekly / 1 monthly buckets past `to`, rounded to 2 dp | n ≥ 14 **and** valid regression | `AnalyticsService::projection` |
| Anomalies | population z-score per bucket: `z = (y − μ) / σ`; flag `|z| > 2` | n ≥ 10 and σ > 0 | `AnalyticsMath::zScores` |
| Acceleration | slope of last 7 buckets − slope of previous 7; "accelerating" if > max(1, 1% of mean) | enough buckets for two windows | `AnalyticsService::acceleration` |

Presentation rules: the fitted trend renders as a dashed gray reference line; the projection is
dotted, ends in an open circle and carries the label "Projection"; flagged anomalies get ringed
points plus a plain-language list ("z-score above 2 — unusual, not good or bad").

## 4. Period comparison (`deltas`)

The previous window is the immediately preceding span of identical length
(`previousWindow()`), so daily/weekly/monthly comparisons are apples-to-apples.
Each KPI delta is the **percentage change**

    Δ% = (current − previous) / previous × 100

returning `null` when `previous = 0` (no fake "+∞%"); the UI renders `—` instead of a number.
The previous-period series is aligned onto the current bucket count so the two lines can share
one axis honestly (`alignToCurrent`).

## 5. Order value distribution (`orderHistogram`)

- Six **equal-width bins** between the minimum and maximum qualifying order value.
- Admin sums `orders.total` per order; owner sums their items' `subtotal` per order (an owner's
  order value is their share of it).
- Percentiles use **linear interpolation**: `percentile(v, p) = v[⌊r⌋] + (r − ⌊r⌋)·(v[⌈r⌉] − v[⌊r⌋])`
  with `r = p/100·(n−1)`; median = P50.
- Mean is shown beside the median deliberately: median ≪ mean exposes right-skew (a few large
  orders pulling the average), and the footnote says exactly that.

## 6. Top products — Pareto concentration (`topProducts`)

Products ranked by net sales, each with share = revenue / total and **cumulative share** of the
ranking. The chart shows ranked one-hue bars; the cumulative column lives in the table twin
(no dual-axis chart). The footnote states the concentration fact without prescribing action.

## 7. Category performance (`categoryPerformance`)

Same item query, grouped by `COALESCE(category.name, 'Uncategorised')` — uncategorised revenue is
grouped, never dropped. Units, revenue and share per category.

## 8. Order status distribution (`statusDistribution`)

Item-status counts inside the window **including pending and cancelled**, with share. Cancelled
items are deliberately visible so the gap between "orders placed" and "net sales" is explainable.
Colors are reserved semantics (delivered = green, pending = amber, cancelled = red), never
reused as series colors.

## 9. Customer acquisition (`acquisition`)

- Admin: new customer accounts per bucket (signups from `users.created_at`).
- Owner: distinct customers ordering from that shop per bucket.
- **Repeat rate** = customers with ≥ 2 qualifying orders ÷ all ordering customers in the window;
  `null` when there are no ordering customers.

## 10. Shop concentration (`shopPerformance`, admin only)

Net sales, order count and share per approved shop (ShopApplication name when present, else the
owner account name). Share describes marketplace dependency on the top sellers.

## 11. Voucher effectiveness (`voucherPerformance`) — descriptive only

- Funnel: vouchers issued vs redeemed, redemption rate = redeemed / issued (`null` when none issued).
- In-window usage per code: distinct orders and total `voucher_discount_total`. Because the
  discount is **order-level**, each order is aggregated once before grouping by code — summing
  over joined item rows would multiply the discount by the order's item count.
- Revenue on voucher vs non-voucher orders is shown side by side with the explicit footnote that
  the two numbers describe different order sets and **do not prove the voucher caused anything**.

## 12. Payment methods (`paymentPerformance`)

- Mix by `COALESCE(payment_method, 'unspecified')` with share.
- **Median checkout→payment-submission latency** (`created_at → payment_submitted_at`) over
  mobile-money orders (bkash/nagad submit references; COD has nothing to submit).
- Verification turnaround is *not* computed: the schema records no verification timestamp, and
  inventing one would violate the data-integrity rule. The footnote says so.

## 13. Inventory risk (`inventoryRisk`)

From the stock ledger, per product, inside the window:

    burn per day = Σ|quantity_change| (type = 'sale') / window days
    days of stock = current stock / burn per day   (null when burn = 0)

Restock events are counted separately. The table sorts by days-of-stock ascending; the footnote
states the assumption that recent pace continues, so the projection is a moving estimate.

## 14. Product movers (`productMovers`)

Per product: revenue this window vs the immediately preceding window of equal length.
Percentage change needs a non-zero previous period; products selling in the current window but
not the previous are counted as **new** rather than reported as "+∞%", and products declining to
zero appear as −100% decliners instead of silently disappearing.

## 15. Order heatmap (`orderHeatmap`)

7 weekdays × 12 two-hour slots of qualifying order counts, single-hue sequential ramp (darker =
busier), rendered only when the window covers **≥ 14 days** — with less data no rhythm exists to
show, and the section stays hidden rather than guessing.

## 16. Caching and integrity

- The whole payload is cached via `Cache::remember` with a key encoding scope, owner id, window,
  granularity and compare flag (`analytics:{scope}:{owner|all}:{from}:{to}:{gran}:{compare|raw}`),
  TTL 10 minutes — dashboards use fixed `analytics:admin:dashboard:30d` /
  `analytics:owner:dashboard:30d:{id}` keys. Different scopes/params can never read each other's
  cache entries (covered by a feature test).
- Guards summary: regression n≥3; z-scores n≥10 & σ>0; projection n≥14; heatmap ≥14 days;
  percentage change undefined on zero base → `—`.
- No sample data, no hardcoded values, no migrations: every figure derives from existing tables
  at request time.
