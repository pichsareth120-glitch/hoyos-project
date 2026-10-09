# HOYOS: Diagram Naming & Entity Reconciliation Map

> **Purpose:** This reference map bridges the legacy draw.io entity names from `WEBFinalPresentation.drawio` (across all 8 tabs: *Customer Management*, *Product + Stock Management*, *Site Management*, *Order + Payment Management*, *HOYOS ERD*, *Report*, *Loyalty*, and *Main Module*) to the final PostgreSQL / Laravel 11 database schema names.  
> **Instruction for Diagram Redraw:** Use the **Final Schema Name** column when redrawing all entities and relations in draw.io. If marked as **"removed"**, delete the entity box from the ERD and consult the architectural reason provided.

---

## 1. Entity & Table Reconciliation Map

| # | Old draw.io Name | Final Schema Name | Action | Architectural Reason |
| :- | :--- | :--- | :--- | :--- |
| **1** | `Customer` | `users` (where `role = 'customer'`) | **Renamed & Unified** | Merged into a unified `users` table with a `role` column to avoid duplicate authentication tables and foreign key confusion. |
| **2** | `Admin/User` or `User` | `users` (where `role = 'admin'`) | **Renamed** | Renamed to plural `users` to follow Laravel conventions and avoid the reserved SQL keyword `user` in PostgreSQL. |
| **3** | `User_Roles` | *removed* | **Removed** | A separate join table is unnecessary overhead for a fixed 2-role system (`customer` \| `admin`); handled directly via `users.role`. |
| **4** | `Account_Status` (table) | `users.account_status` [PLANNED] | **Inlined as Column** | Avoids redundant table; customer state is managed directly as `users.account_status` (`active` \| `suspended`, default `active`), and administrative actions are logged in `admin_audit_logs`. |
| **5** | `Addresses` / `Address` | `addresses` | **Renamed** | Standardized to plural snake_case `addresses`; cascade deleted when the user account is purged. |
| **6** | `Product` | `products` | **Renamed** | Standardized to plural snake_case `products`; contains base catalog metadata, pricing, and SEO slugs. |
| **7** | `quantity_in_stock` (on Product) | *removed* | **Removed** | Stock is held exclusively per variant in `product_variants.stock_quantity`. Total product stock is computed dynamically (`SUM()`) to prevent data drift. |
| **8** | `Product_Variants` | `product_variants` | **Renamed** | Standardized to plural snake_case; holds specific apparel size (`XS`–`XXL`, `One size`), SKU, and individual variant stock. |
| **9** | `Categories` | `categories` | **Renamed** | Standardized to plural snake_case `categories`. |
| **10** | `parent_category_id` (on Categories) | *removed* | **Removed** | HOYOS uses a flat category taxonomy (Y2K, Goth, Streetwear); hierarchical tree depth is not required. |
| **11** | `Product_Images` / `Product Image` | `product_images` | **Renamed** | Standardized to plural snake_case; supports multi-image gallery with `is_primary` flag, `credit`, and `source_url`. |
| **12** | `Stock_Adjustments` | `stock_adjustments` | **Planned Addition** | Retained as a planned table to maintain an immutable audit ledger of all inventory decrements, restocks, and expirations. |
| **13** | `Cart` | `carts` | **Renamed** | Standardized to plural snake_case; supports both anonymous session carts and authenticated user carts. |
| **14** | `Cart_Item` | `cart_items` | **Renamed** | Standardized to plural snake_case; joins `carts` to `product_variants` with compound unique constraint. |
| **15** | `Order` | `orders` | **Renamed** | Renamed to plural snake_case `orders` to follow Laravel conventions and avoid the reserved SQL keyword `"order"` in PostgreSQL. |
| **16** | `Order_Items` | `order_items` | **Renamed** | Standardized to plural snake_case; captures an immutable snapshot of product name, size, and purchase price. |
| **17** | `Payment` | `payments` | **Renamed** | Standardized to plural snake_case; updated to 1:many cardinality with `orders` to support payment timeouts and retries. |
| **18** | `Receipt` | *removed* | **Removed** | Redundant table. The order confirmation view (`/orders/{orderNumber}`) serves as the digital receipt; payment proof is stored in `payments`. |
| **19** | `Order_Status` (table) | `order_status_history` | **Renamed & Refined** | The active status lives on `orders.order_status`; `order_status_history` is retained as a planned chronological transition log. |
| **20** | `Delivery` / `Delivery_Status` | `shipments` | **Renamed & Unified** | Merged into `shipments` table holding carrier (`J&T Express`), tracking code, dispatch dates, and fulfillment stages (including planned `cancelled`). |
| **21** | `Audit_Log` | `admin_audit_logs` | **Renamed & Refined** | Standardized to `admin_audit_logs`; stores backoffice actions with before/after JSON diffs. |
| **22** | `Site_Content` / `menus` / `banners` / `content` / `settings` | *removed* | **Deferred to Phase 2** | Dynamic CMS tables are out of scope for the current release. Storefront marketing content is maintained in Blade templates. |
| **23** | `Loyalty` / `Loyalty_Logs` / `Loyalty_Transaction` | *removed* | **Deferred to Phase 2** | Loyalty rewards are designated as Phase 2 future work and excluded from the operational relational ERD. |
| **24** | `Sales_Report` / `Product_Report` / `Customer_Report` (tables) | *removed* | **Removed** | Pre-calculated snapshot tables cause data inconsistency and require scheduled cron jobs. Reports are generated via live SQL queries. |

---

## 2. Key Cardinality & Relationship Changes for Redrawing

When updating the connection lines in `WEBFinalPresentation.drawio`, apply the following cardinality conventions:

1. **`orders` to `payments`:**
   - *Old Diagram:* `1 -> 1` (`orders ||--|| payments`)
   - *Final Redraw:* **`1 -> many` (`orders ||--o{ payments`)**
   - *Reason:* A customer may encounter a 5-minute timeout on ABA KHQR, generating a subsequent payment attempt for the same order without creating a duplicate order.

2. **`orders` to `shipments`:**
   - *Old Diagram:* `1 -> 1` (`orders ||--|| shipments`)
   - *Final Redraw:* **`orders ||--o| shipments` (0..1 in the design, 1:1 in practice, with a planned unique constraint on `shipments.order_id`)**
   - *Reason:* Every order created via checkout initializes exactly one shipment in code (`CreateOrderAction.php`), but the domain model allows 0..1 (an order conceptually has at most one shipment); adding `unique('order_id')` to `shipments` is planned to guarantee 1:0..1 at the PostgreSQL engine level.

3. **`users` to `orders`:**
   - *Old Diagram:* Mandatory `1 -> many` (requiring customer account)
   - *Final Redraw:* **`0..1 -> many` (`users ||--o{ orders` with nullable `user_id`)**
   - *Reason:* Guest checkout generates orders where `user_id IS NULL` and `guest_token` secures order access.

4. **`products` to `product_variants`:**
   - *Old Diagram:* Omitted in "HOYOS ERD" tab
   - *Final Redraw:* **`1 -> 1..N` (`products ||--|{ product_variants`)**
   - *Reason:* Apparel items always have sizes; stock exists only on variants.

---

## 3. Column-Level Attribute Mapping Reference

Use this table when updating individual entity field names in `WEBFinalPresentation.drawio`:

| Legacy draw.io Column / Field | Final Codebase / Schema Column | Target Table | Architectural Rationale & Implementation Note |
| :--- | :--- | :--- | :--- |
| `password_hash` | `password` | `users` | Follows Laravel Eloquent standard authentication convention (`$table->string('password')`). |
| `customer_id` / `user_id` | `id` | `users` | Primary key on the unified `users` table; avoids separate ID sequences for customers vs admins. |
| `Order.address_id` | `shipping_address`, `province`, `customer_name`, `customer_phone`, `customer_email` | `orders` | **Snapshot on Order:** Addresses are copied directly onto the order at checkout. If a customer later edits or deletes an address in their profile, historical orders remain unchanged. |
| `Receipt.tracking_number` | `tracking_number` | `shipments` | Tracking numbers belong exclusively to logistics fulfillment (`shipments`), not payment receipts. |
| `Receipt.receipt_number` | `order_number` | `orders` | The human-readable unique order identifier (e.g. `HOYOS-000123`) serves as the official transaction receipt number. |
| `quantity_in_stock` (on Product) | `stock_quantity` | `product_variants` | Inventory is tracked per size variant (`product_variants.stock_quantity`). Total product stock is computed dynamically via SQL `SUM()`. |
