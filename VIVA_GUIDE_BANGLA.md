# ShopSync — Full Project Explanation (Viva Preparation Guide)

> Ei file ta porle puro project er sob bujhte parba — kon code kothay, ki kaj kore,
> kontar sathe konta connected, ar common viva question er answer.
> Sararup: **ShopSync ekta multi-vendor e-commerce marketplace** (Amazon/Daraz type),
> jekhane 3 dhoroner user ache — Customer, Business Owner (seller), Admin.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Laravel MVC & Request Lifecycle](#3-laravel-mvc--request-lifecycle)
4. [Folder Structure — kon file kothay, kaj ki](#4-folder-structure)
5. [Database Design — table & relation](#5-database-design)
6. [Role & Security Layer (Middleware)](#6-role--security-layer)
7. [Feature-by-Feature Deep Dive](#7-feature-by-feature-deep-dive)
   - 7.1 Authentication (Breeze)
   - 7.2 Role-based Dashboard Redirect
   - 7.3 Storefront (product browse/search)
   - 7.4 Product Review & Rating
   - 7.5 Cart & Checkout (payment: COD/bKash/Nagad)
   - 7.6 Order Lifecycle (per-item status tracking)
   - 7.7 Inventory Management (audit trail)
   - 7.8 Shop Application (onboarding)
   - 7.9 Admin Moderation + Notifications
   - 7.10 Real-time Chat (Laravel Reverb / WebSocket)
   - 7.11 Loyalty Points & Voucher System
   - 7.12 Reports
   - 7.13 Demo Dataset Seeder (Artisan commands)
8. [Frontend — Blade, Tailwind, Alpine, Vite, Echo](#8-frontend)
9. [Config & Environment (.env)](#9-config--environment)
10. [Order Place korle Full Flow (viva classic)](#10-order-place-korle-full-flow)
11. [Security Best Practices](#11-security-best-practices)
12. [Common Viva Questions & Answers](#12-common-viva-questions--answers)
13. [Demo Login Credentials](#13-demo-login-credentials)

---

## 1. Project Overview

**ShopSync** ekta **multi-vendor online marketplace** — ekta platform jekhane
onek alada doker (shop) nijeder product bikri korte pare, customer gulo sob
shop er theke ek jaygay product kinte pare, ar ekta admin puro platform
moderate kore.

**3 dhoroner user (role):**

| Role | Ki kore |
| --- | --- |
| `customer` | Product browse, search, review, cart, checkout, order track, chat, points/voucher |
| `business_owner` | Shop apply kore, approve hole product manage, stock manage, order manage, report dekhe |
| `admin` | Shop application review, product/shop moderation, category manage, platform report |

**Main features (ek line e):**
- Shop onboarding: business owner apply → admin approve → tarpor bikri start
- Order lifecycle: per-item status `pending → processing → shipped → delivered`
- Inventory audit: prottek stock change er history (before/change/after)
- Payment: Cash on Delivery + bKash/Nagad (mobile banking reference verify)
- Real-time buyer-seller chat (WebSocket)
- Loyalty program: points earn + voucher + surprise reward

**Role er naam gulo code e:** `users` table er `role` column — value: `admin`,
`business_owner`, `customer`.

---

## 2. Tech Stack

| Layer | Technology | Kothay dekhbe |
| --- | --- | --- |
| Backend | Laravel 13, PHP 8.3+ | `composer.json` |
| Database | MySQL/MariaDB (SQLite o chole) | `.env`, `config/database.php` |
| Auth | Laravel Breeze (session-based) | `composer.json` → `laravel/breeze` |
| Real-time | Laravel Reverb (WebSocket server) + Laravel Echo + pusher-js | `routes/channels.php`, `resources/js/echo.js` |
| Frontend | Blade template, Tailwind CSS, Alpine.js, Vite | `resources/views`, `resources/js/app.js` |
| Testing | Pest | `tests/Feature/` |
| Deployment | Docker (Render) | `Dockerfile`, `docker-compose.yml` |

---

## 3. Laravel MVC & Request Lifecycle

Laravel **MVC (Model-View-Controller)** pattern use kore:

- **Model** — database table er PHP representation (`app/Models/`). Data
  fetch/save logic + table er sathe relation.
- **View** — user dekhe je page (`resources/views/`, Blade `.blade.php` file).
- **Controller** — request handle kore, model theke data neye view e pathay
  (`app/Http/Controllers/`).

**Ekta request er jatra (lifecycle):**

```
Browser (URL: /shop)
   ↓
public/index.php          ← sob request er entry point
   ↓
bootstrap/app.php         ← application configure (routing, middleware register)
   ↓
routes/web.php            ← URL ta kon controller er kon method e jabe
   ↓
Middleware                ← auth/role check (gate pass)
   ↓
Controller method         ← business logic
   ↓
Model (Eloquent)          ← database theke data
   ↓
View (Blade)              ← HTML baniye
   ↓
Browser e response
```

**Viva tip:** jodi sir jigges kore "request dhukle ki hoy" — upore er sequence ta bolo.

---

## 4. Folder Structure

| File/Folder | Kaj |
| --- | --- |
| `artisan` | Laravel er command-line tool (php artisan serve, migrate etc.) |
| `composer.json` | PHP dependency list (Laravel, Breeze, Reverb...) |
| `package.json` | JS dependency list (Vite, Tailwind, Alpine, Echo...) |
| `.env` | Local environment config (DB password, app key) — **git e jay na** |
| `.env.example` | .env er template |
| `bootstrap/app.php` | App configure: routing file gulo register, middleware alias, health check `/up` |
| `routes/web.php` | Sob web URL → controller mapping (project er "map") |
| `routes/auth.php` | Login/register/logout/reset-password route (Breeze) |
| `routes/channels.php` | WebSocket private channel authorization rule |
| `app/Models/` | Database table er Eloquent Model (User, Product, Order...) |
| `app/Http/Controllers/` | Request handler logic |
| `app/Http/Controllers/Admin/` | Admin er controller (Category, Moderation, ShopApplication review) |
| `app/Http/Controllers/Owner/` | Business owner er controller (Product, Inventory, Dashboard) |
| `app/Http/Controllers/Auth/` | Breeze er auth controller |
| `app/Http/Middleware/` | Custom middleware: `EnsureUserHasRole`, `EnsureShopApplicationApproved` |
| `app/Http/Requests/` | Form validation class (StoreProductRequest etc.) |
| `app/Services/CustomerRewardService.php` | Loyalty/voucher er business logic (service class) |
| `app/Events/ChatMessageSent.php` | Chat broadcast event |
| `app/Notifications/AdminModerationNotice.php` | Database notification (moderation er somoy) |
| `app/Console/Commands/` | Custom artisan command (SeedDemoDataset, CreateAdmin...) |
| `database/migrations/` | Table banano PHP file (database er version history) |
| `database/factories/` | Test data banano factory |
| `database/seeders/` | Demo data seeder |
| `resources/views/` | Blade template (page design) |
| `resources/js/app.js` | Frontend JS (Alpine, animation, Echo import) |
| `resources/js/echo.js` | WebSocket client config |
| `resources/css/app.css` | Tailwind CSS entry |
| `config/shopsync.php` | Custom config — bKash/Nagad number |
| `public/` | Sobaike dekhoya static file; `public/storage` = uploaded image link |
| `storage/app/` | Uploaded private file (shop application document) |
| `tests/Feature/` | Pest feature test |

> **Note:** project e kichu `.bak` file ache (purano backup) — egulo app er
> part na, Laravel chure na. Viva te confusion hole bolo "leftover backup file".

---

## 5. Database Design

### Table list (migrations theke)

| Table | Ki rakhe | Migration file |
| --- | --- | --- |
| `users` | name, email, password, **role**, email_verified_at | `0001_01_01_000000_create_users_table.php`, `2026_10_10_110104_add_role_to_users_table.php` |
| `categories` | parent_id (self-reference), name, slug, is_active | `2026_10_10_152539_create_categories_table.php` |
| `products` | user_id (owner), category_id, name, slug, price, stock, image_path, is_active, **admin_status**, **admin_reason**, **moderated_by**, **reward_points**, **surprise_voucher_enabled**, deleted_at (soft delete) | `..._create_products_table.php`, `..._add_moderation_fields_to_products_table.php` |
| `orders` | user_id (customer), order_number, customer_name, phone, shipping_address, total, status, payment_method, payment_status, payment_reference, voucher_code, voucher_discount_total | `2026_10_13_124706_create_orders_table.php`, `2026_10_17_150837_add_payment_and_loyalty_rewards.php` |
| `order_idems` (order items — spelling e typo ache, intentionally same rakha) | order_id, product_id, product_name, unit_price, quantity, subtotal, **status**, payment_status, payment_verified_by/at, **reward_points_per_unit**, **surprise_voucher_eligible**, **reward_category_id**, voucher_discount_amount, rewards_processed_at | `2026_10_13_124742_create_order_idems_table.php`, `2026_10_14_171718_add_status...`, `2026_10_17_150837...` |
| `inventory_transactions` | product_id, user_id, order_id, type (restock/adjustment/sale), quantity_before, quantity_change, quantity_after, reason | `2026_10_13_210812_...` |
| `shop_applications` | user_id, assigned_admin_id, shop_name, slug, shop_type, business info, address, legal_name, identity document path, status (pending/needs_information/approved/rejected), **shop_status** (active/suspended/closed), reviewed_by/at | `2026_10_16_102457_...`, `..._add_shop_moderation_fields...` |
| `shop_application_media` | shop_application_id, category (demo_photo/demo_video/product_proof), file_path, original_name, mime_type, file_size | `2026_10_16_102458_...` |
| `shop_application_reviews` | shop_application_id, actor_id, actor_role, action, comment — puro application er audit trail | `2026_10_16_182514_...` |
| `product_reviews` | product_id, user_id, rating (nullable 1-5), comment | `2026_10_17_112237_...` |
| `shop_conversations` | customer_id, shop_owner_id, product_id, product_name, last_message_at | `2026_10_17_132209_...` |
| `chat_messages` | shop_conversation_id, sender_id, sender_name, body, read_at | `2026_10_17_132210_...` |
| `vouchers` | Voucher template: name, discount_type (percent/fixed), discount_value, min_order_total, max_discount, points_cost, product_id, category_id, is_active, funding_source, expires_in_days | `2026_10_17_150837_add_payment_and_loyalty_rewards.php` |
| `customer_vouchers` | Customer er Wallet e thaka voucher: voucher_id, user_id, code (SS...), source (points/surprise), source_order_item_id, status (available/redeemed), redeemed_order_id, expires_at | same |
| `customer_point_transactions` | Points ledger: user_id, order_id, order_item_id, customer_voucher_id, type (earned/spent/reversed), points | same |
| `notifications` | Laravel er database notification (moderation notice) | `2026_10_16_214130_create_notifications_table.php` |
| `cache`, `jobs`, `sessions` | Laravel er framework table — session/cache/queue database driver e chole | `0001_01_01_000001/_000002_...` |

### Relation Map (konta kar sathe connected)

```
users (role: admin / business_owner / customer)
  ├── 1:1  shop_applications        (User::shopApplication — hasOne)
  ├── 1:N  orders                   (Order::user — belongsTo)
  ├── 1:N  products                 (Product::owner — belongsTo, user_id)
  ├── 1:N  shop_conversations       (customer ar shop_owner dikei)
  ├── 1:N  chat_messages            (sender)
  ├── 1:N  customer_vouchers, customer_point_transactions, product_reviews

categories
  ├── self   parent/children        (parent_id — sub-category support)
  ├── 1:N    products               (Product::category — belongsTo)
  ├── 1:N    vouchers               (category-scoped voucher)

products
  ├── belongsTo  users (owner), categories
  ├── 1:N        product_reviews, inventory_transactions, order_idems
  └── SoftDeletes (deleted_at) — delete korleo database e thake, restore kora jay

orders
  ├── belongsTo  users (customer)
  └── 1:N        order_idems (Order::items — HasMany)

order_idems (order er prottek line item)
  ├── belongsTo  orders, products
  └── ei table ei per-item status + payment + reward snapshot thake

shop_applications
  ├── belongsTo  users (applicant), users (assignedAdmin), users (reviewer)
  ├── 1:N        shop_application_media, shop_application_reviews

shop_conversations
  ├── belongsTo  users (customer), users (shopOwner), products
  └── 1:N        chat_messages, 1:1 lastMessage
```

**Snapshot pattern (important viva point):** `order_idems` e product er
**name, unit_price, reward points** copy kore rakha hoy — karon pore owner
product edit/delete korleo purano order er history change hobe na.
Eta `OrderIdem` model er `booted()` hook e kora:

`app/Models/OrderIdem.php`:
```php
static::creating(function (OrderIdem $item) {
    // Snapshot rewards so future product edits cannot alter an old order.
    $item->reward_points_per_unit = (int) $product->reward_points;
    $item->surprise_voucher_eligible = (bool) $product->surprise_voucher_enabled;
    $item->reward_category_id = $product->category_id;
});
```

---

## 6. Role & Security Layer

### Middleware ki?

Middleware = request ar controller er **majhkhone gate pass**. Request
controller e jaoar age middleware check kore — login kora ache kina, role
thik ache kina.

**Custom middleware 2 ta:**

**1. Role check** — `app/Http/Middleware/EnsureUserHasRole.php`
```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    abort_unless($request->user() && in_array($request->user()->role, $roles, true), 403);
    return $next($request);
}
```
Kaj: logged-in user er `role` ta allowed list e ache kina check kore, na
thakle **403 Forbidden**. Alias naam: `role` (routes e `middleware('role:admin')`
likhle eta chole).

**2. Shop approval check** — `app/Http/Middleware/EnsureShopApplicationApproved.php`
Kaj: business_owner er dashboard/product route e dhukar age check kore —
- application e kora nai → `owner.shop-application.create` page e pathay
- application pending/rejected → status page e pathay
- shop `suspended`/`closed` → block
- `approved` + `active` → pass

Application form/status page gulao dhukte pare (nahole owner application
i korte parbe na) — ei jonno 3 ta route whitelist kora ache.

**Alias registration** — `bootstrap/app.php`:
```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    'shop.approved' => \App\Http\Middleware\EnsureShopApplicationApproved::class,
]);
```

**Route group e kivabe use hoy** — `routes/web.php`:
- `Route::middleware('auth')` — sob logged-in route
- `middleware('role:customer')` — review, chat start, rewards
- `middleware(['role:business_owner', 'shop.approved'])->prefix('owner')` — owner panel
- `middleware('role:admin')->prefix('admin')` — admin panel

---

## 7. Feature-by-Feature Deep Dive

### 7.1 Authentication (Laravel Breeze)

**File path:**
- `routes/auth.php` — login/register/logout/password-reset route
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — login/logout
- `app/Http/Controllers/Auth/RegisteredUserController.php` — registration
- `resources/views/auth/login.blade.php`, `register.blade.php` — page design

**Kaj:** Breeze ekta official Laravel starter kit — login, registration,
password reset, email verification sob ready kore dey, **session-based** auth.

**Connection:** register form → `RegisteredUserController@store` → `users`
table e row (password **bcrypt hash** hoy — `User` model er
`'password' => 'hashed'` cast er karone) → session banay → login.

**Viva point:** password plain text e save hoy na — Laravel automatic
bcrypt hash kore, verify kore `Hash::check()` diye.

---

### 7.2 Role-based Dashboard Redirect

**File path:** `routes/web.php` (line ~27)

```php
Route::get('/dashboard', fn() => match (request()->user()->role) {
    'admin' => redirect()->route('admin.dashboard'),
    'business_owner' => redirect()->route('owner.dashboard'),
    default => redirect()->route('customer.dashboard'),
})->middleware('verified')->name('dashboard');
```

**Kaj:** ekta `/dashboard` URL, kintu user er role onujayi alada dashboard e
pathay. PHP 8 er **match expression** use kora hoyeche (switch er modern
version).

**Connection:**
- admin → `AdminController@dashboard` → `resources/views/dashboards/admin.blade.php`
- owner → `Owner\DashboardController@index` → `dashboards/owner.blade.php`
- customer → `Customer\DashboardController@index` → `dashboards/customer.blade.php`

**Owner dashboard** (`app/Http/Controllers/Owner/DashboardController.php`):
total product, total stock, today's sales, pending order count, low stock
(stock ≤ 5) list, recent orders — sob query `whereHas('items.product', ...)`
diye sudhu nijer product gulo ke count kore (multi-vendor logic!).

**Customer dashboard** (`app/Http/Controllers/Customer/DashboardController.php`):
featured products (rating onujayi), new arrivals, category-wise product,
**top shops list** (DB table join: `shop_applications` + `users` + `products`
— sudhu approved + active shop, ar tader active product ache), points balance.

---

### 7.3 Storefront (Product Browse & Search)

**File path:**
- `app/Http/Controllers/StorefrontController.php` — logic
- `resources/views/shop/index.blade.php` — list page
- `resources/views/shop/show.blade.php` — single product page
- `app/Models/Product.php` — `scopeAvailableToCustomers()` query scope

**Product dekhbe ke?** — sob theke important concept:

`app/Models/Product.php`:
```php
public function scopeAvailableToCustomers(Builder $query): Builder
{
    return $query
        ->withAvg([...], 'rating')       // average rating calculate
        ->withCount([...])               // koyjon rating diyechhe
        ->where('products.is_active', true)        // owner active korechhe
        ->where('products.admin_status', 'active') // admin ban kore nai
        ->where('products.stock', '>', 0)          // stock ache
        ->whereHas('owner.shopApplication', function ($shop) {
            $shop->where('status', 'approved')
                 ->where('shop_status', 'active'); // shop tai approved & active
        });
}
```

**Kaj:** customer ke sudhu sei product dekhay jeta — owner activate korechhe +
admin moderate kore active rakhechhe + stock ache + product er maliker er shop
approved. Eke bole **query scope** — `Product::query()->availableToCustomers()`
likhlei ei sob condition apply hoy. (Laravel scope convention: method name
`scopeXyz` hole call korte hoy `xyz`.)

**Search** (`StorefrontController@index`): URL query `?q=...` diye search —
product name, description, category name, owner name, shop name — sob kothay
`LIKE '%...%'` search kore. Category filter `?category=slug` diye. Result
`paginate(12)->withQueryString()` — page 1,2,3 e search query hold kore rake.

**Connection:** `routes/web.php` → `/shop` → `StorefrontController@index` →
`Product` + `Category` model → `shop/index.blade.php` view.

---

### 7.4 Product Review & Rating

**File path:**
- `app/Http/Controllers/ProductReviewController.php`
- `app/Models/ProductReview.php`
- route: `POST /shop/{product}/reviews` + `middleware(['role:customer', 'throttle:10,1'])`

**Rules (code theke):**
1. Sudhu **customer** review dite pare (admin/owner 403 pabe)
2. Prottek customer **prottek product e 1 tai** review — `updateOrCreate` use kore,
   abar dile update hoy
3. **Comment** required, **rating (1-5)** optional — kintu rating dite hole
   **verified purchase** lagbe: `hasPurchased()` method check kore je ei customer
   ei product ta order korechhe kina (`order_idems` + `orders` join kore,
   cancelled/refunded bad diye). Na korle error: "Order this product before rating it."
4. `throttle:10,1` = minute e max 10 request (spam protect)

**Average rating** product page e dekhay + storefront sorting e use hoy
(`withAvg` eager load).

---

### 7.5 Cart & Checkout

**File path:**
- `app/Http/Controllers/CartController.php`
- `resources/views/cart/index.blade.php`, `cart/checkout.blade.php`

**Cart ta database e na, session e save hoy!** Session e ekta array:
`[product_id => quantity]`.

**Cart methods:**
- `add` — product kina jay kina check (`isAvailableToCustomers()`), stock
  limit cross korle error, `session()->put('cart', $cart)`
- `update` — quantity change (stock check soho)
- `remove` — cart theke bad
- `index` — cart page; `purgeUnavailableCartProducts()` private method: jei
  product ekhon ar available na (stock sesh / suspended / deleted) sheta cart
  theke automatic remove kore dey + flash message dey

**Checkout — `placeOrder()` (sob cheye important method):**

```php
$validated = $request->validate([
    'customer_name' => ..., 'phone' => ..., 'shipping_address' => ...,
    'payment_method' => ['required', 'in:cod,bkash,nagad'],
    'payment_reference' => ['nullable', 'required_if:payment_method,bkash,nagad', ...],
    'voucher_code' => ['nullable', ...],
]);
```

Step by step ki hoy:
1. **Validation** — payment bkash/nagad hole `payment_reference` (transaction ID) required
2. **Voucher pre-check** — invalid voucher dile order e na korei error (`previewVoucherForCart`)
3. **`DB::transaction(...)`** — sob kichu **ekta database transaction e**:
   - `Product::...->lockForUpdate()->get()` — product row **lock** kore, jate
     duita customer ek somoy kinte gele stock over-sell na hoy (**race condition
     prevent** — viva te bolar moto point!)
   - Abar stock check — majhe stock sesh hoye gele `ValidationException`
   - bKash/Nagad number config e ache kina check (`config('shopsync.payment_numbers...')`)
   - `Order::create(...)` — order_number: `'ORD-' . Str::upper(Str::random(12))`
   - Prottek cart item er jonno `OrderIdem::create(...)` (name/price snapshot)
   - `$product->decrement('stock', $quantity)` — stock komay
   - `InventoryTransaction::create(...)` — type `sale`, before/change/after stock record
   - Voucher thakle `CustomerRewardService::applyVoucherToOrder()` — discount apply
4. Transaction **pass korle** cart session clear, success message soho order
   page e redirect; **fail korle** sob kichu rollback — kono half-order thake na

**Payment method:**
- `cod` — cash on delivery, payment_status `pending`, delivery er pore owner pay verify kore
- `bkash`/`nagad` — customer number e taka pathay + transaction ID dey; owner
  match kore `paid/failed` mark kore

bKash/Nagad number config theke ashe: `config/shopsync.php` ← `.env` er
`SHOPSYNC_BKASH_NUMBER` / `SHOPSYNC_NAGAD_NUMBER`.

---

### 7.6 Order Lifecycle (per-item status)

**File path:**
- `app/Http/Controllers/OderController.php` (class name e typo ache — "Oder",
  table `order_idems`, model `OrderIdem` — consistent, tai kaj kore)
- `resources/views/order/index.blade.php`, `show.blade.php`, `manage.blade.php`

**Customer side:**
- `index` — nijer sob order (`where('user_id', Auth::id())`), latest first, paginated
- `show` — ekta order er detail; **security:** `abort_unless($order->user_id === Auth::id(), 403)`
  — onyer order dekhte parbe na (IDOR prevent)

**Owner side (`manage`):** owner er shop er **product** j order e ache sudhu
seta dekhe — `whereHas('items.product', fn($q) => $q->where('user_id', $userId))`.
Multi-vendor er karone ek order e anek shop er product thakte pare, tai
per-item tracking lage.

**Status state machine (`updateStatus`):**
```php
$allowedTransitions = [
    'pending'    => ['processing', 'cancelled'],
    'processing' => ['shipped', 'cancelled'],
    'shipped'    => ['delivered'],
    'delivered'  => [],   // final
    'cancelled'  => [],   // final
];
```
Owner ja iccha status set korte parbe na — serial maintain hote hobe. Invalid
transition hole error. Item `delivered` hole →
`CustomerRewardService::processDeliveredItems($order)` call hoy (points earn!).

**Payment verify (`updatePaymentStatus`):**
- Owner prottek item er payment `paid`/`failed` mark kore
- Guard 1: bkash/nagad hole customer er deya `payment_reference` chara paid kora jabe na
- Guard 2: COD e paid mark korte hole age item **delivered** hote hobe
- Sob kaj `DB::transaction` + `lockForUpdate` e — order + item duitai lock
- Item paid hole check kore **order er sob active item paid kina** — sob paid
  hole order er `payment_status` o `paid` hoy → reward process trigger

---

### 7.7 Inventory Management (Audit Trail)

**File path:**
- `app/Http/Controllers/Owner/InventoryController.php`
- `app/Models/InventoryTransaction.php`
- `resources/views/owner/inventory/history.blade.php`

**Kaj:** owner stock restock (+) ba adjustment (−) korte pare, **reason** deite
hobe. Prottek change e `inventory_transactions` table e record thake:
`quantity_before`, `quantity_change`, `quantity_after` — mane puro stock er
**accounting ledger** er moto history.

```php
DB::transaction(function () use ($product, $validated) {
    $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
    ...
    $change = $validated['type'] === 'restock' ? $quantity : -$quantity;
    $after = $before + $change;
    if ($after < 0) throw ValidationException::withMessages([...]); // negative stock na
    $lockedProduct->update(['stock' => $after]);
    InventoryTransaction::create([...]);
});
```

Abar `lockForUpdate` — concurrent update safe. Checkout er somoy je `sale`
type inventory transaction create hoy (CartController theke) — sei history o
ei page e dekha jay. `history()` method e nijer product er sob transaction,
latest first, paginated.

---

### 7.8 Shop Application (Owner Onboarding)

**File path:**
- Owner side: `app/Http/Controllers/ShopApplicationController.php`,
  `resources/views/shop-application/create.blade.php`, `status.blade.php`
- Admin side: `app/Http/Controllers/Admin/ShopApplicationController.php`,
  `resources/views/admin/shop-applications/index.blade.php`, `show.blade.php`
- Models: `ShopApplication`, `ShopApplicationMedia`, `ShopApplicationReview`
- Middleware: `EnsureShopApplicationApproved` (upore dekha)

**Flow:**
1. `business_owner` register kore → application form fill kore (shop name,
   type, address, business details, **NID/passport photo + document upload**,
   demo product photo/video)
2. `store()` method: boro validation (image jpg/png/webp max 5MB, pdf max 10MB,
   terms accepted etc.), file gulo **`local` disk e** save hoy (=`storage/app/`
   — **public na!** identity document, tai web e directly accessible na)
3. `ShopApplication` create — `status: pending`, automatic **`assigned_admin_id`**
   set hoy (prothom admin ke assign hoy; resubmit korle same admin e thake)
4. Shop name er theke **unique slug** banay (`Str::slug()` + duplicate hole `-2`, `-3`)
5. `ShopApplicationReview` e audit record: `submitted`/`resubmitted`
6. Admin panel e assigned admin application dekhe → approve / reject
   (reason required) / request-information (comment required)
7. `needs_information` hole owner abar form edit kore resubmit korte pare
8. Approve hole owner dashboard/products manage korte pare

**Clever detail (bolar moto):** file upload + DB write ek `DB::transaction` e
hoy. Database fail korle already-stored file gulo delete kore dey (rollback
for file system), ar success hole purano replaced file delete kore — mane
**orphan file thake na**.

**Admin security:**
- Admin sudhu **nijer assigned** application dekhte pay (`findAssignedApplication()`
  — onno admin er ta 404)
- Already reviewed application abar review kora jay na (`ensureReviewable` — 409)
- Identity document **direct URL e open hoy na** — `document()` method diye
  authorized admin er through e serve hoy, `no-store` header soho

---

### 7.9 Admin Moderation + Notifications

**File path:**
- `app/Http/Controllers/Admin/ModerationController.php`
- `app/Notifications/AdminModerationNotice.php` (database notification)
- `app/Http/Controllers/UserNotificationController.php` (notification center)
- `resources/views/admin/moderation/index.blade.php`, `notifications/index.blade.php`

**Product moderation:**
| Action | Code ki kore | Owner ki pay |
| --- | --- | --- |
| Suspend | `admin_status = 'suspended'` + reason | Database notification |
| Warn | Sudhu notification | Database notification |
| Remove | `admin_status = 'removed'` + **soft delete** (`$product->delete()`) | Notification |
| Restore | `restore()` + `admin_status = 'active'` | Notification |

**Soft delete viva point:** `Product` model e `use SoftDeletes;` — delete
korle row actually delete hoy na, `deleted_at` set hoy. Customer ar dekhe na
(`availableToCustomers` e dekha jay na), kintu **order history intact thake**
ar admin `withTrashed()` / `onlyTrashed()` diye dekhte + restore korte pare.

**Shop moderation:** approved shop ke `suspend` / `close` / `reactivate` kora
jay (sudhu approved shop, reason required) — `shop_status` column change hoy,
owner notified. Suspend/closed shop er owner middleware diye block hoye jay
(section 6 dekho).

**Notification system:** Laravel er built-in **database notifications** —
`notifications` table. `AdminModerationNotice` er `via()` return kore
`['database']`, data `toArray()` e jay. User notification center e dekhe,
`markAsRead` / `markAllAsRead` korte pare (`UserNotificationController`).

---

### 7.10 Real-time Chat (Laravel Reverb — WebSocket)

Eita project er sob cheye interesting part — viva te ekdom clear rakhba.

**File path:**
- `app/Http/Controllers/ChatController.php`
- `app/Events/ChatMessageSent.php`
- `app/Models/ShopConversation.php`, `ChatMessage.php`
- `routes/channels.php` — channel authorization
- `resources/js/echo.js` — browser side WebSocket client
- `resources/views/chat/index.blade.php`, `chat/show.blade.php`

**Concept:**
- **WebSocket** = browser ar server er majhe **persistent connection** —
  server data pathale page refresh na korei instant dekhay. (Normal HTTP e
  browser request kore, server par e thake.)
- **Laravel Reverb** = Laravel er nijer WebSocket **server** (PHP e toiri).
- **Laravel Echo** = browser side JS library je WebSocket subscribe kore
  event sune.

**Flow:**
1. Customer product page e "Chat with seller" chaple → `ChatController@start` →
   `ShopConversation::firstOrCreate([...])` — (customer, owner, product) combination er
   ekta conversation thakle seta use hoy, nahole notun
2. Chat page (`show`) — page kholar somoy **onyer patha message gulo auto
   `read_at = now()`** set hoy (read receipt!)
3. Message pathano: `storeMessage()` — DB transaction e message create +
   conversation er `last_message_at` update, tarpor:
   ```php
   broadcast(new ChatMessageSent($message))->toOthers();
   ```
4. Event class (`ChatMessageSent`) — **3 ta important method:**
   - `broadcastOn()` → `new PrivateChannel('chat.conversation.' . $id)` —
     **private channel**, mane authenticated subscription lagbe
   - `broadcastAs()` → `'message.sent'` — event name
   - `broadcastWith()` → kon data jabe (id, sender_name, body, time)
   - `implements ShouldBroadcastNow` — **queue chara ekhon i** broadcast
     (chat er jonno delay tolerable na)
5. **Channel authorization** — `routes/channels.php`:
   ```php
   Broadcast::channel('chat.conversation.{conversationId}', function (User $user, int $conversationId): bool {
       // sudhu sei conversation er customer ba shop owner subscribe korte parbe
   });
   ```
   Onno keu ei conversation er message sunte parbe na — **security**
6. Browser e `echo.js`:
   ```js
   window.Echo = new Echo({ broadcaster: 'reverb', key: ..., ... });
   // chat page e: Echo.private('chat.conversation.'+id).listen('.message.sent', ...)
   ```
   Message asle JS DOM e liye add kore dey — refresh lagbe na
7. `->toOthers()` — je pathacche tar nijer browser e event jay na
   (se already UI te add kore feleche), sudhu onno jonno jay

**Fallback:** `.env` e `BROADCAST_CONNECTION=log` thakle broadcast log e jay —
chat refresh korle message dekha jay (Reverb server chalu na thakleo app
bhashbe na).

**Route throttle:** message pathano `throttle:30,1` — minute e max 30 message.

---

### 7.11 Loyalty Points & Voucher System

Project er sob cheye boro business logic — ekta dedicated **Service class** e.

**File path:**
- `app/Services/CustomerRewardService.php` ← core logic
- `app/Http/Controllers/CustomerRewardController.php` — rewards page + voucher buy
- Models: `Voucher`, `CustomerVoucher`, `CustomerPointTransaction`
- `app/Models/Product.php` er `booted()` hook — points assign
- `app/Models/OrderIdem.php` er `booted()` hook — snapshot

**3 table er design (bujha joruri):**

| Table | Kaj |
| --- | --- |
| `vouchers` | Voucher er **template** — 5% discount, min order 500, max discount 100tk, kon category/product e valid |
| `customer_vouchers` | Customer er **wallet** — unique code (SS + random 10 char), status available/redeemed, expire date |
| `customer_point_transactions` | Points er **ledger** (bank statement er moto) — earned/spent/reversed, kono row delete hoy na |

**Points kothey ashe:** Product **create** hobar somoy server automatic point
thik kore (price onujayi):

`app/Models/Product.php`:
```php
static::creating(function (Product $product) {
    [$min, $max] = match (true) {
        $price < 100 => [1, 3],
        $price < 500 => [3, 8],
        $price < 1500 => [8, 15],
        $price < 5000 => [15, 30],
        default => [30, 50],
    };
    // Generated by the server, never trusted from form input.
    $product->reward_points = random_int($min, $max);
    $product->surprise_voucher_enabled = random_int(1, 100) <= 12; // 12% luck
});
```
**Viva point:** point user/form theke ashe na — **server generated**, tahole
keu form hack kore beshi point set korte parbe na.

**Points balance calculation** (ledger theke):
```php
"COALESCE(SUM(CASE
    WHEN type = 'earned' THEN points
    WHEN type IN ('spent', 'reversed') THEN -points
    ELSE 0 END), 0) AS balance"
```
earned jog, spent/reversed biyog — SQL CASE expression.

**Kivabe point earn hoy:**
1. Order er item `delivered` + order er `payment_status = paid` hole
2. `processDeliveredItems()` → prottek item er jonno `processItem()`:
   - `rewards_processed_at` check — **ekbar i process hoy** (double reward block)
   - points = `reward_points_per_unit × quantity` → `CustomerPointTransaction` (earned)
   - Item jodi **surprise eligible** hoy (12% chance) → automatic **surprise
     voucher** wallet e (5% discount, 30 din, category-scoped) — dedupe check
     `source_order_item_id` diye

**Voucher buy (points kate):** `buyVoucher()` — 100 point kate, tar category er
ekta 5% voucher wallet e ashe, `spent` transaction record hoy. Sob `DB::transaction`
e, user row `lockForUpdate` kore (ek sathe 2 bar kate double-spend hobe na).

**Checkout e voucher apply (`applyVoucherToOrder`):**
1. Voucher code → customer er `available` + `not expired` voucher khoje,
   `lockForUpdate` soho
2. **Eligible line ber kore** — voucher jodi category-scoped hoy, sudhu sei
   category er item e discount pore
3. Discount = percent/fixed, `max_discount` + eligible subtotal diye **capped**
4. Discount ta **item gulo te proportional vabe vag kore** rake
   (`voucher_discount_amount`) — pore item cancel korle hisab thik thake
5. Order er `total` komay, voucher `redeemed` mark kore, `redeemed_order_id` set

**Preview before order:** `previewVoucherForCart()` — ek same calculation,
kintu **kono data likhe na** — order banano er agei invalid voucher catch kore,
jate invalid code dile "order toiri holo kintu voucher mile nai" type bug na hoy.

---

### 7.12 Reports

**File path:**
- `app/Http/Controllers/ReportsController.php`
- `resources/views/reports/owner.blade.php`, `reports/admin.blade.php`
- Route: `GET /owner/reports` (role:business_owner + shop.approved), `GET /admin/reports` (role:admin)

**Kaj:** date-range filter (`from`/`to`) soho sales report. Owner report sudhu
nijer product er `order_idems` gulo niye hisab kore (`whereHas('product', ...)`):
total sales (subtotal sum), sold quantity, order count. Admin report puro
platform er. Sales status = `processing, shipped, delivered` (pending/cancelled
bad) — mane **real sales** count hoy.

---

### 7.13 Demo Dataset Seeder (Custom Artisan Command)

**File path:**
- `app/Console/Commands/SeedDemoDataset.php` → `php artisan shopsync:seed-demo`
- `app/Console/Commands/ImportDummyJsonDataset.php` → `shopsync:import-dataset`
- `app/Console/Commands/ValidateDemoDataset.php` → `shopsync:validate-dataset`
- `app/Console/Commands/CreateAdmin.php` → `app:create-admin`

**Kaj:** Demo presentation er jonno puro realistic data automatically toiri
kore — 4 ta business owner (shop), 1 admin, 24 customer, category, ~100 product
(image soho, DummyJSON API theke), ~300 order, inventory history, review,
chat, points — sob. Password sobaike: `password`.

**Safety design (memory theke important):** command ta sudhu **reserved
namespace** er record touch kore — email `@shopsync.test` ending, `DUMMYJSON-*`
order number. **Real/user-made record kokhono modify/delete kore na.**
Options: `--force` (confirm skip), `--skip-images` (offline), `--dry-run`.

**Viva point:** Artisan command = Laravel er CLI tool. Custom command e
`$signature`, `handle()` method thake. Database seeder ar demo command er
difference: Seeder test/sob general data, ai command realistic showcase data.

---

## 8. Frontend

**File path:**
- `resources/views/layouts/app.blade.php` — main layout (navbar, footer, sob
  page ei layout extend kore)
- `resources/views/layouts/guest.blade.php` — login/register er layout
- `resources/views/dashboards/*.blade.php` — role-wise dashboard
- `resources/views/components/` — resuable Blade component (button, input, modal...)
- `resources/js/app.js` — Alpine.js register + custom animation (cursor
  spotlight, scroll reveal, product card 3D tilt)
- `resources/js/echo.js` — WebSocket client
- `resources/css/app.css` — Tailwind CSS
- `vite.config.js` — Vite bundler config

**Kivabe kaj kore:**
- **Blade** = Laravel er template engine. `{{ $variable }}` likhle automatic
  **XSS-safe escape** hoy. `@foreach`, `@if`, `@extends`, `@section` directive
- **Tailwind CSS** = utility-first CSS framework — HTML e class likhe design
  (`class="flex items-center gap-4 p-6"`)
- **Alpine.js** = halka JS framework — dropdown, modal, tab without page reload
- **Vite** = modern bundler — `npm run dev` (development, hot reload),
  `npm run build` (production, minified file `public/build/` e toiri kore)
- Blade e `@vite('resources/js/app.js')` diye asset load hoy
- ** laravel-echo + pusher-js** — chat er jonno (7.10 dekho)

**Build output commit hoy na** — production server e `npm run build` chalate hoy.

---

## 9. Config & Environment

**`.env` file ki?** — environment-specific config: database credential, app
key, mail, third-party key. **Git e push hoy na** (`.gitignore` e ache) —
karon ete secret thake. Prottek machine e alada `.env` thake;
`.env.example` template hisebe thake.

**Important variables ei project e:**

| Variable | Kaj |
| --- | --- |
| `APP_KEY` | Encryption key (session, password reset token encrypt kore) — `php artisan key:generate` diye banay |
| `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`... | Database config (`mysql` ba `sqlite`) |
| `SESSION_DRIVER=database` | Session database e save hoy (`sessions` table) |
| `QUEUE_CONNECTION=database` | Job queue database e |
| `BROADCAST_CONNECTION` | `log` (default, chat fallback) ba `reverb` (real-time chat on) |
| `REVERB_*`, `VITE_REVERB_*` | Reverb server credential — VITE_ gulo **frontend build er somoy** JS e dhukhe |
| `SHOPSYNC_BKASH_NUMBER`, `SHOPSYNC_NAGAD_NUMBER` | Checkout page e je payment number dekhay (`config/shopsync.php` theke pore) |

**Custom config:** `config/shopsync.php` — `env()` helper diye `.env` theke
value neye, code e `config('shopsync.payment_numbers.bkash')` diye pore.
(Straight `env()` code e use kora bad — config cache hole null hoy; tai
config file diye access kora best practice.)

---

## 10. Order Place korle Full Flow

Viva classic: **"Customer checkout e click korle ki ki hoy?"**

```
1. Browser: POST /checkout (form data: name, phone, address, payment_method,
   payment_reference, voucher_code) + session cookie + CSRF token
2. routes/web.php → CartController@placeOrder (middleware: auth)
3. Validation fail → back with errors
4. Voucher code thakle → CustomerRewardService@previewVoucherForCart (read-only check)
5. DB::transaction START
   5a. Product rows lockForUpdate (race-safe)
   5b. Abar stock availability check
   5c. Order row create (status: pending, order_number: ORD-XXXXXXXXXXXX)
   5d. Prottek item: OrderIdem create (price/name/reward snapshot)
   5e. Product stock decrement
   5f. InventoryTransaction create (type: sale, before/change/after)
   5g. Voucher thakle: applyVoucherToOrder (discount allocate, voucher redeemed)
6. DB::transaction COMMIT (kono step fail → sob ROLLBACK)
7. Cart session forget
8. Redirect: GET /my-orders/{order} → order.show view + success flash
```

**Ar pore owner er kaj:**
```
Owner: GET /owner/manage/orders → nijer product er order dekhe
   → PATCH item status: pending → processing → shipped → delivered
   → PATCH payment: bkash reference match → paid
   → delivered + paid hole → CustomerRewardService → customer point + surprise voucher
Customer: notification + points + voucher, /customer/rewards e dekhe
```

---

## 11. Security Best Practices

Ei gulo viva te bolle impression pore:

| Feature | Kothay | Ki problem solve kore |
| --- | --- | --- |
| **Password hashing** (bcrypt) | `User` model cast `'password' => 'hashed'` | Plain text password database leak |
| **CSRF protection** | Laravel built-in, Blade e `@csrf` | Cross-site request forgery |
| **Mass assignment protection** | Model e `$fillable` list | Iccha kore/haate extra field inject |
| **XSS protection** | Blade `{{ }}` auto-escape | Malicious `<script>` inject |
| **Authorization (403)** | Middleware + `abort_unless(owner check)` | IDOR — onyer order/product edit |
| **404 vs 403** | `abort_unless(..., 404)` ba `403` | Onno user er resource exist kora confirm kora jay na |
| **Private file storage** | Application document `local` disk e | Public URL diye identity churi |
| **Race condition handling** | `DB::transaction` + `lockForUpdate` | Ek somoy duita order e over-sell, double point |
| **Rate limiting** | `throttle:10,1` (review), `throttle:30,1` (chat) | Spam/brute force |
| **Server-generated values** | reward_points, order_number, slug | Client-side tampering |
| **Soft delete** | Product | Accidental data loss, order history corruption |
| **Audit trail** | `ShopApplicationReview`, `InventoryTransaction` | Ke ki korechhe seta na thaka |
| **Validation layer** | `$request->validate([...])`, FormRequest class | Malformed/malicious input |
| **Private broadcast channel** | `routes/channels.php` authorization | Onno conversation er message pora |

---

## 12. Common Viva Questions & Answers

**Q: Laravel ki? Keu ken Laravel use koro?**
A: PHP er sobcheye popular web framework — MVC structure, built-in auth,
ORM (Eloquent), migration, routing agei ready. Development fast hoy, security
(CSRF/XSS) built-in.

**Q: Migration ki?**
A: Database table er schema er **PHP version control**. `php artisan migrate`
chalale table toiri hoy; team er sobaike same schema pay; history track hoy
(`migrations` table).

**Q: Eloquent ki? Kon kon relation use korecho?**
A: Laravel er ORM — PHP class diye database query. Amra use korechi:
`hasMany` (Order→items, Product→reviews), `belongsTo` (Product→User/Category),
`hasOne` (User→shopApplication), `whereHas` (relation filter), eager loading
(`with`, `withCount`, `withAvg`) N+1 problem solve korte.

**Q: Middleware ki? Tomar nijer middleware ki ache?**
A: Request filter layer. 2 ta banaiyechi — `EnsureUserHasRole` (role check,
403) ar `EnsureShopApplicationApproved` (owner er shop approved kina check).
`bootstrap/app.php` e alias register kora, `routes/web.php` e use kora.

**Q: Session cart vs database cart — keno session?**
A: Cart ekta temporary thing — login session er sathe thake, order na hole
automatic muche jay. Database cart lagto jodi guest user der cart o
rakhate hoto cross-device. (Amra database session use kori — `sessions` table —
tai cart o scalable.)

**Q: `lockForUpdate()` keno use korecho?**
A: Concurrent request er **race condition** prevent korte. Example: product
stock 1, duita customer ek sathe checkout dilo — lock na thakle dujon e e
order successful hoto, stock -1 hoto. `lockForUpdate` (SELECT ... FOR UPDATE)
transaction er moddhe row lock kore dey.

**Q: Soft delete ki? Keno?**
A: `deleted_at` timestamp set hoy, row thake. Product remove korleo purano
order er history/report intact thake, admin restore o korte pare.
`$product->delete()`, `withTrashed()`, `onlyTrashed()`, `restore()`.

**Q: Event & Broadcasting explain koro.**
A: Chat message save howar pore `ChatMessageSent` event fire hoy. Event ta
`ShouldBroadcastNow` implement kore — Reverb WebSocket server e **private
channel** e payload push kore. Browser e Laravel Echo subscribe kore event
sune, JS diye message UI te add kore. Channel authorization
`routes/channels.php` e — sudhu conversation er dui payer subscribe allowed.

**Q: ShouldBroadcastNow vs ShouldBroadcast?**
A: `ShouldBroadcast` queue e jay (background worker lagbe); `Now` version
**immediately** broadcast kore — real-time chat e delay unacceptable, tai Now.

**Q: Query Scope ki?**
A: Model e reusable query constraint — `scopeAvailableToCustomers()` banaiyechi.
Call: `Product::query()->availableToCustomers()`. Sob jaygay same 5 ta condition
bar bar likha lagto na.

**Q: Service class keno? Controller e kortam na kene?**
A: `CustomerRewardService` ekta dedicated business logic class — voucher/point
logic **3 jaygay** use hoy (checkout, order delivery, rewards page). Controller
e rakhle code duplicate hoto + controller fatiye jeto. Thin controller,
fat service = clean architecture.

**Q: `booted()` / model hook ki?**
A: Model er lifecycle event (creating/created/updated...). `Order Item`
create howar somoy automatic product theke reward snapshot ney; `Product`
create hole automatic reward_points assign hoy.

**Q: N+1 problem ki, solve koro kivabe?**
A: Loop e prottek row er jonno alada query chole — performance killer. Solve:
eager loading — `with('owner', 'category')`, `withCount('reviews')`,
`withAvg('reviews', 'rating')` — ek/sat koyekta query tei sob data.

**Q: `@csrf` ki?**
A: Blade directive — hidden token generate kore POST form e. Laravel prottek
POST request e token match check kore — cross-site forged request block.

**Q: `.env` file e ki thake, git e keno jay na?**
A: Environment secret (DB password, APP_KEY). Git e gele secret leak +
machine-specific config conflict. `.env.example` template hisebe commit hoy.

**Q: `php artisan serve` ki kore?**
A: PHP er built-in development server chalu kore (`http://127.0.0.1:8000`).
Production e na — production e Nginx + PHP-FPM (Docker image e o tai ache).

**Q: Testing ki diye korecho? Ekta test example bolo.**
A: Pest (PHP er modern test framework). `tests/Feature/CheckoutVoucherTest.php`
e checkout er somoy voucher apply logic test kora hoy — database interaction
soho puro feature. `RateLimitingTest.php` e throttle test. Run:
`php artisan test`.

**Q: Project e multi-vendor mane ki? Taki handle koro kivabe?**
A: Ek order e anek shop er product thakte pare. Tai order er **level** e na,
**item level** e status/payment track kori (`order_idems`), owner sudhu nijer
product er item dekhe (`whereHas('items.product', user_id)`), report/dashboards
o item-level e hisab hoy.

**Q: Kono page e "Page Expired" (419) asle ki korbа?**
A: CSRF token expire/mismatch — form e `@csrf` ache kina, session clear kina
check korte hobe.

**Q: `Str::random()` diye order number — collision hole ki hobe?**
A: 12 character alphanumeric random — collision chance khub kom; stronger
design hoto `orders` table e `order_number` unique index + retry logic.

**Q: Database ki ki driver support kore?**
A: MySQL/MariaDB (main), SQLite (quick local/test), Postgres etc. — sudhu
`.env` change. Ai feature er karonei demo/demo setup e SQLite onek sohoje
cholе.

---

## 13. Demo Login Credentials

`php artisan shopsync:seed-demo` chalano thakle (password sobai: `password`):

| Role | Email |
| --- | --- |
| Admin | `admin@shopsync.test` |
| Business Owner (electronics) | `dataset.owner@shopsync.test` |
| Business Owner (fashion) | `owner.fashion@shopsync.test` |
| Business Owner (home) | `owner.home@shopsync.test` |
| Business Owner (beauty) | `owner.beauty@shopsync.test` |
| Customer (order history soho) | `customer01@shopsync.test` … `customer22@shopsync.test` |
| Customer (order nai — nijei order korte parba) | `customer23@shopsync.test` |

**Demo flow suggest (viva/demo er somoy):**
1. `admin@shopsync.test` → admin dashboard, shop application approve koro,
   moderation dekhao
2. `customer23@shopsync.test` → product kino (COD), order track koro
3. `dataset.owner@shopsync.test` → order er item `pending → processing →
   shipped → delivered` koro, payment verify, stock adjust
4. Customer abar dhuko → points + notification dekho, rewards page e voucher kino
5. Customer → product page e "Chat with seller" → owner e chat reply (real-time)

---

*File ta project root e ache — git e commit korleo somossa nei, eta
documentation. Best of luck for the viva! 🎓*
