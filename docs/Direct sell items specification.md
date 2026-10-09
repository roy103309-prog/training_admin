# Direct Sell Items — Specification

**Project:** Hotel & Restaurant Management System (Multi-Tenant SaaS)
**Module:** Inventory — Direct Sell Item Extension
**Version:** 1.0.0
**Depends On:** Restaurant Module v1.0.0, POS Module v2.0.0, Inventory Module v1.0.0
**Status:** Draft

---

## 1. Overview

### What Is a Direct Sell Item?

A direct sell item is a product that is **purchased from a supplier and sold as-is** — no kitchen processing, no recipe, no preparation. The item itself is the stock unit.

**Examples:**

| Item           | Purchased As     | Sold As          | Deduction Per Sale |
|----------------|------------------|------------------|--------------------|
| Pepsi 250ml    | pcs (24-pack box)| 1 pcs per order  | 1.0000 pcs         |
| Orange Juice   | liter (bottle)   | 1 glass = 250ml  | 0.2500 liter       |
| Mineral Water  | pcs              | 1 bottle         | 1.0000 pcs         |
| Chips (packet) | pcs              | 1 packet         | 1.0000 pcs         |
| Cigarette      | pcs (per stick)  | 1 stick          | 1.0000 pcs         |
| Energy Drink   | pcs              | 1 can            | 1.0000 pcs         |

### How It Differs from Recipe Items

```
Recipe Item Flow:
  Order Confirmed
    → Loop recipe ingredients
    → Deduct each raw material by (recipe_qty × order_qty)

Direct Sell Item Flow:
  Order Confirmed
    → Read material_id from menu item
    → Deduct material stock by (sell_unit_qty × order_qty)
```

The menu item **is** the raw material. No recipe table is involved.

---

## 2. Scope of Changes

This extension touches exactly **4 layers**:

| Layer    | What Changes                                                         |
|----------|----------------------------------------------------------------------|
| Database | 1 ALTER TABLE on `restaurant_items`                                  |
| Backend  | 3 functions modified: deduct, cost sync, stock check                 |
| Frontend | 2 screens updated: Food Items CRUD, Recipe Builder                   |
| POS      | 0 changes — transparent to cashier                                   |

No new tables are required. All stock movement uses the existing `inv_stock_ledger`.

---

## 3. Database Changes

### 3.1 Alter `restaurant_items`

```sql
ALTER TABLE restaurant_items
  ADD COLUMN item_sell_type  ENUM('recipe','direct') NOT NULL DEFAULT 'recipe'
    COMMENT 'recipe = kitchen prepared with ingredients; direct = sold as purchased'
    AFTER unit,

  ADD COLUMN material_id     INT UNSIGNED NULL
    COMMENT 'Direct items only — the inv_raw_material that IS this item'
    AFTER item_sell_type,

  ADD COLUMN sell_unit_qty   DECIMAL(10,4) NOT NULL DEFAULT 1.0000
    COMMENT 'Raw material quantity consumed per 1 unit sold. e.g. 1 glass juice = 0.2500 liter'
    AFTER material_id,

  ADD KEY idx_material_link (material_id),

  ADD CONSTRAINT fk_item_material
    FOREIGN KEY (material_id)
    REFERENCES inv_raw_materials(id)
    ON DELETE SET NULL;
```

### 3.2 Column Reference

| Column         | Type                      | Default   | Description                                                 |
|----------------|---------------------------|-----------|-------------------------------------------------------------|
| item_sell_type | ENUM('recipe','direct')   | 'recipe'  | Controls deduction logic at order confirm                   |
| material_id    | INT UNSIGNED NULL         | NULL      | FK to `inv_raw_materials` — only set for direct items       |
| sell_unit_qty  | DECIMAL(10,4)             | 1.0000    | How much of the material 1 sale of this item consumes       |

### 3.3 Example Data After Migration

```sql
-- Existing recipe items — no change needed (defaults apply)
-- item_sell_type='recipe', material_id=NULL, sell_unit_qty=1.0000

-- Direct sell items — set these manually or via the UI
UPDATE restaurant_items SET
  item_sell_type = 'direct',
  material_id    = 14,       -- inv_raw_materials.id for "Pepsi 250ml"
  sell_unit_qty  = 1.0000,
  auto_cost      = 1         -- system will calculate cost from last_purchase_price
WHERE id = 55; -- Pepsi menu item

UPDATE restaurant_items SET
  item_sell_type = 'direct',
  material_id    = 15,       -- inv_raw_materials.id for "Orange Juice (liter)"
  sell_unit_qty  = 0.2500,   -- 1 glass = 250ml = 0.25 liter
  auto_cost      = 1
WHERE id = 56; -- "Fresh OJ Glass" menu item
```

### 3.4 Migration Script

```sql
-- Step 1: Add columns
ALTER TABLE restaurant_items
  ADD COLUMN item_sell_type ENUM('recipe','direct') NOT NULL DEFAULT 'recipe' AFTER unit,
  ADD COLUMN material_id    INT UNSIGNED NULL AFTER item_sell_type,
  ADD COLUMN sell_unit_qty  DECIMAL(10,4) NOT NULL DEFAULT 1.0000 AFTER material_id,
  ADD KEY idx_material_link (material_id),
  ADD CONSTRAINT fk_item_material
    FOREIGN KEY (material_id) REFERENCES inv_raw_materials(id) ON DELETE SET NULL;

-- Step 2: All existing items default to 'recipe' — no data loss
-- Step 3: Manually update or use the UI to set direct items
```

---

## 4. Backend Logic Changes

### 4.1 `deductStockOnOrderConfirm()` — Full Updated Function

This is the core change. The function now branches on `item_sell_type` before looking for a recipe.

```php
function deductStockOnOrderConfirm(int $orderId): void
{
    $order    = RestaurantOrder::findOrFail($orderId);
    $settings = InvSettings::forLocation($order->location_id);

    if (!$settings->auto_deduct_on_confirm) return;

    $orderItems = RestaurantOrderItem::where('order_id', $orderId)->get();

    foreach ($orderItems as $orderItem) {

        $menuItem = RestaurantItem::find($orderItem->item_id);
        if (!$menuItem) continue;

        // ─────────────────────────────────────────────────────────
        // BRANCH A: DIRECT SELL ITEM
        // ─────────────────────────────────────────────────────────
        if ($menuItem->item_sell_type === 'direct') {

            if (!$menuItem->material_id) {
                // Direct item has no material linked — log warning, skip
                Log::warning("Direct item [{$menuItem->name}] has no material_id linked.");
                continue;
            }

            $material  = InvRawMaterial::find($menuItem->material_id);
            $deductQty = $menuItem->sell_unit_qty * $orderItem->quantity;

            // Stock check
            if ($settings->strict_stock) {
                $currentStock = InvStockLedger::currentStock(
                    $menuItem->material_id,
                    $order->location_id
                );
                if ($currentStock < $deductQty) {
                    throw new \Exception(
                        "Out of stock: {$menuItem->name} " .
                        "(Need: {$deductQty} {$material->unit}, " .
                        "Have: {$currentStock} {$material->unit})"
                    );
                }
            }

            // Insert ledger OUT
            InvStockLedger::create([
                'location_id'  => $order->location_id,
                'material_id'  => $menuItem->material_id,
                'entry_type'   => 'OUT',
                'quantity'     => $deductQty,
                'unit_price'   => $material->last_purchase_price,
                'reference'    => 'order',
                'reference_id' => $orderId,
                'note'         => "Direct sell: {$menuItem->name} x{$orderItem->quantity}",
                'created_by'   => auth()->id(),
            ]);

            continue; // done for this item — skip recipe logic below
        }

        // ─────────────────────────────────────────────────────────
        // BRANCH B: RECIPE ITEM
        // ─────────────────────────────────────────────────────────
        $recipe = InvRecipe::where('menu_item_id', $orderItem->item_id)
                           ->where('location_id', $order->location_id)
                           ->with('material')
                           ->get();

        if ($recipe->isEmpty()) {
            if ($settings->strict_recipe) {
                throw new \Exception(
                    "No recipe defined for item: {$orderItem->item_name}"
                );
            }
            // Not strict — skip silently
            Log::info("No recipe for [{$orderItem->item_name}] — stock not deducted.");
            continue;
        }

        foreach ($recipe as $ingredient) {
            $deductQty = $ingredient->quantity * $orderItem->quantity;

            if ($settings->strict_stock) {
                $currentStock = InvStockLedger::currentStock(
                    $ingredient->material_id,
                    $order->location_id
                );
                if ($currentStock < $deductQty) {
                    throw new \Exception(
                        "Insufficient stock: {$ingredient->material->name} " .
                        "(Need: {$deductQty} {$ingredient->material->unit}, " .
                        "Have: {$currentStock} {$ingredient->material->unit})"
                    );
                }
            }

            InvStockLedger::create([
                'location_id'  => $order->location_id,
                'material_id'  => $ingredient->material_id,
                'entry_type'   => 'OUT',
                'quantity'     => $deductQty,
                'unit_price'   => $ingredient->material->last_purchase_price,
                'reference'    => 'order',
                'reference_id' => $orderId,
                'note'         => "Recipe deduct: {$orderItem->item_name}",
                'created_by'   => auth()->id(),
            ]);
        }
    }
}
```

---

### 4.2 `syncItemCosts()` — Updated Function

Cost calculation now handles both item types in a single pass.

```php
function syncItemCosts(int $locationId): void
{
    $items = RestaurantItem::where('location_id', $locationId)
                           ->where('auto_cost', 1)
                           ->with('material')
                           ->get();

    foreach ($items as $item) {

        // ── DIRECT SELL: cost = last_purchase_price × sell_unit_qty
        if ($item->item_sell_type === 'direct') {
            if (!$item->material_id || !$item->material) continue;

            $item->cost_price   = round(
                $item->material->last_purchase_price * $item->sell_unit_qty,
                2
            );
            $item->last_cost_at = now();
            $item->save();
            continue;
        }

        // ── RECIPE ITEM: cost = sum of (ingredient_qty × last_purchase_price)
        $recipe = InvRecipe::where('menu_item_id', $item->id)
                           ->where('location_id', $locationId)
                           ->with('material')
                           ->get();

        if ($recipe->isEmpty()) continue;

        $item->cost_price   = round(
            $recipe->sum(fn($r) => $r->quantity * $r->material->last_purchase_price),
            2
        );
        $item->last_cost_at = now();
        $item->save();
    }
}
```

---

### 4.3 `/inv/stock/check` — Updated Endpoint

The POS calls this before adding an item to cart. It must now handle direct items too.

```php
// GET /inv/stock/check?item_id=55&qty=2&location_id=1
function stockCheck(Request $request): JsonResponse
{
    $menuItem   = RestaurantItem::findOrFail($request->item_id);
    $orderQty   = (float) $request->qty;
    $locationId = (int)   $request->location_id;
    $settings   = InvSettings::forLocation($locationId);

    // ── DIRECT SELL ──────────────────────────────────────────────
    if ($menuItem->item_sell_type === 'direct') {

        if (!$menuItem->material_id) {
            // No material linked — cannot check stock, allow sale
            return response()->json(['status' => 'ok']);
        }

        $material     = InvRawMaterial::find($menuItem->material_id);
        $required     = $menuItem->sell_unit_qty * $orderQty;
        $currentStock = InvStockLedger::currentStock($menuItem->material_id, $locationId);

        return response()->json([
            'status'        => resolveStockStatus($currentStock, $required, $material->min_stock, $settings),
            'material_name' => $menuItem->name,         // show item name on POS warning
            'current_stock' => $currentStock,
            'required'      => $required,
            'unit'          => $material->unit,
        ]);
    }

    // ── RECIPE ITEM ───────────────────────────────────────────────
    $recipe = InvRecipe::where('menu_item_id', $menuItem->id)
                       ->where('location_id', $locationId)
                       ->with('material')
                       ->get();

    if ($recipe->isEmpty()) {
        return response()->json(['status' => 'ok']); // no recipe = no check
    }

    // Find the most critically low ingredient
    $worstStatus   = 'ok';
    $worstMaterial = null;
    $worstStock    = null;
    $worstRequired = null;
    $worstUnit     = null;

    foreach ($recipe as $ingredient) {
        $required     = $ingredient->quantity * $orderQty;
        $currentStock = InvStockLedger::currentStock($ingredient->material_id, $locationId);
        $status       = resolveStockStatus(
            $currentStock, $required, $ingredient->material->min_stock, $settings
        );

        // Escalate: out > low > ok
        if ($status === 'out' || ($status === 'low' && $worstStatus === 'ok')) {
            $worstStatus   = $status;
            $worstMaterial = $ingredient->material->name;
            $worstStock    = $currentStock;
            $worstRequired = $required;
            $worstUnit     = $ingredient->material->unit;
        }

        if ($worstStatus === 'out') break; // no point checking further
    }

    return response()->json([
        'status'        => $worstStatus,
        'material_name' => $worstMaterial,
        'current_stock' => $worstStock,
        'required'      => $worstRequired,
        'unit'          => $worstUnit,
    ]);
}

// Helper
function resolveStockStatus(
    float $current, float $required, float $minStock, InvSettings $settings
): string {
    if ($current < $required) return 'out';
    if ($current <= $minStock) return 'low';
    return 'ok';
}
```

---

### 4.4 `calculateItemCost()` — Updated Helper

```php
function calculateItemCost(int $menuItemId, int $locationId): float
{
    $item = RestaurantItem::find($menuItemId);
    if (!$item) return 0.0;

    // Direct sell: cost = price × qty per sale
    if ($item->item_sell_type === 'direct') {
        if (!$item->material_id) return 0.0;
        $material = InvRawMaterial::find($item->material_id);
        return round($material->last_purchase_price * $item->sell_unit_qty, 2);
    }

    // Recipe item: sum of ingredients
    $recipe = InvRecipe::where('menu_item_id', $menuItemId)
                       ->where('location_id', $locationId)
                       ->with('material')
                       ->get();

    if ($recipe->isEmpty()) return 0.0;

    return round(
        $recipe->sum(fn($r) => $r->quantity * $r->material->last_purchase_price),
        2
    );
}
```

---

### 4.5 Order Cancel Reversal — No Change Required

`cancelOrderStock()` reads from `inv_stock_ledger` by `reference='order'` and inserts `ADJUST_IN` reversals. Since direct-sell items already write to the ledger using the same format, the cancellation function works without any modification.

---

## 5. Frontend Changes

### 5.1 Food Items CRUD (`restaurant-items.html`) — Updated Form

Add `item_sell_type` toggle and conditional fields. The form already has category, name, price, etc. Add these fields:

```html
<!-- Sell Type Toggle — add after unit field -->
<div class="mb-3">
  <label class="form-label fw-semibold">Item Type</label>
  <div class="btn-group w-100" role="group">
    <input type="radio" class="btn-check" name="item_sell_type"
           id="typeRecipe" value="recipe" checked>
    <label class="btn btn-outline-primary" for="typeRecipe">
      🍳 Recipe Item
    </label>

    <input type="radio" class="btn-check" name="item_sell_type"
           id="typeDirect" value="direct">
    <label class="btn btn-outline-success" for="typeDirect">
      📦 Direct Sell
    </label>
  </div>
  <small class="text-muted d-block mt-1" id="sellTypeHint">
    Recipe Item: prepared in kitchen from raw ingredients.
  </small>
</div>

<!-- Direct Sell Fields — hidden by default -->
<div id="directSellFields" style="display:none;">
  <div class="mb-3">
    <label class="form-label">Linked Raw Material
      <span class="text-danger">*</span>
    </label>
    <select class="form-select searchable-select" name="material_id" id="materialId">
      <option value="">— Select Material —</option>
      <!-- populated via /api/inv/materials?location_id=X -->
    </select>
    <small class="text-muted">
      The raw material that represents this item in stock.
      Must be purchased via Purchase Entry first.
    </small>
  </div>

  <div class="mb-3">
    <label class="form-label">Stock Consumed Per Sale</label>
    <div class="input-group">
      <input type="number" class="form-control" name="sell_unit_qty"
             id="sellUnitQty" value="1" min="0.0001" step="0.0001">
      <span class="input-group-text" id="unitLabel">pcs</span>
    </div>
    <small class="text-muted">
      How much of the material is used when 1 unit of this item is sold.
      Example: 1 glass of juice = 0.25 liter — enter 0.25.
    </small>
  </div>

  <!-- Live cost preview -->
  <div class="alert alert-info py-2 px-3 mb-3" id="directCostPreview"
       style="display:none;">
    <small>
      Estimated Cost:
      <strong id="directCostValue">৳0.00</strong>
      <span class="text-muted">
        (last purchase price × qty per sale)
      </span>
    </small>
  </div>
</div>
```

**JavaScript for the form:**

```javascript
// Toggle direct sell fields
document.querySelectorAll('input[name="item_sell_type"]').forEach(radio => {
  radio.addEventListener('change', function () {
    const isDirect = this.value === 'direct';
    document.getElementById('directSellFields').style.display =
      isDirect ? 'block' : 'none';
    document.getElementById('sellTypeHint').textContent = isDirect
      ? 'Direct Sell: purchased from supplier and sold as-is. No kitchen prep.'
      : 'Recipe Item: prepared in kitchen from raw ingredients.';
  });
});

// When material selected — update unit label + show cost preview
document.getElementById('materialId').addEventListener('change', async function () {
  const materialId = this.value;
  if (!materialId) return;

  const res  = await fetch(`/api/inv/materials/${materialId}`);
  const data = await res.json();

  document.getElementById('unitLabel').textContent = data.unit;
  updateDirectCostPreview(data.last_purchase_price);
});

// When qty changed — update cost preview
document.getElementById('sellUnitQty').addEventListener('input', function () {
  const materialSelect = document.getElementById('materialId');
  if (!materialSelect.value) return;
  updateDirectCostPreview(window.selectedMaterialPrice || 0);
});

function updateDirectCostPreview(unitPrice) {
  window.selectedMaterialPrice = unitPrice;
  const qty      = parseFloat(document.getElementById('sellUnitQty').value) || 1;
  const cost     = (unitPrice * qty).toFixed(2);
  const preview  = document.getElementById('directCostPreview');
  preview.style.display = 'block';
  document.getElementById('directCostValue').textContent = '৳' + cost;
}

// On form submit — validate direct sell fields
function validateItemForm() {
  const isDirect = document.querySelector('input[name="item_sell_type"]:checked').value === 'direct';
  if (isDirect && !document.getElementById('materialId').value) {
    showToast('Please select a linked material for direct sell item.', 'danger');
    return false;
  }
  return true;
}
```

**In items list/table — add a type badge column:**

```html
<!-- In items table, after name column -->
<td>
  {% if item.item_sell_type == 'direct' %}
    <span class="badge bg-success">📦 Direct</span>
  {% else %}
    <span class="badge bg-primary">🍳 Recipe</span>
  {% endif %}
</td>
```

---

### 5.2 Recipe Builder (`inv-recipes.html`) — Updated

When a direct sell item is selected, hide the ingredient table and show a linked material summary instead.

```javascript
// When menu item selected in recipe builder
async function onMenuItemSelect(menuItemId) {
  const res  = await fetch(`/api/restaurant/items/${menuItemId}`);
  const item = await res.json();

  if (item.item_sell_type === 'direct') {
    showDirectSellSummary(item);
  } else {
    showRecipeBuilder(menuItemId);
  }
}

function showDirectSellSummary(item) {
  document.getElementById('recipeBuilderSection').style.display = 'none';
  document.getElementById('directSummarySection').style.display = 'block';

  document.getElementById('directSummaryContent').innerHTML = `
    <div class="alert alert-success mb-0">
      <h6 class="mb-2">📦 This is a Direct Sell Item</h6>
      <table class="table table-sm mb-0">
        <tr>
          <td class="text-muted">Linked Material</td>
          <td><strong>${item.material_name}</strong></td>
        </tr>
        <tr>
          <td class="text-muted">Consumed Per Sale</td>
          <td><strong>${item.sell_unit_qty} ${item.material_unit}</strong></td>
        </tr>
        <tr>
          <td class="text-muted">Last Purchase Price</td>
          <td><strong>৳${item.material_last_price}</strong></td>
        </tr>
        <tr>
          <td class="text-muted">Estimated Cost</td>
          <td><strong>৳${item.cost_price}</strong></td>
        </tr>
        <tr>
          <td class="text-muted">Sell Price</td>
          <td><strong>৳${item.sell_price}</strong></td>
        </tr>
        <tr>
          <td class="text-muted">Margin</td>
          <td><strong>${calcMargin(item.sell_price, item.cost_price)}%</strong></td>
        </tr>
      </table>
      <a href="restaurant-items.html?edit=${item.id}" class="btn btn-sm btn-outline-success mt-3">
        ✏️ Edit Material Link
      </a>
    </div>
  `;
}

function calcMargin(sell, cost) {
  if (!sell || sell == 0) return '0.00';
  return (((sell - cost) / sell) * 100).toFixed(2);
}
```

**HTML additions in `inv-recipes.html`:**

```html
<!-- Add alongside existing recipe builder section -->
<div id="directSummarySection" style="display:none;">
  <div id="directSummaryContent"></div>
</div>

<div id="recipeBuilderSection">
  <!-- existing recipe ingredient table stays here unchanged -->
</div>
```

---

### 5.3 Stock Dashboard (`inv-stock.html`) — Visual Indicator

In the materials list, show which materials are linked to direct-sell items. This helps the manager understand what stock will be consumed by direct sales.

```javascript
// After loading materials, check for direct-sell links
async function markDirectSellMaterials() {
  const res   = await fetch(`/api/restaurant/items?location_id=${LOCATION_ID}&sell_type=direct`);
  const items = await res.json();

  // Build lookup: material_id → item names
  const lookup = {};
  items.forEach(item => {
    if (!lookup[item.material_id]) lookup[item.material_id] = [];
    lookup[item.material_id].push(item.name);
  });

  // Annotate rows
  document.querySelectorAll('[data-material-id]').forEach(row => {
    const mid = row.dataset.materialId;
    if (lookup[mid]) {
      const cell = row.querySelector('.material-name-cell');
      cell.innerHTML +=
        `<br><small class="text-success">📦 ${lookup[mid].join(', ')}</small>`;
    }
  });
}
```

---

## 6. API Changes

### 6.1 Updated Endpoints

| Endpoint                        | Change                                                                |
|---------------------------------|-----------------------------------------------------------------------|
| `POST /restaurant/items`        | Accept `item_sell_type`, `material_id`, `sell_unit_qty`               |
| `PUT  /restaurant/items/{id}`   | Accept `item_sell_type`, `material_id`, `sell_unit_qty`               |
| `GET  /restaurant/items/{id}`   | Return `item_sell_type`, `material_id`, `sell_unit_qty`, `material_name`, `material_unit`, `material_last_price` |
| `GET  /restaurant/items`        | Accept `?sell_type=direct\|recipe` filter param                       |
| `GET  /inv/stock/check`         | Updated to handle `direct` items (see Section 4.3)                   |
| `POST /restaurant/orders/{id}/confirm` | Updated deduction hook (see Section 4.1)                      |

### 6.2 Food Item Save/Update Payload

```json
{
  "category_id":    3,
  "name":           "Pepsi 250ml",
  "sell_price":     40.00,
  "unit":           "pcs",
  "item_sell_type": "direct",
  "material_id":    14,
  "sell_unit_qty":  1.0000,
  "auto_cost":      1,
  "is_veg":         0,
  "is_available":   1
}
```

### 6.3 Food Item GET Response (direct item)

```json
{
  "id":                   55,
  "name":                 "Pepsi 250ml",
  "sell_price":           40.00,
  "cost_price":           28.00,
  "unit":                 "pcs",
  "item_sell_type":       "direct",
  "material_id":          14,
  "sell_unit_qty":        1.0000,
  "auto_cost":            1,
  "material_name":        "Pepsi 250ml Can",
  "material_unit":        "pcs",
  "material_last_price":  28.00
}
```

---

## 7. Purchase Flow for Direct Sell Items

No new purchase process is needed. Direct sell items are purchased through the same `inv_purchases` screen as any raw material.

**Step-by-step:**

```
1. Go to Inventory → Purchases → New Purchase
2. Select supplier (e.g., Beverage Distributor)
3. Add material: "Pepsi 250ml Can" — Qty: 48 pcs — Price: ৳28 each
4. Confirm Purchase
5. System:
   → INSERT inv_stock_ledger (IN, material_id=14, quantity=48, unit_price=28)
   → UPDATE inv_raw_materials SET last_purchase_price = 28 WHERE id = 14
   → UPDATE restaurant_items SET cost_price = 28 × 1.0 = 28.00 WHERE material_id = 14 AND auto_cost = 1
6. Stock Dashboard now shows: Pepsi 250ml Can — 48 pcs ✅ OK
```

**When 3 Pepsi are sold on an order:**

```
Order Confirmed
  → item_sell_type = 'direct'
  → material_id = 14, sell_unit_qty = 1.0
  → deduct = 1.0 × 3 = 3 pcs
  → INSERT inv_stock_ledger (OUT, material_id=14, quantity=3)
Stock: 48 - 3 = 45 pcs
```

---

## 8. Profit Calculation for Direct Sell Items

No changes needed to the profit report query. The query already uses `restaurant_items.cost_price` which is auto-calculated for direct items just like recipe items.

For transparency, the profit report should show the item type badge:

```sql
-- Profit per item query — add item_sell_type to SELECT
SELECT
  ri.name,
  ri.item_sell_type,
  ri.sell_price,
  ri.cost_price,
  ri.sell_price - ri.cost_price AS unit_profit,
  SUM(oi.quantity)              AS total_sold,
  SUM(oi.line_total)            AS total_revenue,
  SUM(oi.quantity * ri.cost_price) AS total_cost,
  SUM(oi.line_total) - SUM(oi.quantity * ri.cost_price) AS gross_profit
FROM restaurant_order_items oi
JOIN restaurant_items ri ON ri.id = oi.item_id
JOIN restaurant_orders o ON o.id = oi.order_id
WHERE o.location_id  = ?
  AND o.order_status = 'completed'
  AND DATE(o.created_at) BETWEEN ? AND ?
GROUP BY ri.id
ORDER BY gross_profit DESC;
```

---

## 9. Business Rules

| Rule   | Description                                                                               |
|--------|-------------------------------------------------------------------------------------------|
| BR-01  | A `direct` item must have `material_id` set before it can be added to an active category |
| BR-02  | A `direct` item does not have and must not use a recipe in `inv_recipes`                  |
| BR-03  | Changing `item_sell_type` from `direct` to `recipe` clears `material_id` and `sell_unit_qty` after confirmation prompt |
| BR-04  | Changing `item_sell_type` from `recipe` to `direct` does not delete existing recipe rows — they are ignored while type is `direct` |
| BR-05  | `sell_unit_qty` must be greater than 0                                                    |
| BR-06  | `auto_cost=1` on a direct item: cost auto-updates when linked material's `last_purchase_price` changes |
| BR-07  | `auto_cost=0` on a direct item: cost was manually entered and is not overwritten          |
| BR-08  | Deleting a raw material that is linked to a direct sell item sets `material_id = NULL` (FK ON DELETE SET NULL) — the item remains but stock check is skipped |
| BR-09  | Order cancel reversal works identically for direct and recipe items — both use the same ledger format |
| BR-10  | POS shows the menu item name (not material name) in stock warnings for direct items       |
| BR-11  | Kitchen token does NOT print direct sell items — they require no kitchen preparation      |
| BR-12  | `strict_recipe` setting does not apply to direct items — they are never expected to have a recipe |

---

## 10. Kitchen Token — Direct Items Excluded

Direct sell items go straight from cashier to customer — they never need a KOT. Update the kitchen token template to skip them:

```php
function renderKitchenTokenTemplate(array $order): string
{
    // Filter: only recipe items need kitchen preparation
    $kitchenItems = array_filter(
        $order['items'],
        fn($item) => $item['item_sell_type'] === 'recipe'
    );

    if (empty($kitchenItems)) {
        return ''; // No kitchen items — do not print KOT at all
    }

    // ... rest of existing KOT template using $kitchenItems
}
```

**On POS place order — only print KOT if there are recipe items:**

```javascript
async function placeOrder() {
  const data = await submitOrder(buildOrderPayload());
  if (!data.success) return;

  // Always print bill (Printer 1)
  if (window.currentSellType === 'pay_fast') {
    printBillInvoice(data.order);
  }

  // Only print KOT (Printer 2) if order has recipe items
  if (data.order.has_recipe_items) {
    printKitchenToken(data.order);
  }

  redirect();
}
```

**API response should include the flag:**

```php
// In order create/confirm response
$order['has_recipe_items'] = collect($order['items'])
    ->contains(fn($i) => $i['item_sell_type'] === 'recipe');
```

---

## 11. Validation Rules

| Field          | Rule                                                              |
|----------------|-------------------------------------------------------------------|
| item_sell_type | Required; must be 'recipe' or 'direct'                           |
| material_id    | Required if `item_sell_type = 'direct'`; must exist in `inv_raw_materials` for same location |
| sell_unit_qty  | Required if `item_sell_type = 'direct'`; must be > 0             |
| material_id    | Must be NULL if `item_sell_type = 'recipe'` (enforced on save)   |

---

## 12. Implementation Checklist

### Database
- [ ] Run `ALTER TABLE restaurant_items` — add `item_sell_type`, `material_id`, `sell_unit_qty`
- [ ] Verify all existing items defaulted to `item_sell_type = 'recipe'`
- [ ] Manually update or use UI to tag known direct sell items

### Backend
- [ ] Update `deductStockOnOrderConfirm()` with `direct` branch
- [ ] Update `syncItemCosts()` to handle `direct` items
- [ ] Update `calculateItemCost()` helper
- [ ] Update `/inv/stock/check` endpoint
- [ ] Update `GET /restaurant/items/{id}` to return material fields
- [ ] Update `POST/PUT /restaurant/items` to accept new fields
- [ ] Add `?sell_type=` filter to `GET /restaurant/items`
- [ ] Update kitchen token builder — exclude direct items
- [ ] Add `has_recipe_items` flag to order create/confirm response

### Frontend — Food Items (`restaurant-items.html`)
- [ ] Add `item_sell_type` radio toggle to add/edit form
- [ ] Add `material_id` searchable select (conditional — direct only)
- [ ] Add `sell_unit_qty` number input with unit label (conditional)
- [ ] Add live cost preview for direct items
- [ ] Add type badge column to items list table
- [ ] Add `validateItemForm()` before submit

### Frontend — Recipe Builder (`inv-recipes.html`)
- [ ] Detect `item_sell_type` on menu item select
- [ ] Show `directSummarySection` for direct items
- [ ] Hide ingredient builder for direct items
- [ ] Show material link, qty per sale, cost, margin in summary

### Frontend — Stock Dashboard (`inv-stock.html`)
- [ ] Annotate material rows that are linked to direct-sell menu items
- [ ] Call `markDirectSellMaterials()` after materials table renders

### POS (`pos.html`)
- [ ] No changes to UI
- [ ] Confirm `checkStockBeforeAdd()` calls updated `/inv/stock/check` endpoint
- [ ] Confirm KOT print is conditional on `has_recipe_items`

---

## 13. Testing Scenarios

| Scenario | Expected Result |
|----------|-----------------|
| Purchase 48 Pepsi pcs | Stock: +48. `last_purchase_price` updated. Menu item `cost_price` auto-recalculated |
| Sell 3 Pepsi on Pay Fast order | Stock: -3 pcs. Ledger OUT entry with `reference='order'` |
| Sell Chicken Curry (recipe) + Pepsi (direct) on same order | Recipe ingredients deducted for curry. Material deducted for Pepsi. KOT prints only curry |
| Cancel order with Pepsi | ADJUST_IN reversal: +3 pcs restored |
| strict_stock=1, only 1 Pepsi left, customer orders 2 | Order confirm blocked with "Out of stock: Pepsi 250ml" |
| strict_stock=0, only 1 Pepsi left, customer orders 2 | Order confirms with warning logged. Stock goes to -1 |
| direct item with no material_id linked | Stock check returns 'ok'. No ledger entry. Warning logged |
| strict_recipe=1, direct item has no recipe | No error — strict_recipe does not apply to direct items |
| Change item from direct to recipe | material_id and sell_unit_qty cleared after prompt. Existing recipe rows (if any) become active again |
| Open Recipe Builder on a direct item | Shows summary panel with material link. Recipe builder hidden |

---

*End of Specification — Direct Sell Items Extension v1.0.0*