# ShopSync

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Laravel_Reverb-WebSockets-FF2D20?style=flat-square" alt="Laravel Reverb">
  <img src="https://img.shields.io/badge/Tested_with-Pest-23D0A7?style=flat-square" alt="Pest">
</p>

<p align="center"><strong>ShopSync — a multi-vendor e-commerce marketplace with shop onboarding, moderation, real-time chat, and a loyalty &amp; rewards program.</strong></p>

<!-- 📸 HERO SCREENSHOT: replace the line below with ![ShopSync Home](docs/screenshots/home.png) -->

> 📸 **Hero screenshot placeholder** — replace this block with `![ShopSync](docs/screenshots/home.png)`

---

## 📖 About

ShopSync is a multi-vendor online marketplace where customers browse products from independent shops, business owners run their storefront through an approved application process, and administrators moderate the entire platform. It includes a complete order lifecycle with per-item status tracking, inventory auditing, mobile-banking payments (bKash / Nagad) and cash on delivery, real-time buyer–seller chat, and a points-based loyalty program with surprise vouchers.

## ✨ Features

### 🛍️ For Customers

- **Storefront** — browse products with search (product, category, seller, shop name), category filtering, and pagination
- **Ratings & reviews** — one review per customer per product; ratings require a verified purchase
- **Cart & checkout** — session cart with live stock validation, Cash on Delivery, bKash, and Nagad
- **Vouchers** — apply loyalty vouchers at checkout, scoped to eligible products/categories
- **Order tracking** — order history and per-item status (pending → processing → shipped → delivered)
- **Real-time chat** — talk to shop owners about a product over WebSockets (Laravel Reverb)
- **Loyalty rewards** — earn points on delivered orders, redeem vouchers, receive surprise reward vouchers
- **In-app notifications** — a notification center with mark-as-read controls

### 🏪 For Shop Owners

- **Dashboard** — sales overview and shop performance
- **Product management** — full CRUD with images, categories, pricing, and stock
- **Inventory management** — stock adjustments with a complete audit trail (before / change / after every movement)
- **Order management** — update item statuses and verify mobile-banking payment references
- **Reports** — sales reporting for their shop
- **Shop onboarding** — apply with business details, identity documents, and media; track application status

### 🛡️ For Admins

- **Shop application review** — approve, reject, or request more information, with assigned reviewers
- **Platform moderation** — suspend, warn, remove, or restore products; activate or suspend shops
- **Category management** — manage storefront categories
- **Platform-wide reports** — marketplace-level reporting
- **Moderation notifications** — owners are notified of moderation actions

## 📸 Screenshots

<!-- 📸 Each block below is a placeholder. To add a screenshot, replace the
     "placeholder" line with the markdown image, e.g.:
     ![Customer Dashboard](docs/screenshots/customer-dashboard.png)
     Recommended location: docs/screenshots/ -->

### Customer Dashboard

> 📸 **Placeholder** — `![Customer Dashboard](docs/screenshots/customer-dashboard.png)`

### Storefront & Product Details

> 📸 **Placeholder** — `![Storefront](docs/screenshots/storefront.png)`

### Cart & Checkout

> 📸 **Placeholder** — `![Checkout](docs/screenshots/checkout.png)`

### Customer Orders & Order Details

> 📸 **Placeholder** — `![My Orders](docs/screenshots/my-orders.png)`

### Shop Owner Dashboard & Product Management

> 📸 **Placeholder** — `![Owner Dashboard](docs/screenshots/owner-dashboard.png)`

### Inventory & Order Management

> 📸 **Placeholder** — `![Order Management](docs/screenshots/order-management.png)`

### Shop Application & Status

> 📸 **Placeholder** — `![Shop Application](docs/screenshots/shop-application.png)`

### Admin Dashboard & Moderation

> 📸 **Placeholder** — `![Admin Moderation](docs/screenshots/admin-moderation.png)`

### Real-time Chat

> 📸 **Placeholder** — `![Chat](docs/screenshots/chat.png)`

### Loyalty Points & Vouchers

> 📸 **Placeholder** — `![Rewards](docs/screenshots/rewards.png)`

## 🧰 Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ |
| Database | MySQL (SQLite supported for testing) |
| Frontend | Blade templates, Tailwind CSS, Alpine.js, Vite |
| Authentication | Laravel Breeze (login, registration, password reset) |
| Real-time | Laravel Reverb (WebSockets) + Laravel Echo |
| Testing | Pest |

## 🚀 Installation

### Requirements

- PHP 8.3 or higher with the usual Laravel extensions
- Composer
- Node.js 20+ and npm
- MySQL (or SQLite for a quick local spin)

### Steps

1. **Clone the repository**

    ```bash
    git clone https://github.com/jahinhasan/shopsync.git
    cd shopsync
    ```

2. **Install dependencies**

    ```bash
    composer install
    npm install
    ```

3. **Configure the environment**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    Then set your database credentials in `.env`:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=shopsync
    DB_USERNAME=root
    DB_PASSWORD=
    ```

4. **Run the migrations**

    ```bash
    php artisan migrate
    ```

5. **Seed the demo dataset** (accounts, categories, products, images, orders, inventory history)

    ```bash
    php artisan shopsync:seed-demo
    ```

    Or create a single administrator account instead:

    ```bash
    php artisan app:create-admin
    ```

6. **Build the frontend assets**

    ```bash
    npm run build
    ```

7. **Run the application**

    ```bash
    php artisan serve
    ```

### Enabling real-time chat

Chat runs over WebSockets via [Laravel Reverb](https://laravel.com/docs/reverb). Update `.env` with your Reverb credentials:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Then rebuild the assets and start the Reverb server in a second terminal:

```bash
npm run build
php artisan reverb:start
```

## 🔑 Demo Credentials

All demo accounts use the password `password`. Run `php artisan shopsync:seed-demo` first.

| Role | Email |
| --- | --- |
| Administrator | `admin@shopsync.test` |
| Business Owner | `dataset.owner@shopsync.test` |
| Business Owner | `owner.fashion@shopsync.test` |
| Business Owner | `owner.home@shopsync.test` |
| Business Owner | `owner.beauty@shopsync.test` |
| Customer (with order history) | `customer01@shopsync.test` … `customer22@shopsync.test` |
| Customer (no orders) | `customer23@shopsync.test`, `customer24@shopsync.test` |

> ⚠️ These credentials are for local development and demos only. Remove the demo accounts before deploying publicly.

## ⚙️ Environment Variables

| Variable | Purpose |
| --- | --- |
| `SHOPSYNC_BKASH_NUMBER` | The bKash number shown to customers at checkout |
| `SHOPSYNC_NAGAD_NUMBER` | The Nagad number shown to customers at checkout |
| `BROADCAST_CONNECTION` | Set to `reverb` to enable real-time chat |
| `REVERB_*` / `VITE_REVERB_*` | Reverb server credentials (see above) |

## 🛠️ Artisan Commands

| Command | Description |
| --- | --- |
| `php artisan shopsync:seed-demo` | Import the full demo dataset (accounts, categories, products, images, orders, inventory history) |
| `php artisan shopsync:import-dataset` | Import products, categories, users and carts from DummyJSON |
| `php artisan shopsync:validate-dataset` | Validate the demo dataset integrity (read-only) |
| `php artisan app:create-admin` | Create an administrator account |

## 🧪 Testing

```bash
php artisan test --compact
```

Or run Pest directly:

```bash
vendor/bin/pest
```

## 📄 License

<!-- TODO: choose a license (e.g. MIT) and add a LICENSE file -->

> **Placeholder** — license not selected yet.
