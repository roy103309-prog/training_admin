# POS Module — Full Specification

**Project:** Hotel & Restaurant Management System (Multi-Tenant SaaS)
**Module:** Point of Sale (POS)
**Version:** 2.0.0
**Status:** Draft

---

## 1. Overview

This document defines the complete POS module redesign including:

- Full-width, sidebar-free POS layout
- Two distinct sell flows: **Pay Fast** and **Pay Later**
- Serve page for Pay Fast order confirmation
- Ongoing page for Pay Later dine-in order management
- Due Invoices page for pending payment tracking
- Kitchen token printing
- Dual printer support
- Searchable dropdowns
- Audio feedback on cart interaction

---

## 2. Pages in This Module

| Page File              | Description                                      |
|------------------------|--------------------------------------------------|
| `pos.html`             | Main POS screen (full width, no sidebar)         |
| `pos-serve.html`       | Serve queue for Pay Fast orders                  |
| `pos-ongoing.html`     | Ongoing dine-in orders (Pay Later)               |
| `pos-due.html`         | Due invoices awaiting payment                    |

> Remove `restaurant-kitchen.html` from menus — kitchen output is now handled via printed kitchen token only.

---

## 3. Menu Entries Update

### 3.1 Remove

```sql
-- Remove kitchen display from menus table
UPDATE menus SET isactive = 0 WHERE url = 'restaurant-kitchen.html';
```

### 3.2 Add / Update

```sql
INSERT INTO menus (name, url, icon, parent_id, sort_order, isactive) VALUES
('POS',           'pos.html',          'bx-cart',          @restaurant_parent, 1, 1),
('Serve Queue',   'pos-serve.html',    'bx-check-circle',  @restaurant_parent, 2, 1),
('Ongoing Orders','pos-ongoing.html',  'bx-time',          @restaurant_parent, 3, 1),
('Due Invoices',  'pos-due.html',      'bx-receipt',       @restaurant_parent, 4, 1);
```

---

## 4. Database Schema Changes

### 4.1 New Column on `restaurant_orders`

```sql
ALTER TABLE restaurant_orders
  ADD COLUMN sell_type       ENUM('pay_fast','pay_later') NOT NULL DEFAULT 'pay_fast' AFTER order_type,
  ADD COLUMN serve_status    ENUM('waiting','confirmed')  NOT NULL DEFAULT 'waiting'  AFTER sell_type,
  ADD COLUMN token_no        VARCHAR(20) NULL                                          AFTER serve_status,
  ADD COLUMN paid_amount     DECIMAL(10,2) NOT NULL DEFAULT 0.00                      AFTER final_amount,
  ADD COLUMN due_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00                      AFTER paid_amount,
  ADD COLUMN completed_at    DATETIME NULL                                             AFTER updated_at,
  ADD KEY idx_sell_type  (sell_type),
  ADD KEY idx_serve_status (serve_status);
```

### 4.2 New Table: `pos_settings`

Per-location POS configuration including dual printer setup.

```sql
CREATE TABLE IF NOT EXISTS pos_settings (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id         INT UNSIGNED NOT NULL,

  -- Printer 1 (Bill / Invoice Printer)
  printer1_name       VARCHAR(150) NULL COMMENT 'Windows printer name or IP',
  printer1_label      VARCHAR(100) NULL DEFAULT 'Bill Printer',
  printer1_type       ENUM('thermal','laser','inkjet') NOT NULL DEFAULT 'thermal',
  printer1_paper_size ENUM('58mm','80mm','a4') NOT NULL DEFAULT '80mm',

  -- Printer 2 (Kitchen Token Printer)
  printer2_name       VARCHAR(150) NULL,
  printer2_label      VARCHAR(100) NULL DEFAULT 'Kitchen Printer',
  printer2_type       ENUM('thermal','laser','inkjet') NOT NULL DEFAULT 'thermal',
  printer2_paper_size ENUM('58mm','80mm','a4') NOT NULL DEFAULT '58mm',

  -- POS Behaviour
  beep_sound_file     VARCHAR(255) NULL DEFAULT 'assets/mp3/beep.mp3',
  default_sell_type   ENUM('pay_fast','pay_later') NOT NULL DEFAULT 'pay_fast',
  token_prefix        VARCHAR(10)  NOT NULL DEFAULT 'TKN',
  tax_on_pos          TINYINT(1)   NOT NULL DEFAULT 1,
  allow_discount      TINYINT(1)   NOT NULL DEFAULT 1,

  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.3 Token Number Generation

```php
// Format: TKN-001 (sequential per day per location, prefix from pos_settings)
function generateTokenNo(int $locationId): string {
    $prefix = PosSettings::getPrefix($locationId); // e.g. 'TKN'
    $date   = date('Ymd');
    $last   = DB::scalar(
        "SELECT token_no FROM restaurant_orders
         WHERE location_id = ? AND DATE(created_at) = CURDATE()
           AND token_no IS NOT NULL
         ORDER BY id DESC LIMIT 1",
        [$locationId]
    );
    $seq = $last ? (int) preg_replace('/\D/', '', substr($last, -3)) + 1 : 1;
    return $prefix . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    // e.g. TKN-001, TKN-002
}
```

---

## 5. Page: POS (`pos.html`)

### 5.1 Layout

```
┌──────────────────────────────────────────────────────────────────────┐
│  BROWSER / APP FULL WIDTH — NO SIDEBAR                               │
│                                                                      │
│  ┌───────────────────────────────────────┐  ┌──────────────────────┐│
│  │         ITEMS SECTION  (65%)          │  │  CART SECTION (35%)  ││
│  │                                       │  │                      ││
│  │  [Category            scrollable]     │  │                      ││
│  │  [Search items...        🔍]          │  │       ││
│  │                                       │  │                      ││
│  │  ┌──────────────────────────────────┐ │  │  Place: [Dropdown▼]  ││
│  │  │  ITEM CARDS (5 per row, scroll)  │ │  │  Table: [Dropdown▼]  ││
│  │  │  ┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐      │ │  │  ─────────────────── ││
│  │  │  └──┘ └──┘ └──┘ └──┘ └──┘      │ │  │  Customer: [Search🔍] ││
│  │  │  ┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐      │ │  │                      ││
│  │  │  └──┘ └──┘ └──┘ └──┘ └──┘      │ │  │  ─────────────────── ││
│  │  │  ...scroll...                    │ │  │  Cart items list     ││
│  │  └──────────────────────────────────┘ │  │  (scrollable)        ││
│  └───────────────────────────────────────┘  │  ─────────────────── ││
│                                             │  Note: [textarea]    ││
│                                             │  ─────────────────── ││
│                                             │  Subtotal:   ৳0      ││
│                                             │  Discount:   ৳0      ││
│                                             │  Tax:        ৳0      ││
│                                             │  Total:      ৳0      ││
│                                             │                      ││
│                                             └──────────────────────┘│
└──────────────────────────────────────────────────────────────────────┘
```
│ FIXED ACTION BAR (BOTTOM - ALWAYS VISIBLE) │ │ [Ongoing] [To Serve] [Place Order] [Pay Now]
- show alert for place order.
### 5.2 HTML / CSS Rules

```css
/* Remove sidebar, fill full viewport */
body.pos-page .sidebar       { display: none !important; }
body.pos-page .main-wrapper  { margin-left: 0 !important; width: 100vw; }
body.pos-page .page-title    { display: none !important; }
body.pos-page                { overflow: hidden; }

/* Two-column wrapper */
.pos-wrapper {
  display: flex;
  height: 100vh;
  gap: 0;
}

/* Items section */
.pos-items {
  flex: 0 0 65%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 12px;
  border-right: 1px solid var(--border-color);
}

.pos-items-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);   /* 5 cards per row */
  gap: 10px;
  overflow-y: auto;
  flex: 1;
  padding-right: 4px;
}

/* Item card — fixed size */
.item-card {
  width: 100%;
  height: 160px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: box-shadow 0.2s;
}
.item-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
.item-card .card-img {
  height: 80px;
  object-fit: cover;
  width: 100%;
  background: #f5f5f5;
}
.item-card .card-body {
  padding: 6px 8px;
  flex: 1;
}
.item-card .card-name  { font-size: 12px; font-weight: 600; line-height: 1.2; }
.item-card .card-price { font-size: 11px; color: #555; }
.item-card .btn-add {
  width: 100%;
  border-radius: 0;
  font-size: 12px;
  padding: 5px 0;
}

/* Cart section */
.pos-cart {
  flex: 0 0 35%;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  padding: 12px;
}
.pos-cart .cart-items-list {
  flex: 1;
  overflow-y: auto;
}
```

### 5.3 Sell Type Toggle

```html
<!-- Top of cart section -->
<div class="btn-group w-100 mb-2">
  <button class="btn btn-sm btn-outline-primary active" id="btnPayFast"
          onclick="setSellType('pay_fast')">⚡ Pay Fast</button>
  <button class="btn btn-sm btn-outline-warning" id="btnPayLater"
          onclick="setSellType('pay_later')">🪑 Pay Later</button>
</div>
```

**Pay Fast** — default. Shows billing fields (discount, paid amount, due) before placing.
**Pay Later** — dine-in only. Requires table selection. Billing happens at completion.

```javascript
function setSellType(type) {
  window.currentSellType = type;
  if (type === 'pay_later') {
    document.getElementById('tableRow').style.display = 'block'; // table required
    document.getElementById('billingFields').style.display = 'none';
  } else {
    document.getElementById('tableRow').style.display = 'block'; // optional for fast
    document.getElementById('billingFields').style.display = 'block';
  }
}
```

### 5.4 Place & Table Dropdowns

Located **above** customer search. Both support inline search.

```html
<!-- Order Place -->
<div class="mb-2">
  <select class="form-select form-select-sm searchable-select" id="selectPlace"
          name="order_type">
    <option value="">— Select Place —</option>
    <option value="dine-in">Dine In</option>
    <option value="takeaway">Takeaway</option>
    <option value="delivery">Delivery</option>
  </select>
</div>

<!-- Table (shown for dine-in) -->
<div class="mb-2" id="tableRow">
  <select class="form-select form-select-sm searchable-select" id="selectTable"
          name="table_id">
    <option value="">— Select Table —</option>
    <!-- dynamically populated with search -->
  </select>
</div>
```

### 5.5 Customer Search

Single combined search box. Searches both name and mobile simultaneously.

```html
<div class="mb-2 position-relative">
  <input type="text" id="customerSearch" class="form-control form-control-sm"
         placeholder="Search customer (name / mobile)...">
  <div id="customerDropdown" class="customer-results position-absolute w-100 d-none">
    <!-- results appended here -->
  </div>
</div>
<input type="hidden" id="customerId"   name="customer_id">
<input type="hidden" id="customerName" name="customer_name">
<input type="hidden" id="customerPhone" name="customer_phone">
```

```javascript
// Search fires on input with 300ms debounce
async function searchCustomer(q) {
  const res = await fetch(`/api/customers?q=${encodeURIComponent(q)}&location_id=${LOCATION_ID}`);
  const data = await res.json();
  renderCustomerDropdown(data);
}
// On select: fill hidden fields + show selected pill
// Allow "New Customer" option at bottom to create inline
```

### 5.6 Searchable Dropdown (Reusable)

Applied to: Category, Table, Payment Method, and any `<select>` with class `searchable-select`.

```javascript
// Lightweight implementation — no library dependency
function initSearchableSelect(selectEl) {
  const wrapper  = document.createElement('div');
  wrapper.className = 'ss-wrapper position-relative';
  const input    = document.createElement('input');
  input.className = 'form-control form-control-sm ss-input';
  input.placeholder = selectEl.options[0]?.text || 'Search...';
  const list     = document.createElement('ul');
  list.className  = 'ss-list list-unstyled d-none position-absolute w-100 bg-white border rounded shadow-sm';

  selectEl.parentNode.insertBefore(wrapper, selectEl);
  wrapper.appendChild(input);
  wrapper.appendChild(list);
  selectEl.style.display = 'none';
  wrapper.appendChild(selectEl);

  const options = Array.from(selectEl.options).filter(o => o.value);

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    list.innerHTML = '';
    list.classList.remove('d-none');
    options
      .filter(o => o.text.toLowerCase().includes(q))
      .forEach(o => {
        const li = document.createElement('li');
        li.textContent = o.text;
        li.className   = 'px-3 py-1 ss-item';
        li.addEventListener('click', () => {
          selectEl.value = o.value;
          input.value    = o.text;
          list.classList.add('d-none');
          selectEl.dispatchEvent(new Event('change'));
        });
        list.appendChild(li);
      });
  });

  document.addEventListener('click', e => {
    if (!wrapper.contains(e.target)) list.classList.add('d-none');
  });
}

document.querySelectorAll('.searchable-select').forEach(initSearchableSelect);
```

### 5.7 Beep Sound on Add to Cart

```html
<!-- In <head> or before </body> -->
<audio id="beepSound" src="assets/mp3/beep.mp3" preload="auto"></audio>
```

```javascript
function playBeep() {
  const beep = document.getElementById('beepSound');
  beep.currentTime = 0;
  beep.play().catch(() => {}); // silent fail if autoplay blocked
}

function addToCart(itemId) {
  // ... add item logic ...
  playBeep();
  renderCart();
}
```

> Place `beep.mp3` in `assets/mp3/`. A short 100–200ms click/beep tone is recommended.

### 5.8 Pay Fast — Billing Fields Before Place

Shown only when sell type is **Pay Fast**.

```html
<div id="billingFields">
  <hr class="my-2">
  <!-- Discount -->
  <div class="row g-1 mb-1">
    <div class="col-5">
      <select class="form-select form-select-sm" id="discountType">
        <option value="flat">Flat ৳</option>
        <option value="percent">Percent %</option>
      </select>
    </div>
    <div class="col-7">
      <input type="number" class="form-control form-control-sm" id="discountValue"
             placeholder="Discount" min="0" value="0">
    </div>
  </div>

  <!-- Totals -->
  <div class="d-flex justify-content-between small mb-1">
    <span>Subtotal</span><span id="lblSubtotal">৳0.00</span>
  </div>
  <div class="d-flex justify-content-between small mb-1">
    <span>Discount</span><span id="lblDiscount">৳0.00</span>
  </div>
  <div class="d-flex justify-content-between small mb-1">
    <span>Tax</span><span id="lblTax">৳0.00</span>
  </div>
  <div class="d-flex justify-content-between fw-bold mb-2">
    <span>Total</span><span id="lblTotal">৳0.00</span>
  </div>

  <!-- Payment -->
  <select class="form-select form-select-sm searchable-select mb-1" id="paymentMethod">
    <option value="">— Payment Method —</option>
    <!-- dynamic from payment_methods -->
  </select>
  <input type="number" class="form-control form-control-sm mb-1"
         id="paidAmount" placeholder="Paid Amount" min="0">
  <div class="d-flex justify-content-between small text-danger mb-2">
    <span>Due</span><span id="lblDue">৳0.00</span>
  </div>
</div>
```

```javascript
function recalculate() {
  const subtotal  = cartItems.reduce((s, i) => s + i.line_total, 0);
  const discType  = document.getElementById('discountType').value;
  const discVal   = parseFloat(document.getElementById('discountValue').value) || 0;
  const discAmt   = discType === 'percent' ? subtotal * discVal / 100 : discVal;
  const taxPct    = parseFloat(POS_TAX_PERCENT) || 0;
  const taxAmt    = (subtotal - discAmt) * taxPct / 100;
  const total     = subtotal - discAmt + taxAmt;
  const paid      = parseFloat(document.getElementById('paidAmount').value) || 0;
  const due       = Math.max(0, total - paid);

  document.getElementById('lblSubtotal').textContent = '৳' + subtotal.toFixed(2);
  document.getElementById('lblDiscount').textContent = '৳' + discAmt.toFixed(2);
  document.getElementById('lblTax').textContent      = '৳' + taxAmt.toFixed(2);
  document.getElementById('lblTotal').textContent    = '৳' + total.toFixed(2);
  document.getElementById('lblDue').textContent      = '৳' + due.toFixed(2);
}
```

### 5.9 Place Order Button

```javascript
async function placeOrder() {
  const payload = buildOrderPayload(); // collect all form + cart data
  const res = await fetch('/api/restaurant/orders', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success) {
    if (window.currentSellType === 'pay_fast') {
      printBillInvoice(data.order);   // Printer 1
      printKitchenToken(data.order);  // Printer 2
      window.location.href = 'pos-serve.html';
    } else {
      printKitchenToken(data.order);  // Printer 2 only
      window.location.href = 'pos-ongoing.html';
    }
    resetCart();
  }
}
```

---

## 6. Dual Printer System

### 6.1 Configuration (from `pos_settings`)

| Setting          | Printer 1             | Printer 2              |
|------------------|-----------------------|------------------------|
| Label            | Bill Printer          | Kitchen Printer        |
| Used for         | Invoice / Bill        | Kitchen Token          |
| Default paper    | 80mm thermal          | 58mm thermal           |
| Setup location   | `pos-settings.html`   | same                   |

### 6.2 Print Functions

```javascript
// Detect environment — browser print vs native app bridge
const PRINTER_BRIDGE = window.FlutterBridge || window.ElectronBridge || null;

function printBillInvoice(order) {
  const html = renderBillTemplate(order);  // returns HTML string
  if (PRINTER_BRIDGE) {
    PRINTER_BRIDGE.print({ printer: POS_SETTINGS.printer1_name, content: html });
  } else {
    // Fallback: open print window
    const win = window.open('', '_blank', 'width=400,height=600');
    win.document.write(html);
    win.print();
    win.close();
  }
}

function printKitchenToken(order) {
  const html = renderKitchenTokenTemplate(order);
  if (PRINTER_BRIDGE) {
    PRINTER_BRIDGE.print({ printer: POS_SETTINGS.printer2_name, content: html });
  } else {
    const win = window.open('', '_blank', 'width=300,height=400');
    win.document.write(html);
    win.print();
    win.close();
  }
}
```

### 6.3 Bill Invoice Template

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
      Hotel & Restaurant
      123 Main Street
      Phone: +880XXXXXXXXXX
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  INVOICE
  Order  : RST-20250520-0001
  Token  : TKN-001
  Date   : 20 May 2025 13:42
  Type   : Takeaway
  Cashier: Ali Ahmed
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Customer: Rahim / 01XXXXXXXX
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ITEM              QTY   TOTAL
  Chicken Biryani    1   ৳220
    [Large] [Spicy]
  Mango Lassi        2   ৳120
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Subtotal:          ৳340
  Discount (10%):   -৳34
  Tax (5%):          ৳15
  Total:             ৳321
  Paid:              ৳400
  Change:            ৳79
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Thank you! Visit again.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### 6.4 Kitchen Token Template (Printer 2)

```
━━━━━━━━━━━━━━━━━━━━
  KITCHEN TOKEN
  TKN-001
  Order: RST-0001
  Type : Takeaway
  Time : 13:42
━━━━━━━━━━━━━━━━━━━━
  1× Chicken Biryani
     [Large, Spicy]
     Note: Less oil
  2× Mango Lassi
     [No Ice]
━━━━━━━━━━━━━━━━━━━━
  !! NO PRICE INFO !!
━━━━━━━━━━━━━━━━━━━━
```

> No price, no customer info on kitchen token.

---

## 7. Pay Fast Flow — End to End

```
POS Page
  │
  ├─ Select: Pay Fast
  ├─ Add items to cart
  ├─ Set discount / paid amount
  ├─ Click [Place Order]
  │
  ├─ API: POST /restaurant/orders
  │       sell_type = 'pay_fast'
  │       order_status = 'confirmed'
  │       payment_status = 'paid' | 'partial'
  │       serve_status = 'waiting'
  │
  ├─ Print Invoice (Printer 1)
  ├─ Print Kitchen Token (Printer 2)
  │
  └─ Redirect → pos-serve.html
```

---

## 8. Page: Serve Queue (`pos-serve.html`)

### 8.1 Purpose

Tracks **Pay Fast** orders that have been billed and paid but not yet physically handed to the customer. The kitchen prepares; the cashier/server confirms delivery.

### 8.2 Layout

```
┌──────────────────────────────────────────────────────┐
│  Serve Queue                          [🔄 Refresh]  │
├──────────────────────────────────────────────────────┤
│                                                      │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────┐ │
│  │  TKN-001     │  │  TKN-002     │  │  TKN-003   │ │
│  │  RST-0001    │  │  RST-0002    │  │  RST-0004  │ │
│  │  Takeaway    │  │  Takeaway    │  │  Delivery  │ │
│  │  ──────────  │  │  ──────────  │  │            │ │
│  │  Biryani ×1  │  │  Pasta ×2   │  │  ...       │ │
│  │  Lassi  ×2   │  │  Coffee ×1  │  │            │ │
│  │  ──────────  │  │  ──────────  │  │            │ │
│  │  ⏱ 00:03:22  │  │  ⏱ 00:01:10  │  │  ⏱ 00:00:45│ │
│  │  Rahim       │  │  Karim      │  │  Salam     │ │
│  │  PAID ৳321   │  │  DUE ৳50    │  │  PAID ৳180 │ │
│  │              │  │              │  │            │ │
│  │[✅ Confirm]  │  │[✅ Confirm]  │  │[✅ Confirm]│ │
│  └──────────────┘  └──────────────┘  └────────────┘ │
└──────────────────────────────────────────────────────┘
```

### 8.3 Order Card Details

Each card shows:
- Token number (large, prominent)
- Order number
- Order type (Takeaway / Delivery)
- Item list (name + quantity)
- Elapsed time since order placed (live counter `HH:MM:SS`)
- Customer name
- Payment status badge: **PAID** (green) or **DUE ৳XX** (red)

### 8.4 Confirm Serve Button

```javascript
async function confirmServe(orderId) {
  await fetch(`/api/restaurant/orders/${orderId}/serve-confirm`, { method: 'POST' });
  // Remove card from DOM with animation
  document.getElementById(`card-${orderId}`).classList.add('fade-out');
  setTimeout(() => document.getElementById(`card-${orderId}`).remove(), 400);
  // If due > 0: redirect to pos-due.html for that order
}
```

**API:**

```
POST /restaurant/orders/{id}/serve-confirm
  → SET serve_status = 'confirmed'
  → SET order_status = 'completed'
  → SET completed_at = NOW()
  → SET table status = 'available' (if table was set)
```

### 8.5 Auto Refresh

```javascript
// Auto-refresh serve queue every 10 seconds
setInterval(loadServeQueue, 10000);
```

---

## 9. Pay Later Flow — End to End

```
POS Page
  │
  ├─ Select: Pay Later
  ├─ Select Table (required)
  ├─ Add items to cart
  ├─ Click [Place Order]
  │
  ├─ API: POST /restaurant/orders
  │       sell_type = 'pay_later'
  │       order_status = 'confirmed'
  │       payment_status = 'unpaid'
  │       table_id = selected table
  │
  ├─ Print Kitchen Token only (Printer 2)
  ├─ Set table status = 'occupied'
  │
  └─ Redirect → pos-ongoing.html
```

---

## 10. Page: Ongoing Orders (`pos-ongoing.html`)

### 10.1 Purpose

Live view of all active Pay Later (dine-in) orders. Displayed as table-style cards. Tapping a card expands management options.

### 10.2 Layout — Card Grid

```
┌──────────────────────────────────────────────────────────────┐
│  Ongoing Orders                        [+ New Order]         │
├──────────────────────────────────────────────────────────────┤
│  ┌────────────┐  ┌────────────┐  ┌────────────┐             │
│  │  T-01      │  │  T-03      │  │  T-07      │             │
│  │  RST-0003  │  │  RST-0005  │  │  RST-0008  │             │
│  │  4 items   │  │  2 items   │  │  7 items   │             │
│  │  ৳620      │  │  ৳180      │  │  ৳1,240    │             │
│  │  ⏱ 00:22:15 │  │  ⏱ 00:08:44 │  │  ⏱ 00:45:02 │           │
│  └────────────┘  └────────────┘  └────────────┘             │
└──────────────────────────────────────────────────────────────┘
```

Each card shows:
- Table name (large)
- Order number
- Item count
- Total amount
- Live elapsed time (colour-coded: green < 30min, amber 30–60min, red > 60min)

### 10.3 Layout — Selected Order Detail Panel

Clicking a card opens a bottom slide-up panel (or right drawer on desktop):

```
┌────────────────────────────────────────────────────────┐
│  Table T-01 | Order RST-0003 | ⏱ 00:22:15            │
│  ─────────────────────────────────────────────────────│
│  ITEMS                                                 │
│  ┌─────────────────────────────────────────────────┐  │
│  │ Chicken Biryani       ×2         ৳440           │  │
│  │   [Large] [Extra Spicy]                         │  │
│  │ Mineral Water         ×2         ৳60            │  │
│  │ Garlic Naan           ×4         ৳120           │  │
│  └─────────────────────────────────────────────────┘  │
│  Subtotal: ৳620  |  Tax: ৳31  |  Total: ৳651         │
│  ─────────────────────────────────────────────────────│
│  [ ✏️ Edit ]     [ ❌ Cancel ]     [ ✅ Complete ]     │
└────────────────────────────────────────────────────────┘
```

### 10.4 Bottom Action Buttons

**Edit:**
```javascript
function editOrder(orderId) {
  // Load order items back into POS cart
  window.location.href = `pos.html?edit=${orderId}`;
  // POS detects ?edit param, loads order, locks sell_type to pay_later
}
```

**Cancel:**
```javascript
async function cancelOrder(orderId) {
  if (!confirm('Cancel this order?')) return;
  await fetch(`/api/restaurant/orders/${orderId}/cancel`, { method: 'POST' });
  // Set table status = 'available'
  // Remove card from grid
}
```

**Complete → Payment Modal:**

```
┌─────────────────────────────────────────────────┐
│  Complete Order — RST-0003                      │
│  ─────────────────────────────────────────────  │
│  Items:                                         │
│   Chicken Biryani ×2 ......... ৳440            │
│   Mineral Water   ×2 ......... ৳ 60            │
│   Garlic Naan     ×4 ......... ৳120            │
│  ─────────────────────────────────────────────  │
│  Subtotal: ৳620   Discount: [____]             │
│  Tax (5%): ৳28    Total:    ৳618               │
│  ─────────────────────────────────────────────  │
│  Payment Type: [Cash ▼] (searchable)            │
│  Paid Amount:  [_________]                      │
│  Due Amount:   ৳0                               │
│  ─────────────────────────────────────────────  │
│  [ Pay Now ]   [ Due Invoice ]                  │
└─────────────────────────────────────────────────┘
```

**Pay Now:**
```javascript
async function payNow(orderId, payload) {
  await fetch(`/api/restaurant/orders/${orderId}/complete`, {
    method: 'POST',
    body: JSON.stringify({ ...payload, payment_status: 'paid' })
  });
  printBillInvoice(order);    // Printer 1
  closePanel();
  removeCard(orderId);
}
```

**Due Invoice:**
```javascript
async function dueInvoice(orderId, payload) {
  await fetch(`/api/restaurant/orders/${orderId}/complete`, {
    method: 'POST',
    body: JSON.stringify({ ...payload, payment_status: 'partial' })
  });
  printBillInvoice(order);    // Printer 1 — prints with DUE amount shown
  // Order moves to pos-due.html
  closePanel();
  removeCard(orderId);
}
```

---

## 11. Page: Due Invoices (`pos-due.html`)

### 11.1 Purpose

Central list of all orders with outstanding (due) payment. Allows partial or full settlement.

### 11.2 Layout

```
┌────────────────────────────────────────────────────────────────────┐
│  Due Invoices                  [🔍 Search] [Date Filter ▼]        │
├──────────┬────────────┬──────────────┬──────────┬──────────┬──────┤
│ Order No │ Token      │ Customer     │ Total    │ Paid     │ Due  │
├──────────┼────────────┼──────────────┼──────────┼──────────┼──────┤
│ RST-0002 │ TKN-002    │ Karim / 01X  │ ৳400     │ ৳350     │ ৳50  │
│ RST-0006 │ TKN-006    │ Salam / 01X  │ ৳1,200   │ ৳0       │ ৳1,200│
│ RST-0011 │ TKN-011    │ —            │ ৳680     │ ৳500     │ ৳180 │
└──────────┴────────────┴──────────────┴──────────┴──────────┴──────┘
                                              [ Collect Payment → ]
```

### 11.3 Collect Payment

Clicking a row or **Collect Payment** opens a modal:

```
┌──────────────────────────────────────────┐
│  Collect Payment — RST-0002              │
│  ──────────────────────────────────────  │
│  Total: ৳400  |  Already Paid: ৳350     │
│  Due: ৳50                               │
│  ──────────────────────────────────────  │
│  Payment Method: [Cash ▼]               │
│  Amount: [____]                          │
│  Ref/Note: [____]                        │
│  ──────────────────────────────────────  │
│  [ Collect ]                             │
└──────────────────────────────────────────┘
```

```javascript
async function collectDuePayment(orderId, payload) {
  await fetch(`/api/restaurant/orders/${orderId}/payment`, {
    method: 'POST',
    body: JSON.stringify(payload)
  });
  // If remaining due = 0: remove from due list, set payment_status = 'paid'
  // If still due: update due amount in row
  printPaymentReceipt(orderId); // Optional small receipt from Printer 1
}
```

### 11.4 API

```
GET  /restaurant/orders?payment_status=partial,unpaid&location_id=X
POST /restaurant/orders/{id}/payment
```

---

## 12. API Additions / Changes

### 12.1 New Endpoints

| Method | Endpoint                                | Description                            |
|--------|-----------------------------------------|----------------------------------------|
| POST   | /restaurant/orders                      | Create order (handles both sell types) |
| POST   | /restaurant/orders/{id}/serve-confirm   | Confirm serve (Pay Fast)               |
| POST   | /restaurant/orders/{id}/cancel          | Cancel order                           |
| POST   | /restaurant/orders/{id}/complete        | Complete Pay Later order with payment  |
| POST   | /restaurant/orders/{id}/payment         | Add payment to due order               |
| GET    | /restaurant/orders/serve-queue          | Active Pay Fast orders waiting serve   |
| GET    | /restaurant/orders/ongoing              | Active Pay Later dine-in orders        |
| GET    | /restaurant/orders/due                  | All orders with due balance            |
| GET    | /pos/settings                           | POS settings incl. printer config      |
| PUT    | /pos/settings                           | Update POS settings                    |

### 12.2 Order Create Payload

```json
{
  "sell_type":       "pay_fast",
  "order_type":      "takeaway",
  "table_id":        null,
  "customer_id":     12,
  "customer_name":   "Rahim",
  "customer_phone":  "01700000000",
  "items": [
    { "item_id": 5, "quantity": 1, "note": "Less oil",
      "modifiers": [{ "modifier_id": 3 }] },
    { "item_id": 9, "quantity": 2, "modifiers": [] }
  ],
  "note":            "Hurry please",
  "discount_type":   "percent",
  "discount_value":  10,
  "payment_method_id": 1,
  "paid_amount":     400
}
```

---

## 13. Business Rules Summary

| Rule   | Description                                                                      |
|--------|----------------------------------------------------------------------------------|
| BR-01  | Pay Fast requires at least a paid amount entry (can be 0 for full due)           |
| BR-02  | Pay Later requires `table_id`; table must have status `available`                |
| BR-03  | On Pay Fast place: print bill (P1) + kitchen token (P2) immediately              |
| BR-04  | On Pay Later place: print kitchen token (P2) only                                |
| BR-05  | Serve confirm removes card from serve queue; sets `completed_at`                 |
| BR-06  | Edit (Pay Later) reloads order into POS; new items are appended                  |
| BR-07  | Cancel sets `order_status = cancelled`, restores `table.status = available`      |
| BR-08  | Complete triggers payment modal; "Pay Now" or "Due Invoice" both complete order   |
| BR-09  | Due Invoice: order leaves ongoing page but appears in due invoices page          |
| BR-10  | Full payment on due page sets `payment_status = paid`, removes from due list     |
| BR-11  | Beep plays on every successful `addToCart()` call                                |
| BR-12  | Kitchen token shows NO prices, NO customer info                                  |
| BR-13  | All dropdowns with >10 items must use searchable select                          |

---

## 14. Role Permission Matrix (POS Pages)

| Page / Action           | Admin | Manager | Cashier | Waiter | Kitchen |
|-------------------------|:-----:|:-------:|:-------:|:------:|:-------:|
| POS — Create order      |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |
| POS — Apply discount    |  ✅   |   ✅    |   ✅    |   ❌   |   ❌    |
| Serve Queue — view      |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |
| Serve Queue — confirm   |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |
| Ongoing — view          |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |
| Ongoing — edit order    |  ✅   |   ✅    |   ✅    |   ✅   |   ❌    |
| Ongoing — cancel order  |  ✅   |   ✅    |   ❌    |   ❌   |   ❌    |
| Ongoing — complete      |  ✅   |   ✅    |   ✅    |   ❌   |   ❌    |
| Due Invoices — view     |  ✅   |   ✅    |   ✅    |   ❌   |   ❌    |
| Due Invoices — collect  |  ✅   |   ✅    |   ✅    |   ❌   |   ❌    |
| POS Settings            |  ✅   |   ❌    |   ❌    |   ❌   |   ❌    |

---

## 15. Implementation Checklist

### Database
- [ ] Run `ALTER TABLE restaurant_orders` (add sell_type, serve_status, token_no, paid_amount, due_amount, completed_at)
- [ ] Create `pos_settings` table
- [ ] Insert default `pos_settings` row per location
- [ ] Add beep.mp3 to `assets/mp3/`

### Menu / RBAC
- [ ] Soft-delete `restaurant-kitchen.html` menu row
- [ ] Insert POS, Serve Queue, Ongoing Orders, Due Invoices into `menus`
- [ ] Insert `role_permissions` for new menus per role

### Backend
- [ ] `POST /restaurant/orders` — handle both sell_type flows
- [ ] `POST /restaurant/orders/{id}/serve-confirm`
- [ ] `POST /restaurant/orders/{id}/complete`
- [ ] `POST /restaurant/orders/{id}/payment`
- [ ] `GET /restaurant/orders/serve-queue`
- [ ] `GET /restaurant/orders/ongoing`
- [ ] `GET /restaurant/orders/due`
- [ ] `GET|PUT /pos/settings`
- [ ] Token number generator function

### Frontend
- [ ] `pos.html` — full-width, no sidebar, 5-col grid, sell type toggle
- [ ] `pos.html` — searchable select for all dropdowns
- [ ] `pos.html` — customer combined search
- [ ] `pos.html` — beep sound on add to cart
- [ ] `pos.html` — billing fields for Pay Fast
- [ ] `pos.html` — dual print on place
- [ ] `pos-serve.html` — card grid with live timer, confirm button
- [ ] `pos-ongoing.html` — card grid, slide-up panel, Edit/Cancel/Complete
- [ ] `pos-ongoing.html` — complete modal with Pay Now / Due Invoice
- [ ] `pos-due.html` — list table, collect payment modal
- [ ] Printer bridge integration (or fallback window.print)

---

*End of Specification — POS Module v2.0.0*