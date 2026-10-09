# Restaurant Management Module — Specification

**Project:** Hotel Booking Management System (Multi-Tenant SaaS)
**Module:** Restaurant Management
**Version:** 1.0.0
**Status:** Draft

---

## 1. Overview

This document defines the **Restaurant Management Module** to be integrated into the existing Hotel Booking Management System. The module is designed as a multi-tenant, location-aware extension that plugs into the existing RBAC (Role/Menu/Permission) infrastructure without modifying core tables.

The module covers two major layers:

```
Setup Layer     → Categories, Items, Modifiers, Tables
Operation Layer → Orders, KOT, Billing, Payments, Reports
```

---

## 2. Integration Points with Existing System

### 2.1 Shared Infrastructure (No Changes Required)

| Existing Table     | How It Is Reused                                             |
|--------------------|--------------------------------------------------------------|
| `locations`        | Every restaurant record is scoped by `location_id`          |
| `users`            | Staff (cashier, waiter, kitchen) use existing user accounts  |
| `roles`            | New roles (Waiter, Cashier, Kitchen) added to existing table |
| `menus`            | New menu entries inserted for all restaurant screens         |
| `role_permissions` | Permissions for new menus granted per role as usual          |
| `payment_methods`  | Reused for restaurant payments (Cash, Card, Mobile)          |

### 2.2 New Roles to Insert into `roles` Table

```sql
INSERT INTO roles (name, isactive) VALUES
('Waiter',   1),
('Cashier',  1),
('Kitchen',  1);
```

### 2.3 New Menu Entries to Insert into `menus` Table

```sql
INSERT INTO menus (name, url, icon, parent_id, sort_order, isactive) VALUES
-- Parent
('Restaurant Manage', NULL, 'bx-restaurant', NULL, 4, 1),
-- Children (parent_id = id of above row)
('Dashboard',         'restaurant-dashboard.html',  'bx-right-arrow-alt', @parent, 1, 1),
('Categories',        'restaurant-categories.html', 'bx-right-arrow-alt', @parent, 2, 1),
('Food Items',        'restaurant-items.html',      'bx-right-arrow-alt', @parent, 3, 1),
('Modifiers',         'restaurant-modifiers.html',  'bx-right-arrow-alt', @parent, 4, 1),
('Tables',            'restaurant-tables.html',     'bx-right-arrow-alt', @parent, 5, 1),
('POS / New Order',   'restaurant-pos.html',        'bx-right-arrow-alt', @parent, 6, 1),
('Active Orders',     'restaurant-orders.html',     'bx-right-arrow-alt', @parent, 7, 1),
('Kitchen Display',   'restaurant-kitchen.html',    'bx-right-arrow-alt', @parent, 8, 1),
('Order History',     'restaurant-history.html',    'bx-right-arrow-alt', @parent, 9, 1),
('Reports',           'restaurant-reports.html',    'bx-right-arrow-alt', @parent, 10, 1);
```

---

## 3. Module Scope

### In Scope

- Food category management (hierarchical)
- Food item management with cost price, sell price, images
- Item modifier groups (e.g., size, spice level, extras)
- Table / seating management
- Full POS order flow (dine-in, takeaway, delivery)
- Kitchen Order Ticket (KOT) display
- Bill generation with discount and tax
- Payment recording (full / partial / split)
- Order history and basic reports

### Out of Scope (Future Enhancements)

- Online ordering / QR menu
- Inventory / stock tracking
- Supplier management
- Loyalty/rewards program
- WebSocket real-time push (AJAX polling used initially)

---

## 4. Database Schema

> All tables include `location_id` for multi-location/multi-tenant isolation.
> All foreign keys follow the same ON DELETE CASCADE / SET NULL pattern as the existing schema.

---

### 4.1 `restaurant_categories`

Hierarchical food categories (e.g., Beverages → Hot Drinks → Coffee).

```sql
CREATE TABLE IF NOT EXISTS restaurant_categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  parent_id   INT UNSIGNED NULL,
  name        VARCHAR(255) NOT NULL,
  image       VARCHAR(255) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  isactive    TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  KEY idx_parent   (parent_id),
  KEY idx_isactive (isactive),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id)   REFERENCES restaurant_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**

| Column      | Description                                        |
|-------------|----------------------------------------------------|
| parent_id   | NULL = top-level category; set for sub-categories  |
| sort_order  | Controls display order on POS screen               |
| image       | Category icon/thumbnail                            |

---

### 4.2 `restaurant_items`

Master food/beverage item list.

```sql
CREATE TABLE IF NOT EXISTS restaurant_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id  INT UNSIGNED NOT NULL,
  category_id  INT UNSIGNED NOT NULL,
  name         VARCHAR(255) NOT NULL,
  description  TEXT NULL,
  image        VARCHAR(255) NULL,
  cost_price   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  sell_price   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  unit         VARCHAR(50) NULL COMMENT 'e.g. plate, glass, piece, kg',
  is_veg       TINYINT(1) NOT NULL DEFAULT 0,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  sort_order   INT NOT NULL DEFAULT 0,
  isactive     TINYINT(1) NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location  (location_id),
  KEY idx_category  (category_id),
  KEY idx_available (is_available),
  KEY idx_isactive  (isactive),
  FOREIGN KEY (location_id) REFERENCES locations(id)             ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES restaurant_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**

| Column       | Description                                         |
|--------------|-----------------------------------------------------|
| cost_price   | Purchase / production cost (internal use, reports)  |
| sell_price   | Price shown on POS and printed on bills             |
| is_veg       | Vegetarian flag for dietary filtering               |
| is_available | Toggle availability without deactivating the item   |
| unit         | Free-text serving unit (plate, glass, piece, etc.)  |

---

### 4.3 `restaurant_modifier_groups`

Groups of customisation options (e.g., "Size", "Spice Level", "Extras").

```sql
CREATE TABLE IF NOT EXISTS restaurant_modifier_groups (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name        VARCHAR(255) NOT NULL,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  is_multiple TINYINT(1) NOT NULL DEFAULT 0,
  isactive    TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.4 `restaurant_modifiers`

Individual options within a modifier group.

```sql
CREATE TABLE IF NOT EXISTS restaurant_modifiers (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  modifier_group_id INT UNSIGNED NOT NULL,
  name             VARCHAR(255) NOT NULL,
  extra_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  isactive         TINYINT(1) NOT NULL DEFAULT 1,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_group (modifier_group_id),
  FOREIGN KEY (modifier_group_id) REFERENCES restaurant_modifier_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.5 `restaurant_item_modifier_groups`

Links items to their applicable modifier groups.

```sql
CREATE TABLE IF NOT EXISTS restaurant_item_modifier_groups (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id           INT UNSIGNED NOT NULL,
  modifier_group_id INT UNSIGNED NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_item_group (item_id, modifier_group_id),
  FOREIGN KEY (item_id)           REFERENCES restaurant_items(id)           ON DELETE CASCADE,
  FOREIGN KEY (modifier_group_id) REFERENCES restaurant_modifier_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.6 `restaurant_tables`

Physical seating tables within a location.

```sql
CREATE TABLE IF NOT EXISTS restaurant_tables (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL COMMENT 'Table number or name, e.g. T-01, VIP-1',
  capacity    TINYINT UNSIGNED NOT NULL DEFAULT 4,
  status      ENUM('available','occupied','reserved','maintenance')
                NOT NULL DEFAULT 'available',
  isactive    TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  KEY idx_status   (status),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.7 `restaurant_orders`

Master order record.

```sql
CREATE TABLE IF NOT EXISTS restaurant_orders (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_no       VARCHAR(50) NOT NULL,
  location_id    INT UNSIGNED NOT NULL,
  table_id       INT UNSIGNED NULL,
  customer_name  VARCHAR(255) NULL,
  customer_phone VARCHAR(50)  NULL,
  order_type     ENUM('dine-in','takeaway','delivery') NOT NULL DEFAULT 'dine-in',
  order_status   ENUM('pending','confirmed','preparing','ready','served','completed','cancelled')
                   NOT NULL DEFAULT 'pending',
  payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  subtotal       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_type  ENUM('flat','percent') NULL,
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax_percent    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  tax_amount     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  final_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  note           TEXT NULL,
  created_by     INT UNSIGNED NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_order_no (order_no),
  KEY idx_location      (location_id),
  KEY idx_table         (table_id),
  KEY idx_order_status  (order_status),
  KEY idx_payment_status(payment_status),
  KEY idx_created_by    (created_by),
  FOREIGN KEY (location_id) REFERENCES locations(id)           ON DELETE CASCADE,
  FOREIGN KEY (table_id)    REFERENCES restaurant_tables(id)   ON DELETE SET NULL,
  FOREIGN KEY (created_by)  REFERENCES users(id)               ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Order Status Flow:**

```
pending → confirmed → preparing → ready → served → completed
                                                 ↘ cancelled (any stage before served)
```

---

### 4.8 `restaurant_order_items`

Line items within an order.

```sql
CREATE TABLE IF NOT EXISTS restaurant_order_items (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED NOT NULL,
  item_id     INT UNSIGNED NOT NULL,
  item_name   VARCHAR(255) NOT NULL COMMENT 'Snapshot of name at time of order',
  unit_price  DECIMAL(10,2) NOT NULL,
  quantity    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  modifier_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  note        TEXT NULL,
  status      ENUM('pending','preparing','ready','served') NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_order  (order_id),
  KEY idx_item   (item_id),
  KEY idx_status (status),
  FOREIGN KEY (order_id) REFERENCES restaurant_orders(id)  ON DELETE CASCADE,
  FOREIGN KEY (item_id)  REFERENCES restaurant_items(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> `item_name`, `unit_price` are **snapshotted** at order time so price changes do not affect historical orders.

---

### 4.9 `restaurant_order_item_modifiers`

Selected modifier options per order line item.

```sql
CREATE TABLE IF NOT EXISTS restaurant_order_item_modifiers (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_item_id    INT UNSIGNED NOT NULL,
  modifier_id      INT UNSIGNED NOT NULL,
  modifier_name    VARCHAR(255) NOT NULL,
  extra_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  PRIMARY KEY (id),
  KEY idx_order_item (order_item_id),
  FOREIGN KEY (order_item_id) REFERENCES restaurant_order_items(id) ON DELETE CASCADE,
  FOREIGN KEY (modifier_id)   REFERENCES restaurant_modifiers(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.10 `restaurant_order_payments`

Payment transactions against an order (supports partial payments).

```sql
CREATE TABLE IF NOT EXISTS restaurant_order_payments (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id          INT UNSIGNED NOT NULL,
  payment_method_id INT UNSIGNED NULL,
  amount            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  transaction_ref   VARCHAR(255) NULL,
  payment_date      DATETIME NOT NULL,
  received_by       INT UNSIGNED NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_order          (order_id),
  KEY idx_payment_method (payment_method_id),
  FOREIGN KEY (order_id)          REFERENCES restaurant_orders(id)  ON DELETE CASCADE,
  FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)    ON DELETE SET NULL,
  FOREIGN KEY (received_by)       REFERENCES users(id)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.11 `restaurant_order_status_logs`

Audit trail for every status change on an order.

```sql
CREATE TABLE IF NOT EXISTS restaurant_order_status_logs (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id   INT UNSIGNED NOT NULL,
  old_status VARCHAR(50) NULL,
  new_status VARCHAR(50) NOT NULL,
  changed_by INT UNSIGNED NULL,
  changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_order (order_id),
  FOREIGN KEY (order_id)   REFERENCES restaurant_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.12 `restaurant_settings`

Per-location restaurant configuration.

```sql
CREATE TABLE IF NOT EXISTS restaurant_settings (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id     INT UNSIGNED NOT NULL,
  restaurant_name VARCHAR(255) NULL,
  currency_symbol VARCHAR(10)  NOT NULL DEFAULT '৳',
  tax_percent     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  service_charge  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  receipt_footer  TEXT NULL,
  kot_printer     VARCHAR(100) NULL COMMENT 'Printer name or IP for KOT',
  bill_printer    VARCHAR(100) NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 5. Entity Relationship Summary

```
locations
  │
  ├── restaurant_settings          (1:1)
  ├── restaurant_categories        (1:N, self-referential for hierarchy)
  │     └── restaurant_items       (1:N)
  │           └── restaurant_item_modifier_groups  (N:N via junction)
  ├── restaurant_modifier_groups   (1:N)
  │     └── restaurant_modifiers   (1:N)
  ├── restaurant_tables            (1:N)
  └── restaurant_orders            (1:N)
        ├── restaurant_order_items           (1:N)
        │     └── restaurant_order_item_modifiers (1:N)
        ├── restaurant_order_payments        (1:N)
        └── restaurant_order_status_logs     (1:N)
```

---

## 6. API Endpoints

### Setup APIs

| Method | Endpoint                             | Permission   | Description                  |
|--------|--------------------------------------|--------------|------------------------------|
| GET    | /restaurant/categories               | can_view     | List all categories          |
| POST   | /restaurant/categories               | can_add      | Create category               |
| PUT    | /restaurant/categories/{id}          | can_edit     | Update category               |
| DELETE | /restaurant/categories/{id}          | can_delete   | Soft-delete category          |
| GET    | /restaurant/items                    | can_view     | List items (filterable)       |
| POST   | /restaurant/items                    | can_add      | Create item                   |
| PUT    | /restaurant/items/{id}               | can_edit     | Update item                   |
| DELETE | /restaurant/items/{id}               | can_delete   | Soft-delete item              |
| GET    | /restaurant/modifier-groups          | can_view     | List modifier groups          |
| POST   | /restaurant/modifier-groups          | can_add      | Create modifier group         |
| PUT    | /restaurant/modifier-groups/{id}     | can_edit     | Update modifier group         |
| GET    | /restaurant/tables                   | can_view     | List tables with status       |
| POST   | /restaurant/tables                   | can_add      | Create table                  |
| PUT    | /restaurant/tables/{id}              | can_edit     | Update table                  |

### Order APIs

| Method | Endpoint                             | Permission   | Description                    |
|--------|--------------------------------------|--------------|--------------------------------|
| POST   | /restaurant/orders                   | can_add      | Create new order               |
| GET    | /restaurant/orders                   | can_view     | List orders (active/history)   |
| GET    | /restaurant/orders/{id}              | can_view     | Order detail                   |
| POST   | /restaurant/orders/{id}/items        | can_edit     | Add item to order              |
| PUT    | /restaurant/orders/{id}/items/{iid}  | can_edit     | Update order item              |
| DELETE | /restaurant/orders/{id}/items/{iid}  | can_edit     | Remove item from order         |
| POST   | /restaurant/orders/{id}/confirm      | can_edit     | Confirm → send to kitchen      |
| POST   | /restaurant/orders/{id}/hold         | can_edit     | Hold order                     |
| PUT    | /restaurant/orders/{id}/status       | can_edit     | Update order/item status       |
| GET    | /restaurant/orders/{id}/bill         | can_view     | Generate bill summary          |
| POST   | /restaurant/orders/{id}/payment      | can_edit     | Record payment                 |
| POST   | /restaurant/orders/{id}/complete     | can_edit     | Complete order, free table     |
| POST   | /restaurant/orders/{id}/cancel       | can_edit     | Cancel order                   |
| GET    | /restaurant/kitchen                  | can_view     | Kitchen display feed           |

### Report APIs

| Method | Endpoint                             | Permission   | Description                    |
|--------|--------------------------------------|--------------|--------------------------------|
| GET    | /restaurant/reports/sales            | can_view     | Sales summary (date range)     |
| GET    | /restaurant/reports/items            | can_view     | Best-selling items             |
| GET    | /restaurant/reports/profit           | can_view     | Profit (sell - cost)           |
| GET    | /restaurant/reports/payments         | can_view     | Payment method breakdown       |

---

## 7. Frontend Screens

### 7.1 Setup Screens

**Categories (`restaurant-categories.html`)**
- Hierarchical list with parent/child display
- Add / Edit / Delete with inline toggle for isactive
- Image upload support

**Food Items (`restaurant-items.html`)**
- Filterable by category, availability, veg/non-veg
- Card or table view
- Fields: name, category, image, unit, cost price, sell price, modifiers, is_veg, is_available
- Profit margin auto-calculated: `((sell - cost) / sell) × 100`

**Modifiers (`restaurant-modifiers.html`)**
- Manage modifier groups (Size, Extras, Spice, etc.)
- Add individual options with optional extra price

**Tables (`restaurant-tables.html`)**
- Visual grid showing table name, capacity, status
- Status badge: Available (green), Occupied (red), Reserved (amber), Maintenance (grey)

### 7.2 Operation Screens

**POS / New Order (`restaurant-pos.html`)**

```
┌──────────────────────────────────────────────────────────┐
│  [Table: T-01]  [Type: Dine-in ▼]  [Order #: RST-0042]  │
├───────────────┬──────────────────────┬────────────────────┤
│  Categories   │    Food Items Grid   │    Order Cart      │
│  (Left Panel) │    (Centre Panel)    │    (Right Panel)   │
│               │                      │                    │
│  > Starters   │  [Item] [Item] ...   │  Item       Qty ✕  │
│  > Main       │  [Item] [Item] ...   │  Biryani     1  🗑 │
│  > Drinks     │                      │  Tea         2  🗑 │
│  > Desserts   │  [Search bar]        │  ─────────────     │
│               │  [Veg filter]        │  Subtotal  850     │
│               │                      │  Discount    0     │
│               │                      │  Tax (5%)   43     │
│               │                      │  Total     893     │
│               │                      │  [Hold] [Confirm]  │
└───────────────┴──────────────────────┴────────────────────┘
```

**Active Orders (`restaurant-orders.html`)**
- Kanban-style columns: Pending | Confirmed | Preparing | Ready | Served
- Each card shows order number, table, item count, elapsed time
- Click card to view detail, update status, or open bill

**Kitchen Display (`restaurant-kitchen.html`)**
- Auto-refreshing (5-second AJAX poll)
- Cards for confirmed/preparing orders
- Shows: Order no, table, items + notes, time since confirmed
- Buttons: [Start Preparing] [Mark Ready]
- No price information shown

**Order History (`restaurant-history.html`)**
- Date range picker, status filter, search by order no / customer
- Exportable summary
- Click row to see full detail and bill

**Restaurant Dashboard (`restaurant-dashboard.html`)**
- Today's revenue, order count, average order value
- Table status overview
- Top 5 selling items (today)
- Hourly sales bar chart

### 7.3 Bill / Receipt

- Printable bill: order no, date, table, items, modifiers, subtotal, discount, tax, total
- Payment method and amount received
- Change amount if cash payment
- Receipt footer from `restaurant_settings`

---

## 8. Business Rules

| Rule | Description |
|------|-------------|
| BR-01 | An order must have at least one item before confirmation |
| BR-02 | Table assignment is mandatory for dine-in orders |
| BR-03 | Items cannot be added/removed after the order status reaches `served` |
| BR-04 | Order cannot be completed unless payment_status = `paid` |
| BR-05 | Table status changes to `occupied` on order confirm; reverts to `available` on order complete |
| BR-06 | Partial payment is allowed; order stays in `partial` until fully paid |
| BR-07 | `cost_price` is visible only to Admin/Manager roles; hidden from Cashier/Waiter |
| BR-08 | Order number is auto-generated: `RST-{YYYYMMDD}-{sequence}` per location |
| BR-09 | Cancellation is only allowed before status reaches `served` |
| BR-10 | All price changes to items do NOT affect existing orders (snapshot model) |

---

## 9. Role Permission Matrix

| Screen / Action             | Admin | Manager | Cashier | Waiter | Kitchen |
|-----------------------------|:-----:|:-------:|:-------:|:------:|:-------:|
| Categories (CRUD)           |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Items (CRUD)                |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| View cost_price             |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Tables (CRUD)               |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Create Order (POS)          |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |
| Confirm / Hold Order        |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |
| Update Item Status          |  ✅   |   ✅    |   ❌    |   ✅   |   ✅    |
| Generate Bill               |  ✅   |   ✅    |   ✅    |   ❌   |   ❌    |
| Record Payment              |  ✅   |   ✅    |   ✅    |   ❌   |   ❌    |
| Cancel Order                |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| View Order History          |  ✅   |   ✅    |   ✅    |   ❌   |   ❌    |
| View Reports                |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Kitchen Display             |  ✅   |   ✅    |   ❌    |   ❌   |   ✅    |
| Restaurant Settings         |  ✅   |   ❌    |   ❌    |   ❌   |   ❌    |

---

## 10. Validation Rules

| Field           | Rule                                                         |
|-----------------|--------------------------------------------------------------|
| Item sell_price | Must be > 0                                                  |
| Item cost_price | Must be >= 0                                                 |
| Quantity        | Must be integer > 0                                          |
| Order type      | If dine-in → table_id required                               |
| Payment amount  | Must be > 0 and not exceed remaining balance                 |
| Order confirm   | Must have at least one item in `pending` status              |
| Discount        | Percent discount must be 0–100; flat must not exceed subtotal |
| Tax             | Pulled from `restaurant_settings`, not manually entered      |

---

## 11. Order Number Generation

```php
// Format: RST-YYYYMMDD-XXXX (4-digit sequence, resets daily per location)
function generateOrderNo(int $locationId): string {
    $date = date('Ymd');
    $prefix = "RST-{$date}-";

    // Get last order no for this location + date
    $last = DB::query(
        "SELECT order_no FROM restaurant_orders
         WHERE location_id = ? AND order_no LIKE ?
         ORDER BY id DESC LIMIT 1",
        [$locationId, $prefix . '%']
    );

    $seq = $last ? (int)substr($last->order_no, -4) + 1 : 1;
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}
// Example: RST-20250520-0001
```

---

## 12. Billing Calculation Logic

```
subtotal       = SUM(order_items.line_total)
line_total     = (unit_price + modifier_price) × quantity

discount_amount = discount_type == 'percent'
                  ? subtotal × (discount_value / 100)
                  : discount_value

taxable_amount  = subtotal - discount_amount
tax_amount      = taxable_amount × (tax_percent / 100)

final_amount    = taxable_amount + tax_amount
```

---

## 13. Kitchen Order Ticket (KOT) Format

```
╔══════════════════════════════╗
║   KOT — ORDER #RST-0042      ║
║   Table: T-05  |  Dine-In    ║
║   Time: 13:42  |  Waiter: Ali║
╠══════════════════════════════╣
║  1× Chicken Biryani          ║
║     Note: Extra spicy        ║
║  2× Mango Lassi              ║
║     [Large, No Ice]          ║
║  1× Garlic Naan              ║
╚══════════════════════════════╝
```
- No prices shown on KOT
- Printed/displayed immediately on order confirmation

---

## 14. Performance Considerations

| Concern              | Solution                                              |
|----------------------|-------------------------------------------------------|
| Kitchen screen lag   | AJAX polling every 5 seconds on `restaurant_kitchen`  |
| POS item load speed  | Cache menu items per location (session/localStorage)  |
| Order list queries   | Index on `location_id`, `order_status`, `created_at`  |
| Price snapshots      | Store `item_name`, `unit_price` in `order_items`      |
| Multi-tenant safety  | Every query filters by `location_id` from session     |

---

## 15. Security Considerations

- All endpoints verify `location_id` from authenticated session (prevent cross-tenant data access)
- `cost_price` returned by API only when `role` is Admin or Manager
- Order modification endpoints check order status before allowing changes
- Payment endpoints validate that `amount <= remaining_balance`
- CSRF tokens on all POST/PUT/DELETE form submissions
- Input sanitization on all free-text fields (item notes, customer name)

---

## 16. File / Folder Structure (Suggested)

```
/restaurant/
  ├── api/
  │   ├── categories.php
  │   ├── items.php
  │   ├── modifier-groups.php
  │   ├── tables.php
  │   ├── orders.php
  │   ├── kitchen.php
  │   └── reports.php
  ├── views/
  │   ├── restaurant-dashboard.html
  │   ├── restaurant-categories.html
  │   ├── restaurant-items.html
  │   ├── restaurant-modifiers.html
  │   ├── restaurant-tables.html
  │   ├── restaurant-pos.html
  │   ├── restaurant-orders.html
  │   ├── restaurant-kitchen.html
  │   ├── restaurant-history.html
  │   └── restaurant-reports.html
  ├── js/
  │   ├── pos.js
  │   └── kitchen.js
  └── migrations/
      └── restaurant_module.sql
```

---

## 17. Migration Checklist

- [ ] Run `restaurant_module.sql` for each tenant database
- [ ] Insert new roles (Waiter, Cashier, Kitchen) into `roles`
- [ ] Insert new menu entries into `menus`
- [ ] Grant permissions in `role_permissions` for new menus
- [ ] Insert default row into `restaurant_settings` for each location
- [ ] Verify `payment_methods` has at least one active method per location

---

## 18. Future Enhancements

| Feature                  | Notes                                              |
|--------------------------|----------------------------------------------------|
| QR Code Menu             | Public URL per location; read-only item display    |
| Online Ordering          | Customer-facing booking + restaurant order         |
| Inventory Deduction      | Reduce stock on order item served                  |
| WebSocket KOT            | Replace AJAX polling for real-time kitchen updates |
| Loyalty Points           | Earn points per order, redeem as discount          |
| Multi-Printer Support    | Route KOT to different printers by category        |
| Delivery Tracking        | Driver assignment, live status                     |

---

*End of Specification — Restaurant Management Module v1.0.0*
