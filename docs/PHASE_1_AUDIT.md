# ShopSync — Phase 1 Production Readiness Audit

**Date:** 2026-11-03
**Scope:** Full codebase inspection, security audit, database review, performance review,
deployment review, and test-suite verification. No source code was modified during this audit.
**Method:** Every controller, model, middleware, Form Request, migration, Docker/Render
configuration file, and test file was read in full. Findings below are backed by cited code
locations and, where noted, by actually running the test suite. Nothing is claimed from
assumption.

---

## 1. Architecture Overview

ShopSync is a **multi-role marketplace**: customers shop from products supplied by
independently approved shop owners. Registering as a shop owner grants no seller
capabilities until the owner submits a shop application with identity documents and a
reviewing admin approves it. Admins additionally moderate products and shops.

```
Browser (Blade + Tailwind CSS + Alpine.js, Vite build)
   │  nginx (production Docker) ── php-fpm ── Laravel 13
   │                                   │
   │                    MySQL-compatible TiDB Cloud (production)
   │                    SQLite (local dev & tests)
   │                    session / cache / queue → database tables
   │
   └─ Reverb WebSocket (optional; chat falls back to refresh)
```

- **Framework:** Laravel 13.37 (`laravel/framework ^13.17`), PHP `^8.3`
  (8.5.12 locally), Pest 5 test runner.
- **Frontend reality check:** the brief describes the stack as "Bootstrap 5 + Vanilla JS",
  but the application is actually **Tailwind CSS + Alpine.js + vanilla JS** built with Vite
  (`resources/css/app.css`, `resources/js/app.js`, Breeze component library in
  `resources/views/components/`). This audit treats the *actual* stack as the stack to
  preserve; migrating to Bootstrap would be a rewrite with no benefit.
- **State:** session-based cart, database sessions, database notifications.
- **Deployment:** multi-stage Docker image (node:22 assets stage → `php:8.3-fpm` +
  `nginx-light` runtime), launched on Render with a `/up` health check
  (`docker/start.sh`, `Dockerfile`).
- **Roles:** `customer`, `business_owner`, `admin` (`users.role`; admins can only be
  created via `php artisan app:create-admin`).

### Middleware / authorization chain

| Layer | Implementation | Notes |
|---|---|---|
| Authentication | Breeze session auth (`routes/auth.php`), 5-attempt login throttle (`app/Http/Requests/Auth/LoginRequest.php`) | Standard, solid |
| Role gate | `EnsureUserHasRole` (alias `role:...`) — `abort_unless(..., 403)` | Backend-enforced, correct |
| Shop approval gate | `EnsureShopApplicationApproved` (alias `shop.approved`) — allows the application form/status routes pre-approval, redirects others to status/create | Well thought through; also enforces `shop_status === 'active'` |
| Broadcast auth | `routes/channels.php` — private `chat.conversation.{id}` channel verifies participant identity | Correct |

---

## 2. Module Inventory

| Module | Status | Where |
|---|---|---|
| Registration / login / logout / password reset / password update | ✅ Implemented (Breeze + role selector on the public registration form) | `app/Http/Controllers/Auth/*` |
| Storefront browsing, search (name/description/category/shop), category filter | ✅ Implemented, login-required by design | `StorefrontController` |
| Product CRUD for owners (soft delete, image upload, slug uniqueness) | ✅ Implemented with gaps (§6) | `Owner/ProductController`, `Store/UpdateProductRequest` |
| Inventory ledger (restock/adjustment, row-locked, per-change history) | ✅ Implemented | `Owner/InventoryController`, `InventoryTransaction` |
| Session cart with availability purging | ✅ Implemented | `CartController::index/add/update/remove` |
| Checkout: COD / bKash / Nagad (manual reference), voucher redemption, server-side totals, row-locked stock decrement, atomic order + items + inventory entries | ✅ Implemented | `CartController::placeOrder`, `CustomerRewardService` |
| Per-item order lifecycle with enforced state machine (owner side) | ✅ Implemented; **cancellation does not restock** (§5) | `OderController::updateStatus` |
| Per-item payment verification with COD-after-delivery and reference checks | ✅ Implemented | `OderController::updatePaymentStatus` |
| Loyalty points + vouchers (earned on delivery of paid items, idempotent; 100-point voucher purchase; per-item discount allocation with remainder handling) | ✅ Implemented | `CustomerRewardService`, `CustomerRewardController` |
| Product reviews: one per user/product, ratings gated on verified purchase, throttled | ✅ Implemented | `ProductReviewController` |
| Shop application flow: form with identity docs, admin assignment, review audit trail, needs-information resubmission, slug generation | ✅ Implemented | `ShopApplicationController`, `Admin/ShopApplicationController` |
| Admin moderation: product suspend/warn/remove(soft)/restore, shop suspend/close/reactivate, database notifications | ✅ Implemented | `Admin/ModerationController` |
| Category management (parent/child) | ⚠️ Implemented but **edit is broken** (§4) | `Admin/CategoryController`, `StoreCategoryRequest` |
| Real-time chat (Reverb, private channels, read receipts, throttled) | ✅ Implemented; production degrades to refresh-mode unless Reverb is provisioned (documented in README) | `ChatController`, `ChatMessageSent` |
| Dashboards (admin / owner / customer) and reports | ⚠️ **Admin sales metrics are dead** (§4) | `AdminController`, `ReportsController`, `Customer/Owner DashboardController` |
| Notifications center (database channel, mark read/read-all) | ✅ Implemented | `UserNotificationController` |
| Email verification | ⚠️ **Half-wired, dormant, one 500 path** (§5) | `routes/auth.php`, `User` model |
| Profile update / account deletion | ✅ Implemented (Breeze); deletion cascades business records (§7) | `ProfileController` |

Not present (and not assumed): payment gateway integration, public (guest) storefront,
order cancellation by customers, refunds, multi-image galleries, admin user management UI.
These are simply absent; none were advertised by existing code and left broken.

---

## 3. Verification Performed

- Read all 27 controllers/actions, 14 models, 2 middleware, 4 Form Requests, the service
  class, 19 migrations, all routes, both Docker configs, nginx/PHP config, and all 11 test files.
- **Test suite executed:** `vendor/bin/pest` — **30 tests, 28 passed, 2 failed** (121 assertions).
  - Both failures are stale Breeze-era tests that predate intentional app changes (§4 items A-1, A-2) — the app behavior itself is intentional.
  - The voucher checkout suite (`tests/Feature/CheckoutVoucherTest.php`) passes and exercises the full `placeOrder` path, confirming the transactional checkout works on SQLite.
  - Note: the local `vendor/` had been installed `--no-dev` (production-shaped, from the Docker work); dev dependencies were restored from the committed `composer.lock` with a plain `composer install` so tests could run. `composer.lock` was not modified.
- Grepped the entire codebase for writes to `orders.status` to confirm the metrics bug (§4 A-3): **only the creation default `pending` ever exists** — `grep` shows no controller, job, or observer updates that column.
- Confirmed no unescaped Blade output (`{!! !!}`), no `innerHTML`/`document.write` in JS,
  and chat DOM insertion uses `textContent` (`resources/views/chat/show.blade.php:127`).
- Confirmed `.env` is **not** tracked by git (only `.env.example` is).

---

## 4. Verified Broken Functionality

### A-1. CRITICAL (commercial correctness) — All admin sales metrics are permanently zero / empty

`Order.status` is written only once, at creation (`CartController.php:199` → `'pending'`).
The 2026-10-14 item-status migration moved the order lifecycle to `order_idems.status`
(`OderController::updateStatus` updates **items only**, `OderController.php:111`).
But three admin-facing features still filter `orders.status` with *item-level* values:

- `AdminController::dashboard` — `totalSales` (`AdminController.php:24-28`)
- `ReportsController::admin` — `totalSales`, `dailySales`, `monthlySales`, `bestSellingProducts` (all filter `Order::whereIn('status', ['processing','shipped','delivered'])` or `whereHas('order', ...)` on the same dead column, `ReportsController.php:18-63`)

Since no order ever leaves `pending`, the admin dashboard revenue figure and the entire
admin sales report render zero/empty forever. The owner dashboard and owner report
correctly aggregate `order_idems.status` — only the admin side regressed during the
item-status migration. Also note `totalSales` (order-level `total`, voucher-discounted)
is not consistent with owner-side item-level `subtotal` aggregation; both should be
recomputed from item data with an agreed definition.

### A-2. HIGH (functional) — Admin cannot edit any category without renaming it

`StoreCategoryRequest::rules()` applies `'unique:categories,name'` and
`'unique:categories,slug'` with **no `ignore($category)`** for updates, and marks `slug`
`required`. The edit form prefills the current name/slug
(`resources/views/admin/categories/edit.blade.php:35,48`), so submitting an edit with
unchanged values fails unique validation every time. The controller's
`$data['slug'] ?? Str::slug($name)` fallback (`Admin/CategoryController.php:41`) is
unreachable because the rule requires the field.

### A-3. HIGH (test suite) — 2 of 30 tests fail (stale expectations, red suite)

- `tests/Feature/Auth/RegistrationTest.php:7-21` — posts without `role`; registration now
  requires `role` (`RegisteredUserController.php:32`), so the user is never created. The
  registration *form* does include the role selector
  (`resources/views/auth/register.blade.php:20-32`), so the product flow works; only the test is stale.
- `tests/Feature/ExampleTest.php` — asserts `GET /` returns 200; `/` intentionally redirects to login (`routes/web.php:20-22`).

A red suite destroys the value of CI and violates "critical automated tests pass".

### A-4. MEDIUM (functional, decision needed) — Cancelled order items never restock

`updateStatus` allows `pending→cancelled` and `processing→cancelled`
(`OderController.php:84-90`), but stock was already decremented at checkout
(`CartController.php:230`) and cancellation neither restores stock nor writes an
`InventoryTransaction` ledger entry. Customers cannot cancel at all — only owners can.
This may be an intended business rule; it needs an explicit decision before implementing
(see §12).

### A-5. LOW (latent inconsistency) — `order_idems.payment_status` vocabulary drift

`orders.payment_status` defaults to `'unpaid'` in the migration
(`2026_10_13_124706_create_orders_table.php:19`) but is created as `'pending'` at runtime
(`CartController.php:204`). Views render the value generically (`ucfirst`), so nothing
user-visible breaks today; the default is dead vocabulary that will bite future
queries (`where('payment_status', 'unpaid')` style bugs).

### A-6. LOW (crash path) — Stray model parameter on `placeOrder`

`CartController::placeOrder(Request $request, Product $products)` (`CartController.php:119`)
declares a route-bound model for a route with no `{product}` placeholder. The container
resolves it as an unused empty model — harmless at runtime but misleading signature.

---

## 5. Dormant / Incomplete Functionality

### B-1. HIGH (decision needed) — Email verification is half-wired

- `User` does **not** implement `MustVerifyEmail` (`app/Models/User.php:5` — commented out).
- The framework's auto-registered `SendEmailVerificationNotification` listener therefore
  skips every registration; `verified` middleware on `/dashboard`
  (`routes/web.php:31`) is a no-op for this model.
- The Breeze routes still exist: `verify-email/{id}/{hash}` and
  `POST email/verification-notification` (`routes/auth.php:31-38`). Hitting the resend
  endpoint calls `$request->user()->hasVerifiedEmail()` — a method that only exists on
  `MustVerifyEmail` users — producing a **500 Error** on that path today.

Two coherent options (business decision, §12): finish the feature (implement
`MustVerifyEmail`, send mail, gate seller/customer surfaces) or remove the dead routes/UI
so the app doesn't advertise verification it doesn't do. Either is small; the current
state is the only wrong one.

### B-2. HIGH (deployment dependency) — Production email is not provisioned

`.env.example` and README configure `MAIL_MAILER=log`. On Render this means **password
reset emails (and any future verification email) are written to the log instead of being
delivered** — i.e. account recovery is impossible in production today. This needs SMTP
environment variables documented and provisioned before commercial use.

### B-3. MEDIUM — Real-time chat requires Reverb in production

Documented fallback (refresh to see messages) exists and the Vite build bakes placeholder
Reverb credentials so the bundle never breaks (`Dockerfile:20-28`). Acceptable for demo;
must be provisioned (or kept off deliberately) for commercial use.

---

## 6. Security Findings

### C-1. HIGH — Product image upload accepts arbitrary file types

`StoreProductRequest` validates the upload as `'file', 'max:2048'` with **no `mimes`/`image`
rule** (`app/Http/Requests/StoreProductRequest.php:37`). An approved shop owner can upload
any ≤2 MB file onto the **public** disk (`products/` → served by nginx at `/storage/...`
with a 30-day immutable cache, `docker/nginx.conf`). Two concrete attacks:

- Upload an `.svg` containing `<script>` — nginx serves it as `image/svg+xml`; scripts in
  SVG execute on the site origin when opened → **stored XSS** against admins/customers.
- Upload an `.html` file → phishing page hosted on the shop's origin.

`UpdateProductRequest` correctly uses `'image'` (`UpdateProductRequest.php:37`), but note
Laravel's `image` rule matches by MIME-type wildcards and still accepts `image/svg+xml` —
the fix must pin an explicit allow-list (`mimes:jpg,jpeg,png,webp,gif`, exclude SVG) on
**both** forms. Owner trust boundary matters: owners are externally approved strangers,
not staff.

Mitigating context: shop owners must first pass identity verification and admin approval,
and the upload size is capped at 2 MB. This is a trust-boundary hardening issue, not an
open-to-the-internet hole (uploads require `role:business_owner` + approved shop).

### C-2. Verified-solid areas (no action needed)

- **SQL injection:** every dynamic query uses Eloquent/query-builder parameter binding;
  the only `selectRaw` uses fixed strings (`CustomerRewardService.php:22-28`) or bound
  aggregates. No `DB::raw` with user input anywhere.
- **XSS in Blade:** zero `{!! !!}` occurrences in active templates; chat JS renders with
  `textContent`; Alpine usage is declarative.
- **CSRF:** all state-changing routes are `web` middleware (Laravel's `VerifyCsrfToken`);
  the SPA-style chat POST sends `X-CSRF-Token` from the `<meta>` tag.
- **Mass assignment:** controllers build explicit attribute arrays; `User::$fillable`
  includes `role` but the only mass-assignment of users is registration, where role is a
  validated, intentional selector (`customer|business_owner` only — `admin` is unreachable
  from HTTP and creatable only via `app:create-admin`. This is a **deliberate design**:
  self-selected `business_owner` grants nothing until `shop.approved` passes. Documented
  here so it is recognized as intentional, not an escalation path.
- **Order/object authorization:** every owner action re-verifies product ownership
  server-side (`OderController.php:77-82,133-138`; `Owner/ProductController` checks
  `user_id` on edit/update/destroy); customer order views abort on mismatched `user_id`
  (`OderController.php:30`); chat conversations verify participant identity in controller
  *and* broadcast channel; identity documents are streamed through an authorization check
  with `no-store` + `nosniff` and are never given public URLs
  (`Admin/ShopApplicationController.php:213-233`), stored on the private `local` disk.
- **Privilege escalation:** admin routes are doubly namespaced under `role:admin` groups
  with numeric route constraints (`routes/web.php:174-248`); shop-application review is
  additionally scoped to the *assigned* admin (`findAssignedApplication`, 404-not-403 to
  avoid existence disclosure).
- **Financial integrity:** totals computed server-side from locked rows
  (`CartController.php:163-180`); browser-supplied money values are never trusted;
  voucher preview validated *before* the order transaction and applied *inside* it with
  double-application guards (`CustomerRewardService.php:169-172`).
- **Login:** bcrypt with `hashed` cast, 5-attempt throttle keyed `email|ip`, session
  regeneration on login/logout (Breeze `AuthenticatedSessionController`), CSRF token
  rotation on logout.
- **Secrets:** `.env` untracked (verified via `git ls-files`); `.dockerignore` blocks
  `.env*` from image layers; nginx denies dotfiles; nginx docroot is `public/` only;
  `display_errors=Off`, `expose_php=Off` in production PHP config.

### C-3. LOW — Security hardening candidates (not vulnerabilities)

- `trustProxies(at: '*')` (`bootstrap/app.php:20`) trusts every proxy. Correct behind
  Render's single ingress (TLS terminates there); if the app is ever exposed directly,
  clients could spoof `X-Forwarded-For`. Document or pin to Render ranges later.
- No global `X-Content-Type-Options: nosniff` on `/storage/*` static files (nginx adds it
  only to streamed identity documents). Adding it in the nginx image location block is
  one line and complements the C-1 fix.
- Checkout/order placement has **no rate limit** (login, reviews, chat, verification all
  have one). An authenticated user could script rapid order placement to force-reserve
  stock. Add `throttle:` to `checkout.place`.
- Session cookie flags are env-dependent; production should set
  `SESSION_SECURE_COOKIE=true` (Render is HTTPS-only) — currently unset, so the flag
  defaults off.

---

## 7. Database Findings

Schema reviewed across all 19 migrations. Overall quality is good: FK constraints
throughout, sensible cascade/null-on-delete policies, composite indexes where queries
need them (`product_reviews(product_id,rating)`, `(product_id,user_id)` unique,
`chat_messages(conversation_id,created_at)`, `(conversation_id,read_at)`,
`shop_applications(assigned_admin_id,status)`, `products(category_id,is_active)`,
`(user_id,is_active)`, `customer_vouchers(user_id,status,expires_at)`,
`customer_point_transactions` uniqueness that enforces idempotent reward processing).
`orders.order_number` and `shop_applications.slug` are unique.

### D-1. MEDIUM — Account deletion destroys business records (cascade review)

`products.user_id` and `orders.user_id` are `cascadeOnDelete`; `shop_applications.user_id`
too. A customer or owner deleting their account (`ProfileController::destroy`) wipes their
orders — including order items referenced by *other* parties' dashboards (order items
themselves cascade with orders; `order_idems.product_id` survives via `nullOnDelete` with
snapshot columns). For a commercial system, deleting a buyer should not erase the sales
history sellers and admins rely on. Options: soft-delete users, or restrict
account deletion for users with orders, or re-point `orders.user_id` to
`nullOnDelete` with a snapshot. **Decision needed (§12); no destructive migration will be
proposed without approval.**

### D-2. LOW — Validation/column mismatches (no migration needed; fix in requests)

- `StoreProductRequest` allows `price` up to `99999999999.99`
  (`StoreProductRequest.php:33`) but the column is `decimal(10,2)` → values above
  `99,999,999.99` pass validation and throw a database error (500). The update request
  already uses the correct max — copy it.
- Money math uses `(float)` casts (`CartController.php:179,225`); with 2-decimal columns
  this is low-risk today, but totals should be computed consistently (e.g. round per line,
  as `CustomerRewardService` already does) — noted, not urgent.

### D-3. LOW — Migration hygiene

- `add_status_to_order_idems_table` backfills item statuses from the then-authoritative
  `orders.status` — correct historical move, but the follow-through (updating admin
  dashboards/reports) is exactly what A-1 shows was missed.
- Table/column naming: `order_idems` (sic — "items" misspelled, `OrderIdem` model). Renaming
  a production table is invasive and low-value; **recommendation: leave it**, document the
  quirk. `OderController` (sic) likewise — renaming the class is internal-only and safe to
  do later if desired.
- No `jobs`/`cache` pruning configured; tables will grow slowly under `QUEUE_CONNECTION=database`.

---

## 8. Performance Findings

Overall **good**. Verified specifics:

- Eager loading used correctly where it matters (`availableToCustomers` scope adds
  `withAvg`/`withCount` subqueries for ratings; storefront/dashboard load
  `owner`, `category`; owner order management filters + eager-loads per-owner items in one
  query set; chat loads `lastMessage`, unread counts via `withCount`).
- No N+1 loops found in controllers; dashboards use aggregate SQL, not PHP loops.
- `Customer/DashboardController` runs ~6 queries + a paginated shop aggregate — acceptable
  at lab scale; indexes above cover the joins.
- Pagination everywhere (`paginate(12/15/20)`), `withQueryString()` preserved.
- OPcache with `validate_timestamps=0`, precompiled views, config cache at boot
  (`docker/start.sh`), gzip for text assets in nginx. Route caching is intentionally
  skipped (closure route on `/dashboard`) — a `match` on role could be moved to a
  controller to allow `route:cache`, a small win.
- The storefront `availableToCustomers()` scope includes a `whereHas(owner.shopApplication)`
  correlated subquery per query — fine now, becomes a candidate for denormalized
  `shop_status` on products only if storefront latency ever becomes a measured problem.
  Not recommended to change now.

No action items in this section beyond keeping current patterns.

---

## 9. UI/UX Findings (code-level)

A full visual pass (desktop/tablet/mobile rendering) has **not** been performed yet —
that requires running the app and exercising views (planned Phase 2 task; noted honestly
here rather than fabricated). Code-level observations:

- Consistent design language: Tailwind-based, Breeze components reused, dark overlay
  pattern for shop-locked owners with meta-refresh polling
  (`resources/views/layouts/app.blade.php`) — functional but a heavier pattern than needed.
- Accessibility basics present: `aria-*` on dialogs, `sr-only` labels, `role="dialog"`.
- Copy defects (user-visible typos): `'sucessful', 'productbn updated successfully'`
  (`Owner/ProductController.php:88`), `'your cart is empty'` lowercase
  (`CartController.php:138`), `'please review your cart.'` lowercase
  (`CartController.php:175`), `'Create anadminstarator'` (`CreateAdmin.php:24`).
- Flash keys inconsistent: product update uses `with('sucessful', ...)` while views listen
  for `success` → **the product-update success toast never shows** (same root cause as the
  typo; fix together).
- Tables/forms are Tailwind with responsive classes; mobile verification pending the
  visual pass.
- Empty states: cart, notifications, rewards, and moderation list templates include empty-state
  markup (verified by presence of `@empty`/conditional rendering in those views).

---

## 10. Deployment Findings (Docker + Render)

The containerization is in strong shape. Verified:

- Multi-stage build; npm ci → Vite build with placeholder Reverb env so the bundle never
  breaks; production Composer install from a PHP-8.3-platform-pinned lock
  (`composer-production.lock`); no dev packages in image.
- Secrets never baked: `.env*` blocked by `.dockerignore`; `start.sh` refuses to start
  without `APP_KEY`; config cache built at container start from real env
  (`docker/start.sh:37`).
- **No automatic migrations on boot** — schema changes are manual (`migrate --force`),
  so a redeploy can never run a destructive migration against production data. Correct per
  requirements.
- Health check `/up` wired both in Docker `HEALTHCHECK` and Render; logs go to
  stdout/stderr (nginx + PHP), Laravel log channel to stderr via `php-prod.ini`.
- Permissions: code root-owned read-only, `www-data` owns only `storage` + `bootstrap/cache`;
  storage link created idempotently at boot.
- nginx: docroot `public/` only, dotfile deny, `fastcgi_hide_header X-Powered-By`,
  `try_files $uri =404` on PHP location (no path traversal), 25 MB body cap matching PHP.

### E-1. HIGH (commercial data loss) — Uploaded files are ephemeral on Render

The image bakes `storage/app/public/` **as built** (deliberate — commit b1aec25 — so
seeded demo products have images), but any image uploaded *after* deployment lives on the
container filesystem and is **lost on redeploy/restart**. Same for the database-backed
sessions if the container is replaced (sessions/cache tables live in TiDB, so they are
fine; only files are affected). Options, in order of preference:

1. Render persistent disk mounted at a storage path (paid feature; document only).
2. S3-compatible object storage for the `public` disk (needs explicit approval per
   constraints — not proposed now).
3. Accept ephemerality for demo and **document it** in the deployment guide (minimum).

No change will be made without approval; this is a documented-risk decision (§12).

### E-2. MEDIUM — Production environment checklist items (documentation, no code)

- `APP_DEBUG=false` must be confirmed on Render (local `.env` is a dev file; the Render
  dashboard is the source of truth and cannot be inspected from here).
- `SESSION_SECURE_COOKIE=true` (see C-3).
- SMTP env vars for password reset (see B-2).
- Reverb env vars if real-time chat is wanted (README already documents the fallback).
- `SHOPSYNC_BKASH_NUMBER` / `SHOPSYNC_NAGAD_NUMBER` — without them those payment methods
  are correctly rejected at checkout with a clear message (`CartController.php:183-190`);
  the feature degrades safely.

---

## 11. Prioritized Issue Register

| # | Priority | Issue | Where |
|---|---|---|---|
| A-1 | **Critical** | Admin dashboard/report sales metrics read dead `orders.status`; always zero | `AdminController.php:24`, `ReportsController.php:18-63` |
| A-2 | **High** | Category edit always fails unique validation (no `ignore()` on update) | `StoreCategoryRequest.php:36-38` |
| C-1 | **High** | Product image upload: no MIME allow-list (store), SVG leaks through `image` rule (update) | `StoreProductRequest.php:37`, `UpdateProductRequest.php:37` |
| A-3 | **High** | Test suite red: 2 stale tests (registration `role`, `/` redirect) | `RegistrationTest.php`, `ExampleTest.php` |
| B-1 | **High** | Email verification half-wired; resend endpoint 500s; needs finish-or-remove decision | `User.php:5`, `routes/auth.php:31-38` |
| B-2 | **High** | No production mail → password reset impossible on Render | `.env.example`, README |
| E-1 | **High** | Post-deploy uploads ephemeral (document/decide; demo-acceptable) | `Dockerfile:65` |
| A-4 | Medium | Cancelled items don't restock (needs business decision) | `OderController.php:84-111` |
| D-1 | Medium | Account deletion cascades business records (needs policy decision) | `ProfileController.php:42`, migrations |
| C-3 | Medium | Checkout unthrottled; nosniff on `/storage`; secure-cookie flag | `routes/web.php:86`, `nginx.conf` |
| A-5 | Low | `payment_status` default `'unpaid'` vs runtime `'pending'` | orders migration vs `CartController.php:204` |
| D-2 | Low | Store price max exceeds column; float money math consistency | `StoreProductRequest.php:33` |
| A-6 | Low | Stray `Product $products` param on `placeOrder` | `CartController.php:119` |
| §9 | Low | Copy typos + `sucessful` flash key (success toast never shows) | `Owner/ProductController.php:88`, `CartController.php:138,175` |
| §7 | Low | Repo hygiene: 21 committed `.bak`/`before-*` files; root junk files (`an serve`, `an view:clear`); untracked 630 KB `shopsync-db-backup.sql` **not gitignored**; unused `dashboard`/`welcome` views | repo root, `resources/` |
| §7 | Low | `order_idems`/`OderController` misspellings — recommend leave-as-is, document | schema |

---

## 12. Decisions Needed From You (blockers to implementation, not inventions)

1. **Email verification:** implement fully (requires production SMTP) or remove the dead
   routes/UI? *(Recommended: remove for now — safest, no SMTP dependency — and add
   properly when mail is provisioned.)*
2. **Cancellation restocking:** when an owner cancels a pending/processing item, should
   stock be returned and an inventory ledger entry written? *(Recommended: yes — that's
   what the ledger is for — but this is a business rule so it needs your sign-off.*
3. **Account deletion vs order history:** may users with order history delete their
   accounts (and cascade-wipe orders), or should deletion be blocked/softened for them?
4. **Upload persistence:** accept ephemeral uploads for now (documented), or plan a Render
   disk / object storage migration? *(No S3 migration will happen without explicit approval.)*

Everything else in the register above is unambiguous defect-fixing and needs no business
input.

---

## 13. Recommended Implementation Plan (pending your approval)

Ordered, small, independently testable steps. Nothing touches production data; no
destructive migrations; no new dependencies.

**Batch 1 — Correctness (fixes existing features)**
1. A-1: recompute admin dashboard/report sales from `order_idems` (item-level statuses,
   same definitions the owner side uses); align order-level vs item-level totals to one
   documented definition.
2. A-2: `Rule::unique(...)->ignore($category)` on update (split request rules or bind the
   route model); make `slug` optional-with-autogenerate consistently.
3. D-2: align store `price` max with the column; keep money math rounding consistent.
4. A-5/A-6: align `payment_status` vocabulary in code paths; drop the stray `placeOrder`
   parameter.

**Batch 2 — Security hardening**
5. C-1: explicit `mimes:jpg,jpeg,png,webp,gif` on both product image forms (SVG excluded);
   delete the replaced image file when a new one is stored (orphan prevention);
   `nosniff` on nginx static block.
6. C-3: `throttle` checkout; document `SESSION_SECURE_COOKIE=true` for Render.

**Batch 3 — Suite green + regression coverage**
7. A-3: update the two stale tests; add focused tests for: admin sales metrics (the A-1
   regression), category update (A-2), image MIME rejection (C-1), order item state
   machine and payment verification authorization, inventory stock bounds. Run suite.

**Batch 4 — Decisions + docs (after your answers to §12)**
8. Implement (or remove) email verification per decision; cancellation restocking per
   decision; account-deletion policy per decision.
9. Create `docs/PHASE_1_IMPLEMENTATION_REPORT.md` (changes, reasons, test evidence) and
   `docs/PHASE_1_DEPLOYMENT_GUIDE.md` (env var reference without secrets, build/run,
   health checks, rollback, the E-1 persistence note, B-2 SMTP setup).

**Batch 5 — Hygiene + UI/UX pass (lowest risk, done last)**
10. Copy/flash-key fixes (§9); remove committed `.bak`/`before-*` files, junk root files,
    unused views, and gitignore the SQL backup *(each removal listed for your approval
    before deletion — none are app code paths)*; browser-based responsive pass on
    desktop/tablet/mobile with screenshots, fixing only genuine layout breakage.

**Deliberately out of scope (per constraints):** renaming `order_idems`/`OderController`;
framework or frontend-stack migration; payment gateways; new roles; refactors of working
code without measured benefit.

---

## 14. Test Evidence

```
vendor/bin/pest — 30 tests: 28 passed, 2 failed, 121 assertions

PASS  auth registration screen renders / login / logout / email verification notice /
      password confirm / reset flows / profile update & deletion / rate limiting
      (5 attempts, then throttle) / checkout vouchers (invalid → no order + stock intact;
      valid → discount, redemption, stock decrement; mismatch → no order)
FAIL  RegistrationTest::new_users_can_register        (stale — missing `role` field, A-3)
FAIL  ExampleTest::returns_a_successful_response      (stale — `/` now redirects, A-3)
```

Test DB: SQLite `:memory:` (`phpunit.xml`) — production TiDB is never touched by tests.

---

*Prepared as the Phase 1 deliverable. Implementation begins only after review and
approval of §12–§13.*
