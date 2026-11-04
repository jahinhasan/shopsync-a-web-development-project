# Dashboard Analytics Redesign — Final Report

Date: 2026-11-04
Scope: admin + owner dashboards rebuilt around the Phase 2 analytics payload, with a
four-preset window selector and mathematically honest KPI cards. The reference screenshot
used for design direction was the existing dashboard KPI card style, so the work is an
enhancement of the current design language, not a restyle.

## 1. UI summary

- Same visual system as the rest of ShopSync: white cards, subtle `gray-200` borders,
  `rounded-2xl` corners, indigo accent, the app's existing chart components and reveal
  animation. No new JS frameworks, no chart libraries, no dependencies added.
- Three new Blade components, reused by both dashboards:
  - `<x-kpi-card>` — title, large tabular value, icon tile top-right, one-line subtitle,
    optional Δ% chip (green/red/gray via arrow glyph, `invert` prop for metrics where down
    is good). Matches the reference card layout exactly.
  - `<x-range-picker>` — 7 days / 30 days / 90 days / 12 months as plain GET links (works
    without JavaScript); the active preset is highlighted and `aria-current="page"`.
  - `<x-empty-state>` — dashed-border card for "no data in this window yet".
- Mathematical intelligence lives in the numbers and their captions, not in visual noise:
  every rate tile carries its own denominator ("0 of 7 items delivered"), every estimate
  carries an honest footnote (projection ≠ fact, Pareto share is measured, days-of-stock
  assumes recent pace), and every gap in the data renders as a stated empty state rather
  than a zero or a fabricated number.

## 2. Admin dashboard changes

- Hero replaced by six `<x-kpi-card>`s: Total Users (Δ new customers), Total Products,
  Categories, Total Orders (Δ orders), Processed Sales (Δ net sales), Low-Stock Products —
  the last now shows the real `COUNT(stock ≤ 5)` instead of the old tile that counted a
  `take(8)` display collection (a silently capped figure).
- New analytics section under the hero, driven by `AnalyticsService::overview(null, window,
  compare: true)`:
  - **Sales trend** — net sales + moving average overlay (7-period daily / 3-period weekly
    or monthly), with trend chips (direction, rate of change, Δ% vs previous window),
    regression line, projection (n ≥ 14, labelled estimate), anomalies, and a Chart/Table
    twin whose rows follow the window's bucket noun (Day/Week/Month).
  - **Order outcomes + status mix** — completion/cancellation tiles with denominators and
    "N still open" reconciliation; five-status segmented bar (all items, cancelled included).
  - **Customer growth** — registrations chart, repeat rate with its fraction, avg spend per
    ordering customer, orders-per-customer distribution (1 / 2 / 3+).
  - **Product performance** — top-5 rank bars plus the 6-column table twin (units, revenue,
    share, cumulative share) and the measured Pareto footnote.
  - **Revenue by category / by shop** — top-6 category bars; shop concentration rank bars.
  - **Inventory intelligence** — low / out / healthy counts, "Running out soonest" list with
    days-of-stock, honest-footnoted.
- Legacy sections preserved: welcome banner, User Accounts, Recent Orders, Low-Stock
  Alerts, Administration. Footer link to the full Reports page.

## 3. Owner dashboard changes

- Same six-card hero pattern, but scoped to the authenticated owner's catalogue only:
  Total Products, Total Orders (Δ), Processed Sales (Δ), Pending Orders (with "waiting on
  processing" hint), Low-Stock Products, Average Order Value (Δ; "net sales ÷ qualifying
  orders"). Deltas render only when a same-scope previous window exists and the base is
  non-zero — never a fake percentage.
- Today strip reworked: Today's Sales / Today's Orders / Available Stock (pending and
  processing moved to the hero, where they belong).
- Owner-scoped analytics section (identical componentry, "your products only" captioning):
  sales trend, order outcomes for items containing the owner's products, product
  performance Pareto + table, **Your customers** (no cross-shop data), revenue by category,
  inventory intelligence (wide card). No shop-concentration card — owners have no
  cross-shop visibility.
- All-owner AOV on the controller is now consistent with the "qualifying orders"
  definition (distinct `order_id` over qualifying items) instead of a plain order count.
- Legacy sections preserved: Recent Orders, Low Stock Alerts, Quick Actions, storefront CTA.

## 4. Mathematical methods (new in this task)

Full derivations live in `docs/PHASE_2_ANALYTICS_METHODOLOGY.md` §17–§21. Summary:

- **Windows**: 7d/30d daily, 90d weekly, 12m monthly buckets; comparison is always against
  the immediately preceding window of equal length. Whitelist validation; unknown values
  fall back to 30d at the resolver and are rejected with a validation error at the request
  layer.
- **Order funnel**: completion = delivered ÷ all items placed (pending stay in the
  denominator — an undelivered order is not a failure); cancellation = cancelled ÷ all
  items; both `null` when no items exist; `open = total − delivered − cancelled` shown so
  the numbers reconcile.
- **Trend summary**: slope direction against a `max(0.01, 1% of mean)` dead-band
  (no false "rising" on flat-but-noisy data), R² fit bands (strong ≥ 0.5 / moderate ≥ 0.25 /
  weak), rate of change from the acceleration layer (n ≥ 14).
- **Stock health**: current-state counts (`stock ≤ 5`, `stock ≤ 0`) — a stock variable is
  not window-sliced.
- **Customer frequency**: 1 / 2 / 3+ order buckets from already-computed per-customer
  counts; avg spend per *ordering customer* (net sales ÷ buyers) kept distinct from AOV
  (net sales ÷ orders); `newCustomers` Δ% admin-only, `null` on zero base.
- Zero-division, null and insufficient-data guards throughout; facts, estimates and
  unavailability are visually distinct (solid numbers vs footnoted estimates vs em-dash).

## 5. Queries & performance

- The dashboard renders one cached payload per scope+window (10-minute TTL), so page views
  cost one cache read regardless of chart count.
- Funnel, orders-per-customer distribution and status mix are **derived** from result rows
  the payload already computes — zero additional queries.
- Stock health adds exactly one aggregate query (`COUNT`, `SUM(stock<=5)`, `SUM(stock<=0)`)
  with the soft-delete scope applied by Eloquent.
- The admin `newCustomers` delta adds two indexed count queries; owners never run them.
- All aggregation stays in SQL (Phase 2 engine); no N+1: Blade iterates prepared
  collections only. MariaDB-safe grouping is covered by the Phase 2 hotfix pattern
  (`orders.id` + selected columns in `GROUP BY`).

## 6. Security verification

- Owner payloads are scoped at the service layer by `owner_id` = `Auth::id()`; the cache
  key encodes the owner id from the session, never from the request — a manipulated
  `?window=` parameter cannot widen scope (feature test: owner B still sees only their
  ৳900 while owner A sees their ৳500).
- Admin-only metrics (`shopPerformance`, `newCustomers` delta) are absent from owner
  payloads (asserted `null`).
- CSV export passes through the same scope (feature test: owner's export contains their
  product and not another owner's).
- Window parameter is server-validated; there is no client-side filtering anywhere, so
  nothing trusts the frontend.
- Both dashboards verified live on MariaDB (production-like) across all four windows with
  read-only payloads — including the `ONLY_FULL_GROUP_BY` mode that SQLite tests ignore.

## 7. Responsive results

Browser-verified (Chromium, headless) at 1440 / 1024 / 768 / 390 px on both dashboards:

- Horizontal overflow: **0 px** in all 8 combinations.
- Reveal animations: all sections revealed before capture (0 stuck-hidden elements).
- KPI grid: 2 columns ≥ `sm`, single column below; analytics cards single column below
  `lg`, wide cards span 2 of 3 columns at desktop; tables scroll inside their cards.
- Mobile renders stack cleanly; range pills wrap to one row; charts shrink with their
  cards (SVG viewBox).
- Note: full-page screenshots show the sticky navbar mid-page — a stitching artifact of
  full-page capture, not a layout defect (live scrolling is correct).

## 8. Test results

- New `tests/Feature/DashboardAnalyticsTest.php` (7 tests, 27 assertions): window presets
  & validation error; funnel denominators + null rates on empty window; customer frequency
  distribution & per-customer spend; stock-health counts; owner isolation incl. manipulated
  window; hero deltas against the preceding window; owner CSV scope.
- Full suite: **85 passed, 320 assertions** (78 prior + 7 new). All green.
- `php artisan view:cache` + `php -l` lint gate run over every compiled Blade view after
  the final template edits.

## 9. Limitations (honest gaps)

- No revenue-by-shop card for owners (they cannot see other shops' data) — by design.
- Payment verification turnaround is still not computed anywhere: the schema has no
  verification timestamp (documented in the methodology, §12).
- Days-of-stock assumes recent sales pace continues and is footnoted as a moving estimate;
  products with no sales get no estimate rather than a fake number.
- Deltas need a non-zero previous window; new stores see "No data to compare" instead of
  an inflated percentage.
- The dashboard payload is cached 10 minutes; a fresh order may take up to 10 minutes to
  appear (stated in the section caption).
- The hero Δ% chip shows raw measured percentages (e.g. +21,300% users when the previous
  window had 1 registration and the current 214) — the number is a fact, not a bug.

## 10. Files modified

Modified (7):
- `app/Http/Controllers/AdminController.php` — window resolution, scoped cache, payload pass-through (+23 lines net).
- `app/Http/Controllers/Owner/DashboardController.php` — same pattern, owner-id cache key, consistent all-time AOV (+33).
- `app/Services/Analytics/AnalyticsService.php` — `orderFunnel()`, `stockHealth()`, `trendSummary()`, `newCustomerCount()`, acquisition distribution + per-customer AOV, summary in series (+127).
- `app/Services/Analytics/WindowResolver.php` — `12m` preset, `DASHBOARD_WINDOWS`, `dashboard()`, `expandDashboardWindow()` (+48).
- `resources/views/dashboards/admin.blade.php` — KPI hero + six analytics cards (+433/−230 across both views).
- `resources/views/dashboards/owner.blade.php` — scoped hero + five analytics cards.
- `docs/PHASE_2_ANALYTICS_METHODOLOGY.md` — §17–§21 dashboard methodology.

New (4):
- `resources/views/components/kpi-card.blade.php` (57 lines)
- `resources/views/components/range-picker.blade.php` (30 lines)
- `resources/views/components/empty-state.blade.php` (15 lines)
- `tests/Feature/DashboardAnalyticsTest.php` (265 lines, 7 tests)

No dependencies added; no migrations; no existing files or features removed.
