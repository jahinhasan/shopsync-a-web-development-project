# ShopSync — Phase 1 Implementation Report

**Date:** 2026-11-03
**Branch:** `feature/phase-1-production-readiness`
**Companion documents:** [PHASE_1_AUDIT.md](PHASE_1_AUDIT.md) (findings), [PHASE_1_DEPLOYMENT_GUIDE.md](PHASE_1_DEPLOYMENT_GUIDE.md)

All fixes follow the audit's issue register (A-x = broken functionality, B-x = dormant
functionality, C-x = security, D-x = database, E-x = deployment). No migrations were
created — **the database schema is unchanged**, so production data compatibility is
preserved by construction. No production data was touched; all verification ran against
the SQLite test database.

---

## 1. Changes Implemented

### 1.1 Admin sales metrics restored (Audit A-1 — Critical)

| File | Change |
|---|---|
| `app/Http/Controllers/AdminController.php` | `totalSales` now aggregates `SUM(order_idems.subtotal)` filtered on item statuses `processing/shipped/delivered` — the same authoritative lifecycle the owner dashboard uses. |
| `app/Http/Controllers/ReportsController.php` | `admin()` report: `totalSales`, `dailySales`, `monthlySales`, `bestSellingProducts` all now derive from a shared, item-status-filtered query scoped to the report's order date range. `orders.status` is no longer referenced by any sales query. |

**Documented metric definition (applies to both admin and owner sides):** *net sales* =
sum of order-item subtotals whose item status is `processing`, `shipped` or `delivered`.
Cancelled and still-pending items are excluded. Voucher discounts are not netted out at
the metric level (item `subtotal` is gross); the per-order discounted total remains on
`orders.total` for receipt display. This one definition is now used consistently.

Portability note: the monthly grouping uses `DATE_FORMAT` on MySQL-compatible databases
(TiDB production) and `strftime` on SQLite (tests) — selected by driver, production SQL
unchanged.

### 1.2 Category editing fixed (Audit A-2 — High)

`app/Http/Requests/StoreCategoryRequest.php`

- `name` and `slug` unique rules now `ignore()` the category being updated, so editing a
  category without renaming no longer fails validation (duplicate names across different
  categories are still rejected).
- `slug` is now `nullable` with the controller's existing `Str::slug()` fallback, so
  creating a category without typing a slug works (the fallback was previously dead code
  behind a `required` rule).
- Fixed the case-sensitive-style import `illuminate\Validation\Rule` → `Illuminate\Validation\Rule`.

### 1.3 Product upload validation + image lifecycle (Audit C-1, D-2 — High)

| File | Change |
|---|---|
| `app/Http/Requests/StoreProductRequest.php` | Image rule changed from generic `file` to `image, mimes:jpg,jpeg,png,webp,gif` — arbitrary files (HTML, SVG, executables) can no longer be planted on the public disk. `price` max aligned with the `decimal(10,2)` column (`99999999.99`), preventing a 500 on oversized values. |
| `app/Http/Requests/UpdateProductRequest.php` | Explicit `mimes:jpg,jpeg,png,webp,gif` added — this also closes the Laravel `image`-rule loophole that admits `image/svg+xml`. |
| `app/Http/Controllers/Owner/ProductController.php` | Replacing an image now deletes the old file from the `public` disk (orphan prevention). Flash key typo fixed (`sucessful` → `success`, message text corrected) so the success toast actually displays. |

Trust boundary note: uploads still require an approved `business_owner`; this hardens the
boundary against the approved-owner SVG/HTML same-origin script injection identified in
the audit.

### 1.4 Cancellation restocking (Decision #2 — Audit A-4)

`app/Http/Controllers/OderController.php` — `updateStatus()` rewritten:

- The status transition now happens inside a `DB::transaction` with `lockForUpdate()` on
  the order item (and on the product when cancelling), so concurrent duplicate requests
  serialize — the second request reads the already-applied status and exits through the
  "unchanged" path. **Stock can never be restored twice.**
- Cancelling a `pending` or `processing` item restores its quantity to `products.stock`
  and writes an `InventoryTransaction` of type `cancellation` with full
  before/change/after quantities and the order reference. Items whose product row no
  longer exists are cancelled without restock (defensive; force-deletion is not used).
- The existing state machine is untouched (`delivered`/`cancelled` are terminal);
  invalid transitions now return the same validation error atomically from inside the
  transaction.
- `processDeliveredItems()` still runs after the transaction commits on the `delivered`
  path.

### 1.5 Account deletion policy (Decision #3 — Audit D-1)

`app/Http/Controllers/ProfileController.php` — `destroy()`:

- Accounts with business records (orders, products, a shop application, product reviews,
  or chat messages) are **anonymized instead of hard-deleted**: name → `Former user #<id>`,
  email → `former-user-<id>@shopsync.invalid` (`.invalid` TLD, frees the original email
  for reuse), password randomized (login impossible), `remember_token` and
  `email_verified_at` cleared. Order, product, application, review and chat history is
  fully preserved and still renders everywhere it is referenced.
- Accounts with no business records are removed outright (previous Breeze behavior,
  password-confirmed as before).

### 1.6 Dormant email verification removed; password reset kept (Decision #1 — Audit B-1)

Removed (this was the feature's complete remaining surface — listed here explicitly as
the approval covers exactly these five files):

- `app/Http/Controllers/Auth/VerifyEmailController.php`
- `app/Http/Controllers/Auth/EmailVerificationPromptController.php`
- `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- `resources/views/auth/verify-email.blade.php`
- `tests/Feature/Auth/EmailVerificationTest.php` (the removed feature's test file)

Also removed: the three verification routes in `routes/auth.php` and the dead
`send-verification` form block in the profile form
(`resources/views/profile/partials/update-profile-information-form.blade.php` — it called
`route('verification.send')` unconditionally and would have thrown a 500 on the profile
page once the route was removed).

Kept intact: **password reset end-to-end** (request, email, token reset, update),
password change, password confirmation. SMTP provisioning for password reset is
documented in the deployment guide (§4).

### 1.7 Upload persistence preparation (Decision #4 — Audit E-1)

`config/filesystems.php` — the `public` disk root now reads
`env('FILESYSTEM_PUBLIC_ROOT', storage_path('app/public'))`. Unset, behavior is exactly
as before. Set (e.g. to a mounted Render disk path), uploads and `php artisan
storage:link` follow the persistent volume with no further code changes. `.env.example`
documents the variable; the deployment guide describes the full Render-disk procedure.
No external storage provider was introduced.

### 1.8 Security hardening (Audit C-3)

- `routes/web.php` — `POST /checkout` now runs under `throttle:10,1` (order-flood/stock
  reservation abuse bounded; login 5/min, reviews 10/min, chat 30/min previously existed).
- `docker/nginx.conf` — `X-Content-Type-Options: nosniff` added to the static-file
  location that serves `/storage` uploads. (One-line, additive; no behavior change
  otherwise.)

### 1.9 Minor correctness/copy (Audit A-6, §9)

- `CartController::placeOrder()` — removed the stray unused `Product $products` parameter
  (route has no model binding); capitalized the two customer-facing cart error messages.
- `app/Console/Commands/CreateAdmin.php` — help text typo fixed.
- `.gitignore` — added `/*.sql` so local database backups (`shopsync-db-backup.sql`,
  630 KB) can never be committed accidentally. The backup file itself was **not** touched.

---

## 2. Files Changed

**Modified (11):**

```
app/Http/Controllers/AdminController.php
app/Http/Controllers/CartController.php
app/Http/Controllers/OderController.php
app/Http/Controllers/ProfileController.php
app/Http/Controllers/ReportsController.php
app/Http/Controllers/Owner/ProductController.php
app/Http/Requests/StoreCategoryRequest.php
app/Http/Requests/StoreProductRequest.php
app/Http/Requests/UpdateProductRequest.php
config/filesystems.php
docker/nginx.conf
```

Plus: `routes/web.php`, `routes/auth.php`, `.env.example`, `.gitignore`,
`app/Console/Commands/CreateAdmin.php`,
`resources/views/profile/partials/update-profile-information-form.blade.php`,
`tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/ExampleTest.php`,
`tests/Feature/RateLimitingTest.php` — **19 modified files total.**

**Deleted (5 — decision #1 scope, enumerated above).**

**Created (7):**

```
docs/PHASE_1_AUDIT.md
docs/PHASE_1_IMPLEMENTATION_REPORT.md   (this file)
docs/PHASE_1_DEPLOYMENT_GUIDE.md
tests/Feature/AdminSalesMetricsTest.php
tests/Feature/AdminCategoryManagementTest.php
tests/Feature/ProductImageUploadTest.php
tests/Feature/OrderCancellationTest.php
tests/Feature/AccountDeletionTest.php
tests/Feature/Auth/EmailVerificationRemovalTest.php
```

**Database changes: none.** No new or altered migrations; zero production-data risk.

---

## 3. Test Results

```
vendor/bin/pest
  BEFORE implementation:  30 tests | 28 passed | 2 failed (stale expectations) | 121 assertions
  AFTER implementation:   51 tests | 51 passed | 0 failed | 228 assertions
```

New/updated coverage and the defect each test pins down:

| Test | Guards against |
|---|---|
| `AdminSalesMetricsTest` (3 tests) | A-1 regression: dashboard/report sales come from item statuses, cancelled/pending excluded, date-range filtering works |
| `AdminCategoryManagementTest` (3 tests) | A-2 regression: update without renaming succeeds; cross-category duplicates still rejected; slug autogeneration |
| `ProductImageUploadTest` (4 tests) | C-1 regression: `.txt` and `.svg` uploads rejected and nothing stored; valid PNG stored; replaced image file deleted |
| `OrderCancellationTest` (5 tests) | Decision #2: restock + ledger entry on cancel; **duplicate cancellation restores stock exactly once**; shipped items refuse cancellation; other owners get 404 |
| `AccountDeletionTest` (5 tests) | Decision #3: order/owner/application-bearing accounts anonymized with history preserved; email freed; old password cannot log in |
| `EmailVerificationRemovalTest` (2 tests) | Decision #1: verification endpoints gone (404); password reset endpoints still available |
| `RateLimitingTest` (+1 test) | C-3: checkout throttled after 10 orders/min |
| `RegistrationTest` (+1 test) | A-3: registration works (with role); `admin` role is not registrable |
| `ExampleTest` (updated) | A-3: `/` redirects guests to login |

Environment note: this machine has no PHP GD extension, so the image tests use an inline
1×1 PNG (`UploadedFile::fake()->createWithContent`) instead of `fake()->image()`.

---

## 4. Remaining Issues & Risks

### Unresolved (awaiting your approval — nothing was deleted)

1. **Repo junk files to delete** (listed per your instruction; all are committed copies
   or shell-typo artifacts, none are app code paths):
   - 21 `.bak` / `.before-item-status` / `.step2.bak` / `.before-category-dropdown.bak` /
     `.customer-dashboard.bak` files under `app/`, `resources/`, `routes/`
     (full list: `git ls-files | grep -E "\.bak|before-"`)
   - `'an serve'` and `'an view:clear'` in the repo root (accidental shell artifacts)
   - unused Breeze leftovers: `resources/views/dashboard.blade.php`,
     `resources/views/welcome.blade.php`
   - `shopsync-db-backup.sql` (630 KB local DB backup — now gitignored; recommend moving
     it out of the repo directory entirely)
2. **Naming quirks left alone deliberately** (audit §7): `order_idems` table /
   `OrderIdem` / `OderController` misspellings — renaming is invasive for zero behavior
   gain.
3. **Browser-based responsive UI pass** (audit plan Batch 5) — **not yet performed.**
   Code-level UI findings (copy, flash keys) were fixed; the desktop/tablet/mobile visual
   verification needs the app running with a browser and remains open.
4. `parent_id` category cycles are only prevented at depth one (the edit form excludes
   self); A↔B cycles remain theoretically possible via crafted requests. Rendering is
   single-level so nothing breaks today.

### Deployment risks (unchanged by this work; see deployment guide §5–6)

- Production uploads remain ephemeral on Render until a persistent disk is attached
  (procedure now prepared and documented).
- SMTP must be configured on Render or **password reset stays non-functional in
  production** (mail is currently written to logs).
- `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and Reverb credentials should be
  confirmed on the Render dashboard (checklist in the guide).

---

## 5. Verification Statement

- The full test suite passes: **51/51, 228 assertions** (SQLite in-memory; production
  TiDB never touched).
- No destructive commands were run anywhere (`migrate:fresh`/`db:wipe` never executed;
  the only `git rm` calls were the five decision-#1 files).
- Pint was run only on the six brand-new test files (per project convention, app code
  was not reformatted).
- Nothing has been pushed; commits live on the local
  `feature/phase-1-production-readiness` branch only.
