# ShopSync — Phase 2 Audit: Mathematics-Driven UI & Advanced Analytics

**Date:** 2026-11-03
**Base branch:** `feature/phase-1-production-readiness` (10 local commits, unpushed)
**Companion:** [PHASE_1_AUDIT.md](PHASE_1_AUDIT.md), [PHASE_1_IMPLEMENTATION_REPORT.md](PHASE_1_IMPLEMENTATION_REPORT.md)

This document is the Phase 1 (UI audit) deliverable of the Phase 2 request: what exists today,
what the data actually supports, the proposed mathematical design system, the visualization plan
per dashboard, and the implementation/testing plan. **No code has been changed yet.**

---

## 1. Current Stack & Visual System (verified)

| Aspect | Finding |
|---|---|
| CSS framework | **Tailwind CSS 3.1** via PostCSS (`tailwind.config.js` v3 format, `@tailwindcss/forms`, autoprefixer). The `@tailwindcss/vite ^4` dev-dependency is installed but **unused** — `vite.config.js` has no reference to it. (Earlier docs said "Tailwind 4"; the operative stack is v3.) |
| Interactivity | Alpine.js 3 (`Alpine.start()` in `resources/js/app.js`), Laravel Echo + pusher-js for chat |
| Charting | **None.** No chart library in `package.json`; every dashboard/report is plain HTML tables |
| Typography | Figtree **400/500/600 only** (Bunny Fonts). Views use `font-bold` extensively → browsers synthesize faux-bold |
| Accent/identity | Indigo — `--ss-accent: 99,102,241` (indigo-500) in `resources/css/app.css`; primary buttons `bg-indigo-600`; brand mark `S` |
| Motion system | ~570 lines of custom CSS + ~230 lines of JS: custom cursor, mouse spotlight, 3D product-card tilt, scroll-reveal, entrance animations (`ss-motion-card`, `data-reveal`), reduced-motion respected throughout |
| Layout | `x-app-layout`: nav + optional header slot + bare `<main>`; pages self-container with `max-w-7xl` (17×) or misc widths |

### 1.1 Quantified inconsistencies (the "why" for a design system)

- **Radii:** `rounded-xl` ×152, `rounded-lg` ×73, `rounded-2xl` ×64, `rounded-md` ×54, `rounded-3xl` ×15, `rounded` ×10 — six radii in use with no rule.
- **Containers:** `max-w-7xl` (17), `max-w-4xl` (9), `max-w-3xl` (5), plus 2xl/5xl/6xl stragglers — page families differ arbitrarily.
- **Headings:** `text-2xl` ×40 dominates page titles, with `text-3xl/4xl` hero variants and no scale between.
- **Stat cards:** every dashboard re-implements its own metric card markup (owner, customer, admin, reports pages all differ structurally — padding p-5 vs p-6, border vs shadow vs both).
- **Buttons:** `bg-indigo-600 hover:bg-indigo-700` repeated inline across views; `.ss-action-button` exists in CSS but is used sparsely; radii/heights vary per form.
- **Hex colors** appear only in `resources/views/welcome.blade.php` (unused Breeze leftover, already on the approved-deletion list). Live views are Tailwind-palette-only. ✔
- **Font weight:** `font-bold` (700) is used ~200× but Figtree 700 is not loaded → faux-bold rendering, inconsistent letterforms.
- The two root artifacts of the motion JS (`document.querySelectorAll` on DOMContentLoaded-ish timing) re-run on media-query change and re-create cursor/spotlight — functional but decorative-heavy; performance cost is small but the visual complexity works against the "minimal and clean" goal.

### 1.2 Current analytics surface (what exists)

| Page | Route | Contents today |
|---|---|---|
| Admin control center | `/admin/dashboard` | 6 stat cards (users, products, categories, orders, processed sales, low-stock) + role accounts breakdown. No charts, no time dimension. |
| Admin reports | `/admin/reports` | From/To date form (default: month-start → today); cards (period sales, period orders, users, products, categories); **tables only**: daily sales, monthly sales, best-selling products, low stock. |
| Owner dashboard | `/owner/dashboard` | Hero, 4 stat cards, today's sales/orders/pending, recent orders, low stock, recent inventory transactions. No charts. |
| Owner reports | `/owner/reports` | Same shape as admin reports, scoped to own products. Tables only. |
| Inventory history | `/owner/inventory/history` | Paginated ledger table (before/change/after). |

**Shared metric definition (from Phase 1, keep exactly):** *net sales* = SUM(`order_idems.subtotal`) where item status ∈ {processing, shipped, delivered}; `orders.total` is the customer-facing discounted total; item `subtotal` is gross of voucher discounts; per-item `voucher_discount_amount` and per-order `voucher_discount_total` exist for discount analysis. Cancelled items restore stock (Phase 1) — a cancelled item contributes zero sales.

**Server facts:** DB cache driver default (`config/cache.php` → `CACHE_STORE=database`) → `Cache::remember` usable for report caching with no new infra. `DATE()` grouping works on both MySQL/TiDB and SQLite (tests). No index on `orders.created_at` — acceptable at current volumes; flagged, not changed (no migrations without approval).

## 2. Data Audit — what the mathematics can honestly use

| Domain | Tables / columns available | Enables |
|---|---|---|
| Orders | `orders`: created_at, user_id, order_number, status, payment_method, payment_status, payment_reference, payment_submitted_at, voucher_code, voucher_discount_total, reward_points, total | Time series, period comparison, payment mix, acquisition cohorts, repeat-purchase behavior. *(Correction during implementation: the table has **no** `payment_verified_at`/`payment_verified_by` columns — owner verification time is never recorded — so verification-turnaround analytics are impossible and were replaced by checkout→payment-submission latency.)* |
| Order items | `order_idems`: product_id, product_name, unit_price, quantity, subtotal, status, voucher_discount_amount, reward_points_per_unit, surprise_voucher_eligible | Revenue/quantity series (status-filtered), Pareto analysis, discount impact, category rollups via products |
| Catalog | `products`: price, stock, user_id, category_id, is_active, admin_status, moderation fields; `categories` (parent_id) | Catalog composition, category performance, share analysis |
| Inventory | `inventory_transactions`: type (sale/restock/cancellation/manual…), quantity_before/change/after, order_id, product_id, user_id, timestamps | **Stock burn-rate** (derivative), days-of-stock-remaining, replenishment patterns |
| Vouchers | `vouchers`: discount_type, discount_value, min_order_total, max_discount, points_cost, product/category scope, funding_source, is_active; `customer_vouchers`: source, status, expires_at, redeemed_order_id, redeemed_at | Redemption funnel (issued→redeemed), discount spend vs revenue lift (descriptive), per-voucher performance |
| Users | `users`: role, created_at (+ anonymization from Phase 1) | Acquisition curve, role composition |
| Reviews | `product_reviews`: rating, product_id, user_id | Rating distribution alongside product performance |
| Chat/applications | `chat_messages`, `shop_applications` | Not needed for core analytics; left alone |

### 2.1 Honest limitations (§5 of the brief — data integrity)

1. **No cost-of-goods / supplier cost column exists anywhere.** Profit/margin analysis is **impossible** — it will not be invented. Revenue-only framing everywhere.
2. **"Cancelled" is the only reversal state** — there is no distinct refund concept. Refund-style analyses are not offered.
3. The demo/production dataset is young (weeks of real density in places). Statistical techniques get **minimum-data guards**: regression/trend needs ≥ 3 data points; z-score anomalies need ≥ 10 buckets and σ > 0; projections need ≥ 14 daily buckets; the heatmap needs ≥ 14 days of window. Below threshold → show the basic metric with a plain explanation instead of a weak statistical claim.
4. Vouchers are platform/owner-funded incentives; comparing "voucher vs non-voucher orders" is **descriptive correlation** — copy will never claim causation.
5. Any projection is a **labeled linear-trend estimate**, visually separated (dashed, prefixed "Projection"), never merged into historical fact lines.

## 3. Proposed Mathematical Design System (Phase 2 of work)

**Principle: the math lives in the layout rules and the analytics, not in decoration.**

1. **Spacing:** 4 px base grid (Tailwind's default); standardize section rhythm to `py-8` page, `gap-4/6` grids, `p-5` cards — expressed once in components, not re-decided per page. Vertical spacing between page sections snaps to the scale (no one-off `mt-[13px]`-style values — none exist today; keep it that way).
2. **Type scale:** a geometric 1.25 ratio ladder — `text-sm 14 / text-base 16 / text-lg 18 / text-xl 20 / text-2xl 24 / text-3xl 30 / text-4xl 36` — with fixed roles: page title `text-xl/2xl semibold`, card title `text-base semibold`, KPI value `text-2xl/3xl bold tabular-nums`, label `text-xs/ sm medium gray`. Add **Figtree 700** to the font link (one line) so bold stops being synthesized.
3. **Radii:** collapse six values → three tokens: `rounded-lg` (controls/inputs), `rounded-xl` (cards/sections), `rounded-full` (pills/dots/avatars). Charts use `rounded-full` for dots/caps.
4. **Color:** keep the indigo identity; define semantic chart tokens as CSS variables in `app.css` (`--ss-chart-1…6` from the existing Tailwind palette: indigo, emerald, amber, sky, rose, violet) so every chart uses consistent series colors with meaning: green=revenue/sales, amber=pending/warning, rose=cancelled/decline, indigo=primary metric.
5. **Elevation:** two shadows only (`shadow-sm` resting, one hover lift) — formalized on the shared card component.
6. **Motion:** keep the existing tokens (`220ms` base, `280ms` hover, `cubic-bezier(0.2,0.75,0.25,1)` — an ease-out already matching a critically-damped spring), keep reduced-motion support; **chart transitions use the same 280 ms curve** so analytics feels native. The cursor/spotlight/tilt layer stays untouched (working, identity-forming) unless you want it trimmed later — flagged, not changed.
7. **Components extracted once, reused everywhere** (new Blade components under `resources/views/components/`): `x-stat-card` (label, value, sublabel, optional sparkline + delta chip), `x-chart-card` (title, subtitle, slot for chart, footnote for methodology text), `x-filter-bar` (date range + granularity + compare toggle + preset chips), `x-data-table` (shared table chrome), `x-badge` (status pill with fixed color map). Existing Breeze components untouched.

## 4. Visualization approach — zero new dependencies (justification)

The brief requires preserving the stack and avoiding unnecessary dependencies; there is no charting dependency today. Adding Chart.js (≈200 KB canvas lib) for what is fundamentally **server-computed geometry** would violate the performance requirements. Instead:

- A small PHP service layer (`app/Services/Analytics/`) computes series, regressions, distributions — plain, unit-testable math.
- New **SVG chart Blade components** render polylines/areas/bars/points from the precomputed arrays. SVG is responsive by viewBox (satisfies tablet/mobile), crisp on all DPIs, styleable with the semantic tokens, and adds **zero JS** for rendering.
- **Alpine** powers the interactive layer only: tooltips (single shared store), granularity/compare toggles (URL params → server recompute, keeping truth in the database round-trip), tab switches, and loading/empty/error states.

Estimated added JS: ~150 lines of small Alpine components. No build change; existing Vite pipeline untouched.

### Chart component set

*(Revised during implementation: the dataviz method rejects dual-axis charts, so the Pareto became ranked bars whose table twin carries the cumulative-% column; donuts were dropped in favour of segmented/rank bars, which read faster at these data sizes.)*

| Component | Geometry | Used for |
|---|---|---|
| `x-chart-line` | SVG polyline + area wash, 2 px lines, dashed regression overlay, dotted labeled projection | Revenue/orders trend, cumulative column in table twin, regression overlay, projection segment |
| `x-chart-bars` | SVG columns ≤ 24 px, 4 px rounded data-ends, full-column hover targets | Histogram buckets, acquisition per bucket |
| `x-chart-rank-bars` | One-hue horizontal ranked bars + value column | Top products, categories, shop concentration (Pareto: share + cumulative % in the table twin) |
| `x-chart-segmented` | Segmented horizontal bar (tall / thin), legend swatches | Order-status mix (reserved status colors), payment method mix |
| `x-chart-heatmap` | CSS grid of cells, single-hue sequential ramp | Weekday × 2-hour-slot order density (when ≥ 2 weeks data) |
| `x-stat-card` | Label / hero value / delta chip / inline sparkline | Period KPIs |
| `x-ss-badge` | Status pill with fixed color map | Status columns in tables |

## 5. Analytics plan — business question → technique → visualization

### 5.1 Admin analytics (dashboard + `/admin/reports`)

| # | Business question | Mathematical technique | Presentation |
|---|---|---|---|
| A1 | How is net sales moving? | Daily series (status-filtered) + **7-day moving average** + **least-squares linear regression** (slope ৳/day = rate of change) | `x-chart-line` area + trend overlay; stat card shows slope with plain-words meaning ("revenue is growing ≈ ৳X/day over this period") |
| A2 | How fast is it *accelerating*? | Second difference of the 7-day MA (৳/day²) over ≥ 14 points | Small "accelerating / steady / decelerating" chip on the revenue card; tooltip explains in one sentence |
| A3 | Cumulative performance | Running sum (discrete integral) of net sales | Toggle on the trend chart: "Cumulative" view |
| A4 | Are we up or down vs the previous period? | Δ% vs the immediately preceding equal-length window (from/to shifted back) | Delta chips on all KPI cards (▲ emerald / ▼ rose), labeled "vs previous 30 days" |
| A5 | What does the order-value distribution look like? | Mean, median, P90 + **histogram** of order totals | Histogram bars + percentile table row; median vs mean exposes skew |
| A6 | Which products dominate? | **Pareto**: sorted revenue share + cumulative % | `x-chart-pareto`; footnote states "top N products = X% of revenue" |
| A7 | Category performance | Revenue & quantity grouped by category (via product→category), share % | Rank bars + table twin with share column |
| A8 | Order status distribution | Item-status counts in period | Segmented bar with reserved semantic colors (amber=pending etc.) + per-status table |
| A9 | Is anything unusual happening? | **Z-score** on daily net sales (μ, σ over period; flag \|z\| > 2 when σ > 0 and ≥ 10 days) | Flagged dots on the trend chart + "Unusual days" list with date + deviation; footnote explains z-score in one line |
| A10 | Are customers growing? | New users/day (created_at), plus **repeat rate** = orders from customers with ≥ 2 orders ÷ ordering customers | Acquisition area chart + repeat-rate KPI |
| A11 | Which shops carry the platform? | Net sales + orders per owner (approved shops), share % | Bar chart + concentration note |
| A12 | How do vouchers perform? | Issued vs redeemed count (funnel), redemption rate %, total discount given (`voucher_discount_total`), revenue on voucher orders vs not | Voucher card + table per code; footnote: descriptive comparison, not causal |
| A13 | Payment health | Mix by payment_method; **median checkout→payment-submission latency** (created_at → payment_submitted_at). *(Verification turnaround dropped: the schema never records when an owner verifies.)* | Segmented bar + latency KPI (mobile-money orders only) |
| A14 | Inventory risk | Per product: **consumption rate** ৳/day from `sale` ledger entries in window → **days-of-stock** = stock ÷ rate; restock event count | "Inventory risk" table sorted by days-of-stock ascending; replaces/augments static low-stock list |

### 5.2 Owner analytics (dashboard + `/owner/reports`) — same engine, shop-scoped

Every query runs through the existing owner isolation pattern (`whereHas('product', fn ($q) => $q->where('user_id', $ownerId))`), verified by authorization tests.

| # | Business question | Technique | Presentation |
|---|---|---|---|
| O1 | Revenue & order trends for **my shop** | Same A1/A3/A4 pipeline scoped to owner | Line chart + cumulative + delta chips |
| O2 | Which of my products lead / lag? | Pareto + **growth per product** (Δ% vs previous period) | Pareto chart; best/worst movers list |
| O3 | What does a typical order look like for me? | Mean/median/P90 order value (my items aggregated per order) | Histogram + percentiles |
| O4 | When do my customers order? | Weekday × hour heatmap of my orders (≥ 2 weeks data) | `x-chart-heatmap` |
| O5 | Stock: what runs out first? | Consumption rate + days-of-stock per product (from my ledger) | Risk table + restock frequency |
| O6 | Did vouchers help my sales? | Redemption count on my orders, discount given, revenue with/without voucher (descriptive) | Voucher card |
| O7 | Customer pattern | New vs repeat customers per period (orders on my products) | Simple stacked bars + KPI |
| O8 | Period comparison | Same engine; granularity & compare controls shared | Same UI as admin |

### 5.3 Interactive experience (§4 of the brief)

- `x-filter-bar`: date from/to + **preset chips** (7d / 30d / this month / this year / all time) + **granularity** (daily/weekly/monthly — weekly = ISO week `DATE_FORMAT(x, '%x-W%v')` / SQLite `strftime('%x-W%W')`; driver-selected like the existing month expression) + **compare-to-previous-period** toggle (`?compare=1`).
- Everything is URL-parameter driven → shareable, server-rendered (no client-side recomputation of truth), works without JS.
- Tooltips: single Alpine tooltip showing date, value, series name; charts expose data as `data-*` attributes.
- Loading: charts render inline (no async fetch on first paint — data arrives with the page); granularity/date changes trigger a normal form GET with a small skeleton via `data-reveal`. Empty states per section ("No sales in this period" style, already established). Error states: validation errors from the existing `dateRange()` rules render in the filter bar.
- Export: **CSV download** of the period's daily series + top products per report (`?export=csv` — stream response, no library).
- Every advanced widget gets a one-line "What this means" footnote (e.g. slope, z-score, median vs mean) — no calculus vocabulary required.

### 5.4 Performance (§9)

- All aggregates computed in SQL (GROUP BY / SUM / COUNT) — no model hydration for series.
- `Cache::remember("analytics:{scope}:{hash of params}", now()->addMinutes(10), …)` around the analytics payload (database cache driver — zero new infra). Invalidation is acceptable at 10-min staleness for analytics; dashboards stay uncached for the live "today" cards (cheap queries).
- Series queries bounded by the selected range; no unbounded history loads (hard cap: ranges > 366 days are bucketed monthly).
- Charts are static SVG — no per-frame JS, no canvas loops. The existing motion JS is untouched.

## 6. Files to be created / modified (planned)

**New (planned):**
```
app/Services/Analytics/AnalyticsService.php        (series, regression, percentiles, z-scores, Pareto, burn-rate)
app/Services/Analytics/AdminAnalyticsController.php (or extend ReportsController — decided in Phase 3)
docs/PHASE_2_ANALYTICS_METHODOLOGY.md              (§10 requirement: math methodology per report)
tests/Feature/AdminAnalyticsTest.php, OwnerAnalyticsTest.php, AnalyticsMathTest.php (unit)
resources/views/components/stat-card.blade.php, chart-card.blade.php, filter-bar.blade.php,
                           chart-line.blade.php, chart-bars.blade.php, chart-pareto.blade.php,
                           chart-donut.blade.php, chart-heatmap.blade.php, sparkline.blade.php,
                           data-table.blade.php, badge.blade.php
```

**Modified (planned):** `resources/views/dashboards/admin.blade.php`, `dashboards/owner.blade.php`, `reports/admin.blade.php`, `reports/owner.blade.php`, `AdminController.php`, `ReportsController.php`, `Owner/DashboardController.php`, `resources/css/app.css` (tokens + chart styles only), `resources/js/app.js` (small tooltip Alpine component), `layouts/app.blade.php` (Figtree 700 weight — one line), `tailwind.config.js` (chart color tokens if tokenized in config).

**Explicitly untouched:** all controllers/views outside dashboards+reports, the motion system, existing components, routes (except adding `export` handling inside existing report routes), and no schema changes (**no migrations required** — all analytics derive from existing columns).

## 7. Testing plan

- **Unit (AnalyticsMathTest):** regression slope/intercept on known series; percentile/median correctness (even/odd counts); z-score flagging incl. zero-σ guard; Pareto cumulative share; burn-rate/days-of-stock; moving average; empty/1-point series guards.
- **Feature (admin):** report renders with factory-seeded orders across statuses; net-sales definition preserved (cancelled/pending excluded); delta-vs-previous-period correctness; granularity grouping (week/month); CSV export response; cache key isolation between different param sets.
- **Feature (owner isolation):** owner A's numbers never include owner B's products; direct-URL access with foreign data impossible; owner report totals equal manual SUM over their items.
- **Regression:** full existing suite (51 tests) must stay green; browser smoke pass of all four upgraded pages at three viewports (same tooling as the Phase 1 UI pass).

## 8. Branch & delivery strategy (needs your call at approval time)

- Implement on a **new branch `feature/phase-2-analytics-ui` created from `feature/phase-1-production-readiness`** (analytics builds on the Phase 1 fixes; both branches push to GitHub at the end per your instruction).
- Commits: one logical commit per phase (design system → admin analytics → owner analytics → responsive pass → tests/docs).
- The final push will include the Phase 1 branch (still unpushed) plus the new branch — unless you'd rather merge Phase 1 into `main` first; say which you prefer with your approval of this plan.

## 9. Open decisions for you (answer with your approval)

1. **Charting approach:** zero-dependency SVG + Alpine (recommended, §4) — or do you want Chart.js?
2. **Motion system:** keep the cursor/spotlight/tilt layer as-is (recommended — it's the brand identity) or trim it for a more austere look?
3. **Branch strategy:** stack Phase 2 on the Phase 1 branch (recommended) or base on `main`?
4. **CSV export** scope: daily series + top products per report (recommended) — enough, or do you want full-table exports?

---

**Status: approved 2026-11-03 ("proceed with recommendation") and implemented. Implementation corrections vs this audit: donuts → segmented/rank bars; dual-axis Pareto → ranked bars + table twin (no dual axes); payment turnaround → submission latency (no verified-at column exists). The per-report methodology lives in [PHASE_2_ANALYTICS_METHODOLOGY.md](PHASE_2_ANALYTICS_METHODOLOGY.md).**
