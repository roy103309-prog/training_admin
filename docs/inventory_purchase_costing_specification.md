# Inventory, Purchase & Costing Module — Full Specification

**Project:** Hotel & Restaurant Management System (Multi-Tenant SaaS)
**Module:** Inventory, Purchase & Costing
**Version:** 1.0.0
**Depends On:** Restaurant Module v1.0, POS Module v2.0
**Status:** Draft

---

## 1. Overview

This module is the financial intelligence layer of the restaurant system. It connects every order placed in the POS directly to raw material consumption, purchase cost, and profit reporting.

```
Supplier → Purchase → Stock Ledger → Recipe Engine → Order Deduction
                                                           ↓
                                               Cost Calculation → Profit Report
```

### What This Module Unlocks

| Without This Module     | With This Module              |
|-------------------------|-------------------------------|
| You know what you sold  | You know what it cost to sell |
| No stock visibility     | Real-time stock per material  |
| Manual cost guessing    | Auto cost from purchase price |
| No waste tracking       | Damage/loss adjustments       |
| No supplier records     | Full supplier purchase history|

---

## 2. Integration Points with Existing System

### 2.1 Existing Tables Used

| Table                    | How It Is Used                                            |
|--------------------------|-----------------------------------------------------------|
| `locations`              | All inventory data scoped by `location_id`                |
| `users`                  | Created-by tracking on purchases and adjustments          |
| `restaurant_items`       | Recipes are linked to `restaurant_items.id`               |
| `restaurant_order_items` | Order confirm hook triggers stock deduction               |
| `restaurant_orders`      | Order status change to `confirmed` fires deduction event  |
| `payment_methods`        | Reused for supplier payment recording                     |

### 2.2 Hook Into Existing Order Flow

In `restaurant_orders`, when `order_status` changes to `confirmed`:

```php
// In OrderService::confirm($orderId)
// After updating order_status = 'confirmed':
StockService::deductForOrder($orderId);
```

This is the **single integration point** — no other existing table is modified.

### 2.3 New Menu Entries

```sql
INSERT INTO menus (name, url, icon, parent_id, sort_order, isactive) VALUES
-- Parent
('Inventory',          NULL,                       'bx-store',          NULL,    5,  1),
-- Children
('Raw Materials',      'inv-materials.html',       'bx-right-arrow-alt', @inv,   1,  1),
('Material Categories','inv-mat-categories.html',  'bx-right-arrow-alt', @inv,   2,  1),
('Suppliers',          'inv-suppliers.html',       'bx-right-arrow-alt', @inv,   3,  1),
('Purchases',          'inv-purchases.html',       'bx-right-arrow-alt', @inv,   4,  1),
('Stock Ledger',       'inv-stock.html',           'bx-right-arrow-alt', @inv,   5,  1),
('Stock Adjustment',   'inv-adjustment.html',      'bx-right-arrow-alt', @inv,   6,  1),
('Recipe Builder',     'inv-recipes.html',         'bx-right-arrow-alt', @inv,   7,  1),
('Cost Analysis',      'inv-cost.html',            'bx-right-arrow-alt', @inv,   8,  1),
('Profit Report',      'inv-profit.html',          'bx-right-arrow-alt', @inv,   9,  1);
```

---

## 3. Complete Database Schema

> Naming convention matches existing system.
> All tables include `location_id`.
> All FKs follow existing ON DELETE patterns.

---

### 3.1 `inv_material_categories`

Group raw materials by type (Meat, Dairy, Vegetables, Spices, Beverages, Dry Goods, etc.).

```sql
CREATE TABLE IF NOT EXISTS inv_material_categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name        VARCHAR(255) NOT NULL,
  isactive    TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.2 `inv_materials`

Master list of all raw materials / ingredients tracked in stock.

```sql
CREATE TABLE IF NOT EXISTS inv_materials (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id     INT UNSIGNED NOT NULL,
  category_id     INT UNSIGNED NULL,
  name            VARCHAR(255) NOT NULL,
  unit            VARCHAR(50)  NOT NULL COMMENT 'kg, g, liter, ml, pcs, pack, bag',
  min_stock       DECIMAL(10,3) NOT NULL DEFAULT 0.000
                  COMMENT 'Low stock threshold for alerts',
  current_stock   DECIMAL(10,3) NOT NULL DEFAULT 0.000
                  COMMENT 'Maintained by triggers/service — do not update directly',
  last_cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00
                  COMMENT 'Cost from most recent purchase — used in recipe costing',
  avg_cost_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00
                  COMMENT 'Weighted average cost — used for profit reports',
  isactive        TINYINT(1) NOT NULL DEFAULT 1,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  KEY idx_category (category_id),
  KEY idx_isactive (isactive),
  FOREIGN KEY (location_id) REFERENCES locations(id)              ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES inv_material_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields Explained:**

| Column           | Description                                                    |
|------------------|----------------------------------------------------------------|
| `unit`           | Smallest trackable unit: `kg`, `g`, `liter`, `ml`, `pcs`, etc.|
| `min_stock`      | Alert fires when `current_stock` drops below this             |
| `current_stock`  | Denormalised cache; recalculated from `inv_stock_ledger`      |
| `last_cost_price`| Updated on each purchase, used in item-level cost preview     |
| `avg_cost_price` | Weighted average: `(old_avg × old_qty + new_price × new_qty) / total_qty` |

---

### 3.3 `inv_suppliers`

Supplier master data.

```sql
CREATE TABLE IF NOT EXISTS inv_suppliers (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name        VARCHAR(255) NOT NULL,
  phone       VARCHAR(50)  NULL,
  email       VARCHAR(100) NULL,
  address     TEXT NULL,
  due_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00
              COMMENT 'Outstanding payment to supplier',
  isactive    TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.4 `inv_purchases`

Purchase order header.

```sql
CREATE TABLE IF NOT EXISTS inv_purchases (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id     INT UNSIGNED NOT NULL,
  purchase_no     VARCHAR(50)  NOT NULL,
  supplier_id     INT UNSIGNED NULL,
  purchase_date   DATE NOT NULL,
  total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  paid_amount     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  due_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method_id INT UNSIGNED NULL,
  payment_status  ENUM('paid','partial','unpaid') NOT NULL DEFAULT 'unpaid',
  note            TEXT NULL,
  created_by      INT UNSIGNED NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_purchase_no (purchase_no),
  KEY idx_location  (location_id),
  KEY idx_supplier  (supplier_id),
  KEY idx_date      (purchase_date),
  KEY idx_status    (payment_status),
  FOREIGN KEY (location_id)       REFERENCES locations(id)       ON DELETE CASCADE,
  FOREIGN KEY (supplier_id)       REFERENCES inv_suppliers(id)   ON DELETE SET NULL,
  FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by)        REFERENCES users(id)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.5 `inv_purchase_items`

Line items of a purchase order.

```sql
CREATE TABLE IF NOT EXISTS inv_purchase_items (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  purchase_id INT UNSIGNED NOT NULL,
  material_id INT UNSIGNED NOT NULL,
  quantity    DECIMAL(10,3) NOT NULL,
  unit_price  DECIMAL(10,2) NOT NULL COMMENT 'Price per unit at time of purchase',
  total_price DECIMAL(10,2) NOT NULL,

  PRIMARY KEY (id),
  KEY idx_purchase (purchase_id),
  KEY idx_material (material_id),
  FOREIGN KEY (purchase_id) REFERENCES inv_purchases(id)  ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES inv_materials(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.6 `inv_stock_ledger` ⭐ Core Table

**Single source of truth** for all stock movement. Never deleted — append-only audit log.

```sql
CREATE TABLE IF NOT EXISTS inv_stock_ledger (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id  INT UNSIGNED NOT NULL,
  material_id  INT UNSIGNED NOT NULL,
  txn_type     ENUM('IN','OUT','ADJUST_ADD','ADJUST_SUB','DAMAGE','RETURN')
                 NOT NULL,
  quantity     DECIMAL(10,3) NOT NULL COMMENT 'Always positive; direction = txn_type',
  unit_cost    DECIMAL(10,2) NOT NULL DEFAULT 0.00
               COMMENT 'Cost per unit at time of this transaction',
  total_cost   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  balance_after DECIMAL(10,3) NOT NULL DEFAULT 0.000
               COMMENT 'Stock level after this transaction (running total)',
  ref_type     ENUM('purchase','order','manual','return','damage') NOT NULL,
  ref_id       INT UNSIGNED NULL COMMENT 'ID of purchase/order/adjustment record',
  note         TEXT NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location   (location_id),
  KEY idx_material   (material_id),
  KEY idx_txn_type   (txn_type),
  KEY idx_ref        (ref_type, ref_id),
  KEY idx_created_at (created_at),
  FOREIGN KEY (location_id) REFERENCES locations(id)    ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES inv_materials(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by)  REFERENCES users(id)         ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Transaction Types:**

| `txn_type`    | Trigger                                    | Stock Effect |
|---------------|--------------------------------------------|:------------:|
| `IN`          | Purchase saved                             | +            |
| `OUT`         | Order confirmed (auto via recipe)          | −            |
| `ADJUST_ADD`  | Manual positive adjustment                 | +            |
| `ADJUST_SUB`  | Manual negative adjustment                 | −            |
| `DAMAGE`      | Damage / spoilage entry                    | −            |
| `RETURN`      | Return to supplier                         | −            |

**Real-time stock formula:**
```sql
SELECT
  SUM(CASE WHEN txn_type IN ('IN','ADJUST_ADD') THEN quantity ELSE 0 END) -
  SUM(CASE WHEN txn_type IN ('OUT','ADJUST_SUB','DAMAGE','RETURN') THEN quantity ELSE 0 END)
  AS current_stock
FROM inv_stock_ledger
WHERE material_id = ? AND location_id = ?;
```

> `inv_materials.current_stock` is updated after every ledger insert as a performance cache.

---

### 3.7 `inv_material_cost_history`

Immutable log of every price change per material (one row per purchase).

```sql
CREATE TABLE IF NOT EXISTS inv_material_cost_history (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id  INT UNSIGNED NOT NULL,
  purchase_id  INT UNSIGNED NOT NULL,
  unit_price   DECIMAL(10,2) NOT NULL,
  recorded_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_material (material_id),
  KEY idx_purchase (purchase_id),
  FOREIGN KEY (material_id) REFERENCES inv_materials(id)  ON DELETE CASCADE,
  FOREIGN KEY (purchase_id) REFERENCES inv_purchases(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.8 `inv_recipes`

Defines which raw materials (and how much) make up one unit of a restaurant menu item.

```sql
CREATE TABLE IF NOT EXISTS inv_recipes (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  item_id     INT UNSIGNED NOT NULL COMMENT 'FK → restaurant_items.id',
  material_id INT UNSIGNED NOT NULL,
  quantity    DECIMAL(10,4) NOT NULL COMMENT 'Amount needed per 1 unit of item sold',
  note        VARCHAR(255) NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_item_material (item_id, material_id),
  KEY idx_location (location_id),
  KEY idx_item     (item_id),
  KEY idx_material (material_id),
  FOREIGN KEY (location_id) REFERENCES locations(id)       ON DELETE CASCADE,
  FOREIGN KEY (item_id)     REFERENCES restaurant_items(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES inv_materials(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example Data:**

| item_id | material     | quantity | unit   |
|---------|--------------|----------|--------|
| 5       | Chicken      | 0.500    | kg     |
| 5       | Cooking Oil  | 0.100    | liter  |
| 5       | Mixed Spice  | 0.050    | kg     |
| 5       | Salt         | 0.010    | kg     |

> Order of 2× Chicken Curry → deducts Chicken 1.0 kg, Oil 0.2 L, Spice 0.1 kg, Salt 0.02 kg.

---

### 3.9 `inv_stock_adjustments`

Formal record for manual stock changes (damage, loss, physical count correction).

```sql
CREATE TABLE IF NOT EXISTS inv_stock_adjustments (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id  INT UNSIGNED NOT NULL,
  adj_date     DATE NOT NULL,
  adj_type     ENUM('damage','loss','correction_add','correction_sub','return') NOT NULL,
  note         TEXT NULL,
  created_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by)  REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.10 `inv_stock_adjustment_items`

Line items of an adjustment entry.

```sql
CREATE TABLE IF NOT EXISTS inv_stock_adjustment_items (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  adjustment_id  INT UNSIGNED NOT NULL,
  material_id    INT UNSIGNED NOT NULL,
  quantity       DECIMAL(10,3) NOT NULL,
  reason         VARCHAR(255) NULL,

  PRIMARY KEY (id),
  KEY idx_adjustment (adjustment_id),
  KEY idx_material   (material_id),
  FOREIGN KEY (adjustment_id) REFERENCES inv_stock_adjustments(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id)   REFERENCES inv_materials(id)          ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.11 `inv_supplier_payments`

Track payments made to suppliers (supports partial supplier due tracking).

```sql
CREATE TABLE IF NOT EXISTS inv_supplier_payments (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id       INT UNSIGNED NOT NULL,
  supplier_id       INT UNSIGNED NOT NULL,
  purchase_id       INT UNSIGNED NULL COMMENT 'Optionally linked to a specific purchase',
  amount            DECIMAL(10,2) NOT NULL,
  payment_method_id INT UNSIGNED NULL,
  transaction_ref   VARCHAR(255) NULL,
  payment_date      DATE NOT NULL,
  note              TEXT NULL,
  created_by        INT UNSIGNED NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_location (location_id),
  KEY idx_supplier (supplier_id),
  KEY idx_purchase (purchase_id),
  FOREIGN KEY (location_id)       REFERENCES locations(id)       ON DELETE CASCADE,
  FOREIGN KEY (supplier_id)       REFERENCES inv_suppliers(id)   ON DELETE RESTRICT,
  FOREIGN KEY (purchase_id)       REFERENCES inv_purchases(id)   ON DELETE SET NULL,
  FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by)        REFERENCES users(id)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.12 `inv_settings`

Per-location inventory configuration.

```sql
CREATE TABLE IF NOT EXISTS inv_settings (
  id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id              INT UNSIGNED NOT NULL,
  costing_method           ENUM('last_purchase','avg_cost') NOT NULL DEFAULT 'avg_cost',
  block_order_on_no_stock  TINYINT(1) NOT NULL DEFAULT 0
                           COMMENT '1 = block POS if any ingredient is out of stock',
  warn_order_on_low_stock  TINYINT(1) NOT NULL DEFAULT 1
                           COMMENT '1 = show warning on POS if ingredient is low',
  require_recipe_to_sell   TINYINT(1) NOT NULL DEFAULT 0
                           COMMENT '1 = cannot place order for item without recipe',
  purchase_no_prefix       VARCHAR(10) NOT NULL DEFAULT 'PUR',
  updated_at               TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Entity Relationship Summary

```
locations
  │
  ├── inv_settings                   (1:1)
  ├── inv_material_categories        (1:N)
  │     └── inv_materials            (1:N)
  │           ├── inv_stock_ledger   (1:N)  ← all movements
  │           ├── inv_material_cost_history (1:N)
  │           └── inv_recipes        (N:N via junction with restaurant_items)
  ├── inv_suppliers                  (1:N)
  │     ├── inv_purchases            (1:N)
  │     │     └── inv_purchase_items (1:N)
  │     └── inv_supplier_payments    (1:N)
  └── inv_stock_adjustments          (1:N)
        └── inv_stock_adjustment_items (1:N)

restaurant_items ←──── inv_recipes (item_id FK)
restaurant_orders ──── triggers ───► StockService::deductForOrder()
```

---

## 5. Service Layer (Backend Logic)

### 5.1 `PurchaseService::save()`

```php
DB::transaction(function () use ($data) {

    // 1. Insert inv_purchases header
    $purchaseId = DB::insert('inv_purchases', [...]);

    foreach ($data['items'] as $item) {

        // 2. Insert inv_purchase_items
        DB::insert('inv_purchase_items', [
            'purchase_id' => $purchaseId,
            'material_id' => $item['material_id'],
            'quantity'    => $item['quantity'],
            'unit_price'  => $item['unit_price'],
            'total_price' => $item['quantity'] * $item['unit_price'],
        ]);

        // 3. Insert stock ledger (IN)
        StockService::ledgerIn(
            materialId  : $item['material_id'],
            quantity    : $item['quantity'],
            unitCost    : $item['unit_price'],
            refType     : 'purchase',
            refId       : $purchaseId
        );

        // 4. Update cost history
        DB::insert('inv_material_cost_history', [
            'material_id' => $item['material_id'],
            'purchase_id' => $purchaseId,
            'unit_price'  => $item['unit_price'],
        ]);

        // 5. Update last_cost_price and avg_cost_price on inv_materials
        StockService::updateCostCache($item['material_id'], $item['unit_price'], $item['quantity']);
    }

    // 6. Update supplier due_balance if not fully paid
    if ($data['due_amount'] > 0) {
        DB::increment('inv_suppliers', $data['supplier_id'], 'due_balance', $data['due_amount']);
    }
});
```

---

### 5.2 `StockService::ledgerIn()`

```php
function ledgerIn(int $materialId, float $quantity, float $unitCost,
                  string $refType, int $refId, ?string $note = null): void
{
    $current = DB::scalar(
        'SELECT current_stock FROM inv_materials WHERE id = ?', [$materialId]
    );
    $balanceAfter = $current + $quantity;

    DB::insert('inv_stock_ledger', [
        'location_id'   => LOCATION_ID,
        'material_id'   => $materialId,
        'txn_type'      => 'IN',
        'quantity'      => $quantity,
        'unit_cost'     => $unitCost,
        'total_cost'    => $quantity * $unitCost,
        'balance_after' => $balanceAfter,
        'ref_type'      => $refType,
        'ref_id'        => $refId,
        'note'          => $note,
        'created_by'    => Auth::id(),
    ]);

    DB::update('inv_materials', $materialId, [
        'current_stock' => $balanceAfter
    ]);
}
```

---

### 5.3 `StockService::deductForOrder()` ⭐ Core Hook

Called automatically when an order is confirmed.

```php
function deductForOrder(int $orderId): void
{
    $orderItems = DB::query(
        'SELECT item_id, quantity FROM restaurant_order_items WHERE order_id = ?',
        [$orderId]
    );

    foreach ($orderItems as $oi) {
        $recipes = DB::query(
            'SELECT material_id, quantity FROM inv_recipes
             WHERE item_id = ? AND location_id = ?',
            [$oi['item_id'], LOCATION_ID]
        );

        if (empty($recipes)) continue; // No recipe = no deduction

        foreach ($recipes as $r) {
            $deductQty = $r['quantity'] * $oi['quantity'];
            $material  = DB::find('inv_materials', $r['material_id']);

            // Block if out of stock and strict mode is on
            $settings = InvSettings::get(LOCATION_ID);
            if ($settings->block_order_on_no_stock && $material->current_stock < $deductQty) {
                throw new \Exception("Insufficient stock: {$material->name}");
            }

            StockService::ledgerOut(
                materialId : $r['material_id'],
                quantity   : $deductQty,
                unitCost   : $material->avg_cost_price,
                refType    : 'order',
                refId      : $orderId
            );
        }
    }
}
```

---

### 5.4 `StockService::updateCostCache()` — Weighted Average

```php
function updateCostCache(int $materialId, float $newPrice, float $newQty): void
{
    $mat = DB::find('inv_materials', $materialId);

    // Weighted average = (old_avg × old_stock + new_price × new_qty) / (old_stock + new_qty)
    $newAvg = ($mat->current_stock > 0)
        ? (($mat->avg_cost_price * $mat->current_stock) + ($newPrice * $newQty))
          / ($mat->current_stock + $newQty)
        : $newPrice;

    DB::update('inv_materials', $materialId, [
        'last_cost_price' => $newPrice,
        'avg_cost_price'  => round($newAvg, 2),
    ]);
}
```

---

### 5.5 `CostService::calculateItemCost()`

Calculates the live cost to produce 1 unit of a menu item.

```php
function calculateItemCost(int $itemId, string $method = 'avg_cost'): float
{
    $recipes = DB::query(
        'SELECT r.quantity, m.last_cost_price, m.avg_cost_price
         FROM inv_recipes r
         JOIN inv_materials m ON m.id = r.material_id
         WHERE r.item_id = ? AND r.location_id = ?',
        [$itemId, LOCATION_ID]
    );

    $cost = 0;
    foreach ($recipes as $r) {
        $price = ($method === 'avg_cost') ? $r['avg_cost_price'] : $r['last_cost_price'];
        $cost += $price * $r['quantity'];
    }
    return round($cost, 2);
}
```

---

## 6. POS Integration

### 6.1 Stock Warning on POS Item Load

When the POS loads menu items, it fetches stock status for each item's ingredients:

```javascript
// On POS load — check all visible item stock levels
async function checkItemStockWarnings(itemIds) {
  const res = await fetch('/api/inv/stock/item-check?items=' + itemIds.join(','));
  const warnings = await res.json();
  warnings.forEach(w => {
    if (w.status === 'out') {
      markItemUnavailable(w.item_id, 'Out of stock');
    } else if (w.status === 'low') {
      markItemLowStock(w.item_id, w.message); // "Low: Chicken (0.5kg left)"
    }
  });
}
```

**API:**
```
GET /api/inv/stock/item-check?items=5,9,12&location_id=1
Response:
[
  { "item_id": 5, "status": "ok" },
  { "item_id": 9, "status": "low", "message": "Low: Chicken (0.8kg left)" },
  { "item_id": 12, "status": "out", "message": "Out of stock: Milk" }
]
```

### 6.2 Visual Indicators on Item Cards

```css
/* Low stock — amber border */
.item-card.stock-low  { border: 2px solid #f59e0b; }
.item-card.stock-low::after {
  content: '⚠ Low';
  background: #fef3c7;
  color: #92400e;
  font-size: 10px;
  padding: 2px 6px;
  position: absolute;
  top: 4px; right: 4px;
  border-radius: 4px;
}

/* Out of stock — greyed out, not clickable */
.item-card.stock-out  { opacity: 0.45; pointer-events: none; }
.item-card.stock-out::after {
  content: 'Out of Stock';
  background: #fee2e2;
  color: #991b1b;
  font-size: 10px;
  padding: 2px 6px;
  position: absolute;
  top: 4px; right: 4px;
  border-radius: 4px;
}
```

### 6.3 Order Confirm Hook

```php
// OrderService.php — existing method, add one line:

public function confirm(int $orderId): void
{
    DB::update('restaurant_orders', $orderId, ['order_status' => 'confirmed']);

    // ← NEW: trigger stock deduction
    StockService::deductForOrder($orderId);
}
```

---

## 7. API Endpoints

### 7.1 Raw Materials

| Method | Endpoint                      | Description                      |
|--------|-------------------------------|----------------------------------|
| GET    | /inv/materials                | List all materials (filterable)  |
| POST   | /inv/materials                | Create material                  |
| PUT    | /inv/materials/{id}           | Update material                  |
| DELETE | /inv/materials/{id}           | Soft-delete material             |
| GET    | /inv/materials/{id}/stock     | Current stock + ledger summary   |
| GET    | /inv/material-categories      | List categories                  |
| POST   | /inv/material-categories      | Create category                  |

### 7.2 Suppliers

| Method | Endpoint                      | Description                      |
|--------|-------------------------------|----------------------------------|
| GET    | /inv/suppliers                | List suppliers                   |
| POST   | /inv/suppliers                | Create supplier                  |
| PUT    | /inv/suppliers/{id}           | Update supplier                  |
| GET    | /inv/suppliers/{id}/dues      | Outstanding due balance          |
| POST   | /inv/suppliers/{id}/payment   | Record supplier payment          |

### 7.3 Purchases

| Method | Endpoint                      | Description                      |
|--------|-------------------------------|----------------------------------|
| GET    | /inv/purchases                | List purchases                   |
| POST   | /inv/purchases                | Create purchase + update stock   |
| GET    | /inv/purchases/{id}           | Purchase detail                  |
| PUT    | /inv/purchases/{id}           | Edit purchase (before confirmed) |
| DELETE | /inv/purchases/{id}           | Cancel + reverse stock           |

### 7.4 Stock

| Method | Endpoint                      | Description                          |
|--------|-------------------------------|--------------------------------------|
| GET    | /inv/stock                    | All materials with current stock     |
| GET    | /inv/stock/ledger             | Full ledger (filter by material/date)|
| POST   | /inv/stock/adjust             | Create manual adjustment             |
| GET    | /inv/stock/low-alerts         | Materials below min_stock            |
| GET    | /inv/stock/item-check         | POS stock check per menu item        |

### 7.5 Recipes

| Method | Endpoint                      | Description                      |
|--------|-------------------------------|----------------------------------|
| GET    | /inv/recipes/{item_id}        | Get recipe for a menu item       |
| POST   | /inv/recipes/{item_id}        | Save/replace full recipe         |
| DELETE | /inv/recipes/{item_id}/{mat}  | Remove one ingredient            |

### 7.6 Cost & Profit

| Method | Endpoint                      | Description                                |
|--------|-------------------------------|---------------------------------------------|
| GET    | /inv/cost/items               | Cost price per menu item (live calculated) |
| GET    | /inv/cost/orders              | Cost breakdown per order                   |
| GET    | /inv/profit/daily             | Daily profit report (sell - cost)          |
| GET    | /inv/profit/items             | Profit per menu item                       |
| GET    | /inv/profit/summary           | Date range profit summary                  |

---

## 8. Frontend Pages

### 8.1 Raw Materials (`inv-materials.html`)

```
┌─────────────────────────────────────────────────────────┐
│  Raw Materials         [+ Add Material]  [🔍 Search]    │
├─────────┬──────────────┬───────┬─────────┬──────────────┤
│ Name    │ Category     │ Unit  │ Stock   │ Min Stock    │
├─────────┼──────────────┼───────┼─────────┼──────────────┤
│ Chicken │ Meat         │ kg    │ 12.5 ✅ │ 2.0          │
│ Milk    │ Dairy        │ liter │ 0.8 ⚠️  │ 2.0          │
│ Salt    │ Spices       │ kg    │ 0.0 🔴  │ 0.5          │
└─────────┴──────────────┴───────┴─────────┴──────────────┘
```

Stock badges: ✅ OK (green) · ⚠️ Low (amber) · 🔴 Out (red)

### 8.2 Purchases (`inv-purchases.html`)

- Header form: Supplier (searchable), Date, Payment method, Paid amount
- Items table: Material (searchable), Quantity, Unit price, Total (auto-calculated)
- Summary: Grand total, Paid, Due
- Save triggers stock update immediately

### 8.3 Stock Ledger (`inv-stock.html`)

```
Material: [Chicken ▼]     Date: [From] [To]    [Filter]

┌──────────────┬──────┬──────────┬──────┬───────────┬─────────┐
│ Date/Time    │ Type │ Qty      │ Cost │ Ref       │ Balance │
├──────────────┼──────┼──────────┼──────┼───────────┼─────────┤
│ 20 May 08:00 │ IN   │ +10.0 kg │ ৳120 │ PUR-0012  │ 10.0 kg │
│ 20 May 13:42 │ OUT  │ -1.0 kg  │ ৳120 │ RST-0041  │  9.0 kg │
│ 20 May 15:10 │ OUT  │ -0.5 kg  │ ৳120 │ RST-0044  │  8.5 kg │
│ 21 May 09:00 │ ADJ- │ -0.3 kg  │ —    │ Damage    │  8.2 kg │
└──────────────┴──────┴──────────┴──────┴───────────┴─────────┘
```

### 8.4 Recipe Builder (`inv-recipes.html`)

```
┌───────────────────────────────────────────────────────┐
│  Recipe Builder                                        │
│                                                        │
│  Menu Item: [Chicken Biryani ▼] (searchable)          │
│  ──────────────────────────────────────────────────── │
│  INGREDIENTS                          [+ Add Row]     │
│  ┌──────────────────┬──────────┬──────┬──────┐        │
│  │ Material         │ Qty      │ Unit │      │        │
│  ├──────────────────┼──────────┼──────┼──────┤        │
│  │ [Chicken      ▼] │ [0.500]  │ kg   │ [🗑] │        │
│  │ [Cooking Oil  ▼] │ [0.100]  │ liter│ [🗑] │        │
│  │ [Mixed Spice  ▼] │ [0.050]  │ kg   │ [🗑] │        │
│  └──────────────────┴──────────┴──────┴──────┘        │
│                                                        │
│  Estimated Cost: ৳78.50 per serving                   │
│  Sell Price:     ৳220.00                              │
│  Profit Margin:  64.3%                                │
│                                                        │
│  [Save Recipe]                                         │
└───────────────────────────────────────────────────────┘
```

- Item dropdown is searchable
- Material dropdown per row is searchable
- Unit auto-filled from material record
- Cost estimate updates live as quantities are entered

### 8.5 Cost Analysis (`inv-cost.html`)

Table of all menu items showing:

| Item Name       | Sell Price | Cost Price | Profit | Margin % |
|-----------------|-----------|-----------|--------|----------|
| Chicken Biryani | ৳220      | ৳78.50    | ৳141.50| 64.3%    |
| Mango Lassi     | ৳60       | ৳18.20    | ৳41.80 | 69.7%    |
| Garlic Naan     | ৳30       | ৳8.90     | ৳21.10 | 70.3%    |

- Sort by margin ascending to identify loss-making items
- Red highlight if margin < configurable threshold (e.g., 20%)

### 8.6 Profit Report (`inv-profit.html`)

- Date range picker
- Summary cards: Total Revenue · Total Cost · Gross Profit · Margin %
- Bar chart: Daily revenue vs cost
- Table: Per-item sold quantity, revenue, cost, profit
- Export to PDF / CSV

### 8.7 Stock Dashboard (Overview Widget on Restaurant Dashboard)

Add to existing `restaurant-dashboard.html`:

```html
<!-- Low Stock Alerts Widget -->
<div class="card border-warning">
  <div class="card-header">⚠️ Stock Alerts</div>
  <ul class="list-group list-group-flush" id="stockAlertList">
    <!-- dynamically populated -->
  </ul>
</div>
```

---

## 9. Purchase Number Generation

```php
// Format: PUR-YYYYMMDD-XXXX
function generatePurchaseNo(int $locationId): string {
    $prefix = InvSettings::get($locationId)->purchase_no_prefix ?? 'PUR';
    $date   = date('Ymd');
    $last   = DB::scalar(
        "SELECT purchase_no FROM inv_purchases
         WHERE location_id = ? AND purchase_no LIKE ?
         ORDER BY id DESC LIMIT 1",
        [$locationId, "PUR-{$date}-%"]
    );
    $seq = $last ? (int) substr($last, -4) + 1 : 1;
    return "PUR-{$date}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    // e.g. PUR-20250520-0001
}
```

---

## 10. Costing Methods

### 10.1 Last Purchase Price

Item cost uses the most recent purchase price of each ingredient. Fast, simple.

```php
$price = $material->last_cost_price;
```

### 10.2 Weighted Average Cost (Recommended Default)

More accurate for fluctuating prices. Recalculated on every purchase.

```
new_avg = (current_stock × old_avg + new_qty × new_price)
          ÷ (current_stock + new_qty)
```

Configured per location in `inv_settings.costing_method`.

---

## 11. Business Rules

| Rule   | Description                                                                             |
|--------|-----------------------------------------------------------------------------------------|
| BR-01  | Stock deduction fires on `order_status = confirmed`, not on item serve                 |
| BR-02  | If `require_recipe_to_sell = 1`, POS blocks item without recipe                        |
| BR-03  | If `block_order_on_no_stock = 1`, order confirmation throws error if any ingredient is short |
| BR-04  | If `warn_order_on_low_stock = 1`, POS shows amber warning but does not block           |
| BR-05  | Cancelled orders do NOT reverse stock deduction (staff consumed prep time/ingredients) |
| BR-06  | Purchase edit is only allowed before `confirmed` status; confirmed purchases are locked |
| BR-07  | Purchase delete cancels the purchase and inserts a reversal `ADJUST_SUB` in ledger     |
| BR-08  | Recipe save is a full replace — delete all rows for item, re-insert from form          |
| BR-09  | `avg_cost_price` recalculates on every purchase line item insertion                    |
| BR-10  | Supplier `due_balance` increments on purchase; decrements on supplier payment          |
| BR-11  | `current_stock` column on `inv_materials` is a performance cache — ledger is authoritative |
| BR-12  | Manual adjustment requires a reason note (min 5 characters)                            |

---

## 12. Role Permission Matrix

| Page / Action              | Admin | Manager | Cashier | Waiter | Kitchen |
|----------------------------|:-----:|:-------:|:-------:|:------:|:-------:|
| Raw Materials (CRUD)       |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Material Categories (CRUD) |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Suppliers (CRUD)           |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Purchases (create)         |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Purchases (view)           |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Stock Ledger (view)        |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Stock Adjustment           |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Recipe Builder             |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Cost Analysis              |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Profit Report              |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Supplier Payments          |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Inv Settings               |  ✅   |   ❌    |   ❌    |   ❌   |   ❌    |
| POS stock warnings (view)  |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |

---

## 13. Validation Rules

| Field               | Rule                                                                 |
|---------------------|----------------------------------------------------------------------|
| Material name       | Required, max 255 chars, unique per location                        |
| Material unit       | Required, from allowed list: kg, g, liter, ml, pcs, pack, bag, box |
| Min stock           | Must be >= 0                                                        |
| Recipe quantity     | Must be > 0, max 4 decimal places                                   |
| Purchase quantity   | Must be > 0                                                         |
| Purchase unit_price | Must be > 0                                                         |
| Paid amount         | Must be >= 0 and <= total_amount                                     |
| Adjustment quantity | Must be > 0                                                         |
| Adjustment reason   | Required, min 5 characters                                          |

---

## 14. Performance Considerations

| Concern                        | Solution                                                          |
|--------------------------------|-------------------------------------------------------------------|
| Real-time stock on POS load    | `current_stock` cached column; refresh only on ledger insert     |
| Recipe cost calculation         | Computed on demand, not stored; cache per request lifecycle       |
| Profit report on large datasets | Aggregate with GROUP BY date, pre-filter by location_id          |
| Stock ledger growth            | Index on `(material_id, created_at)`; paginate display           |
| Deduction on order confirm     | Run in same DB transaction as order status update                |

---

## 15. Implementation Checklist

### Database
- [ ] Create `inv_material_categories`
- [ ] Create `inv_materials`
- [ ] Create `inv_suppliers`
- [ ] Create `inv_purchases`
- [ ] Create `inv_purchase_items`
- [ ] Create `inv_stock_ledger`
- [ ] Create `inv_material_cost_history`
- [ ] Create `inv_recipes`
- [ ] Create `inv_stock_adjustments`
- [ ] Create `inv_stock_adjustment_items`
- [ ] Create `inv_supplier_payments`
- [ ] Create `inv_settings`
- [ ] Insert default `inv_settings` row per location

### Menu / RBAC
- [ ] Insert `Inventory` parent menu and all child menus
- [ ] Insert `role_permissions` for all new menus

### Backend Services
- [ ] `PurchaseService::save()` — full transaction with ledger insert
- [ ] `StockService::ledgerIn()` — stock increase + cache update
- [ ] `StockService::ledgerOut()` — stock decrease + cache update
- [ ] `StockService::deductForOrder()` — recipe-based auto deduction
- [ ] `StockService::updateCostCache()` — weighted average recalculation
- [ ] `CostService::calculateItemCost()` — live item cost from recipe
- [ ] Hook `StockService::deductForOrder()` into `OrderService::confirm()`
- [ ] `GET /inv/stock/item-check` — POS stock warning API

### Backend APIs
- [ ] All `/inv/materials` endpoints
- [ ] All `/inv/suppliers` endpoints
- [ ] All `/inv/purchases` endpoints
- [ ] All `/inv/stock` endpoints
- [ ] All `/inv/recipes` endpoints
- [ ] All `/inv/cost` and `/inv/profit` endpoints

### Frontend Pages
- [ ] `inv-materials.html` — with stock badge colours
- [ ] `inv-mat-categories.html`
- [ ] `inv-suppliers.html`
- [ ] `inv-purchases.html` — dynamic item rows, live total calc
- [ ] `inv-stock.html` — ledger table with filters
- [ ] `inv-adjustment.html` — adjustment form
- [ ] `inv-recipes.html` — searchable dropdowns, live cost preview
- [ ] `inv-cost.html` — margin table with colour thresholds
- [ ] `inv-profit.html` — date range, chart, export
- [ ] Update `restaurant-dashboard.html` — add stock alert widget
- [ ] Update `pos.html` — stock check on item load, visual badges

---

## 16. Future Enhancements

| Feature                     | Notes                                                             |
|-----------------------------|-------------------------------------------------------------------|
| FIFO costing method         | Track individual purchase batches, deduct oldest first           |
| Expiry management           | Add `expiry_date` to purchase items; alert on expiry approach    |
| Central warehouse           | Multi-location stock transfer between branches                   |
| Supplier due management     | Aging report, reminder system for overdue supplier payments      |
| Physical stock count        | Periodic count sheet, variance report vs. ledger                 |
| Ingredient-level reporting  | Which ingredient costs the most, usage trends over time          |
| Auto reorder                | Create purchase suggestion when stock hits min_stock level       |

---

*End of Specification — Inventory, Purchase & Costing Module v1.0.0*
