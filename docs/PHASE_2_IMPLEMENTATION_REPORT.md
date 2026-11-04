# ShopSync — Phase 2 Implementation Report

**Feature:** Mathematics-Driven UI & Advanced Analytical Visualization
**Branch:** `feature/phase-2-analytics-ui` (stacked on `feature/phase-1-production-readiness`)
**Companions:** [PHASE_2_UI_AUDIT.md](PHASE_2_UI_AUDIT.md) · [PHASE_2_ANALYTICS_METHODOLOGY.md](PHASE_2_ANALYTICS_METHODOLOGY.md)

## Result

The admin/owner Reports pages and dashboards now render a full analytics payload computed
entirely from real database records at request time — trend with regression/projection/anomaly
detection, period comparison, order-value distribution with percentiles, Pareto product ranking,
category rollup, status mix, acquisition, shop concentration, voucher funnel, payment mix +
submission latency, inventory burn-down, product movers and an order-time heatmap. Charts are
zero-dependency server-computed SVG + a small Alpine tooltip helper; every chart has a table
twin; every report exports to CSV. Statistical techniques only render when their minimum-data
guards pass; projections and estimates are labelled as such, never blended with facts.

## Files

### New — analytics engine (`app/Services/Analytics/`)

| File | Purpose |
|---|---|
| `AnalyticsMath.php` (268 ln) | Pure statistics: `linearRegression` (least squares + R²), `movingAverage`, `cumulative`, `zScores`, `percentile` (linear interpolation), `percentageChange` (null on zero base), `niceCeil`, `median`. Unit-tested in isolation. |
| `AnalyticsService.php` (960 ln) | SQL aggregation + orchestration of the whole payload; item-level net-sales definition (processing/shipped/delivered); owner scoping; driver-aware bucket SQL (SQLite `strftime` / MySQL `DATE_FORMAT`); 10-min scoped cache keys. |
| `WindowResolver.php` (67 ln) | Validates `from`/`to`/preset (`7d/30d/90d/mtd/ytd/all`, default MTD) and granularity; forces >366-day ranges to monthly. |
| `AnalyticsCsv.php` (132 ln) | Streamed CSV twin: KPIs, daily series, top products (Pareto with cumulative share). |
| `Formats.php` (81 ln) | Money/number/compact/percent formatting shared by views and CSV. |

### New — views & tests

- 9 Blade components: `chart-card`, `chart-line`, `chart-bars`, `chart-rank-bars`,
  `chart-segmented`, `chart-heatmap`, `stat-card`, `ss-badge`, `filter-bar` — all SVG/HTML/CSS,
  tooltips via shared `ssChart` Alpine component (x-text only, so DB strings are never HTML).
- `resources/views/reports/admin.blade.php` (596 ln) and `reports/owner.blade.php` (583 ln),
  preserving the legacy Monthly Sales / Best-Selling / Low-Stock / Inventory Activity tables.
- `tests/Unit/Services/AnalyticsMathTest.php` (19 tests) and
  `tests/Feature/AnalyticsReportsTest.php` (7 tests).

### Modified

- `ReportsController.php` — window resolution, cached payload, CSV export branch; Monthly Sales
  restored to all-time (matching its heading).
- `AdminController.php`, `Owner/DashboardController.php` — dashboards consume the cached 30-day
  pulse (trend chart, KPI minis, status mix / top products).
- `dashboards/admin.blade.php` (+107), `dashboards/owner.blade.php` (+79) — analytics pulse
  sections.
- `resources/css/app.css` (+69) — design tokens (`--ss-chart-*`), tooltip, heatmap styles.
- `resources/js/app.js` (+94) — `ssChart` Alpine component (crosshair/tooltip, keyboard focus).
- `layouts/app.blade.php` — reports nav entries.
- `tests/Feature/AdminSalesMetricsTest.php` — asserts the new payload contract (status
  filtering, date scoping, exclusion), preserving the original regression intent.
- `public/build/manifest.json` + removal of stale hashed assets (`/public/build` is gitignored;
  the manifest stays tracked for deploys that skip `npm run build`).

## Dependencies

**None added.** `composer.json` and `package.json` are untouched. Charts are hand-computed SVG;
statistics are hand-written PHP (~270 lines, unit-tested); interactivity is the existing Alpine.

## Calculations (summary — full detail in the methodology doc)

- **Net sales** = `SUM(order_idems.subtotal)` where item status ∈ {processing, shipped,
  delivered}; cancelled/pending excluded; owner-scoped via `product.user_id`.
- **Trend**: zero-filled buckets → trailing moving average (7/3) → least-squares regression with
  R² → 7-bucket projection (n ≥ 14 only) → z-score anomalies (n ≥ 10, σ > 0, |z| > 2) →
  acceleration (slope delta).
- **Δ%** = (current − previous)/previous, null when previous = 0; previous window = same-length
  preceding span; previous series re-indexed onto current buckets.
- **Histogram**: 6 equal-width bins; percentiles by linear interpolation; median beside mean.
- **Pareto**: share and cumulative share per ranked product.
- **Inventory**: burn/day = Σ|quantity_change| (sales) / days; days-of-stock = stock ÷ burn.
- **Movers**: current vs preceding equal-length window; "new" instead of +∞%; −100% kept visible.
- **Heatmap**: 7×12 two-hour order counts, ≥ 14 days only.

## Tests

| Suite | Result |
|---|---|
| `tests/Unit/Services/AnalyticsMathTest.php` (new) | 19 passed |
| `tests/Feature/AnalyticsReportsTest.php` (new) | 7 passed — owner isolation, net-sales definition incl. cancelled-exclusion, daily/weekly/monthly bucketing, compare deltas, guard behaviour (no projection/heatmap on short windows), CSV export contents, invalid window rejection |
| `tests/Feature/AdminSalesMetricsTest.php` (updated) | passes with the new payload contract |
| **Full suite** | **77 passed, 291 assertions** (`vendor/bin/pest --compact`) |

Also verified outside the suite: `php artisan view:cache` + `php -l` on every compiled view
(caught Blade `@php(...)` pairing pitfalls), and a browser pass across desktop/tablet/mobile —
zero layout overflow, zero console errors, CSV download verified.

## Remaining limitations

1. **No COGS/procurement cost data** → revenue-only; no margin metrics can be honest yet.
2. **Verification turnaround impossible**: the schema records no payment-verification timestamp;
   the report shows checkout→submission latency instead and says so in a footnote.
3. **Seed/demo dataset has no items advanced past pending/cancelled**, so live demo charts show
   honest empty states (৳0 net sales) — the guards and empty-state copy handle this by design.
4. **Projection assumes the fitted linear trend continues**; it is capped at 7 buckets, labelled,
  and hidden below 14 buckets of data.
5. Known pre-existing (Phase 1) issue, unchanged: "Top Shops" panel text overflows at 14px on
   narrow screens in `dashboards/customer.blade.php`.
6. Laravel Boost MCP was unavailable for part of the session; documentation lookups fell back to
   the installed framework source.
