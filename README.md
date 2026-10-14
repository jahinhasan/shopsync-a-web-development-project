## Demo Credentials

Use the following credentials to explore the ShopSync application. **All demo accounts use the password `password`.**

### 1. Admin Account

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@shopsync.test` | `password` |

### 2. Business Owner Accounts

| Store | Email | Password |
|---|---|---|
| TechNova Electronics | `dataset.owner@shopsync.test` | `password` |
| Dhaka Style House | `owner.fashion@shopsync.test` | `password` |
| GreenHome Living | `owner.home@shopsync.test` | `password` |
| GlowCare Beauty | `owner.beauty@shopsync.test` | `password` |

**Store Categories:**

- **TechNova Electronics:** Phones, laptops, tablets, accessories, motorcycles, and vehicles.
- **Dhaka Style House:** Clothing, shoes, watches, bags, jewellery, and sunglasses.
- **GreenHome Living:** Furniture, home decor, kitchen products, and groceries.
- **GlowCare Beauty:** Beauty products, fragrance, skincare, and sports accessories.

### 3. Customer Accounts

All customer accounts use the password `password`.

| Customer Group | Email Range | Password | Notes |
|---|---|---|---|
| Customers with orders | `customer01@shopsync.test` – `customer22@shopsync.test` | `password` | Includes existing order history |
| Customers without orders | `customer23@shopsync.test` | `password` | Edge case: no order history |
| Customers without orders | `customer24@shopsync.test` | `password` | Edge case: no order history |

### Important Notes

- These are **demo credentials** intended for local development, testing, and demonstration purposes.
- Ensure the demo database has been seeded before attempting to log in.
- Customer accounts `customer01` through `customer22` are intended to demonstrate customer order history and related functionality.
- Customer accounts `customer23` and `customer24` are provided for testing scenarios where a customer has no orders.
- Do not use these credentials in a production environment. Change or remove demo accounts before deploying the application publicly.
