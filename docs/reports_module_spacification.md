# Reports Module — Specification

**Project:** Hotel & Restaurant Management System (Multi-Tenant SaaS)
**Module:** Reports (Universal Report Engine)
**Version:** 1.0.0
**Depends On:** All modules (Hotel, Restaurant, POS, Inventory)
**Status:** Draft

---

## 1. Overview

A unified report engine shared across all modules. Every report follows
the same pattern:

```
Filter Bar → Preview Button → Report Preview → Print / Export
```

Every printed or exported report has a standard page header:

```
[Logo]  Business Name
        Phone | Email | Address
        ─────────────────────────
        Report Name          Date Range
```

---

## 2. Report List

### 2.1 Restaurant / POS Reports

| Report Name           | Page File                  | Module      |
|-----------------------|----------------------------|-------------|
| Sales Summary         | report-sales.html          | POS         |
| Order Details         | report-orders.html         | POS         |
| Item Sales            | report-items.html          | Restaurant  |
| Category Sales        | report-category.html       | Restaurant  |
| Payment Summary       | report-payments.html       | POS         |
| Due Invoices          | report-due.html            | POS         |
| Cashier Summary       | report-cashier.html        | POS         |
| Modifier Usage        | report-modifiers.html      | Restaurant  |
| Profit & Loss         | report-profit.html         | Inventory   |

### 2.2 Inventory Reports

| Report Name           | Page File                  | Module      |
|-----------------------|----------------------------|-------------|
| Stock Status          | report-stock.html          | Inventory   |
| Stock Ledger          | report-ledger.html         | Inventory   |
| Purchase Summary      | report-purchase.html       | Inventory   |
| Supplier Due          | report-supplier-due.html   | Inventory   |
| Material Consumption  | report-consumption.html    | Inventory   |
| Item Cost Analysis    | report-cost.html           | Inventory   |

### 2.3 Hotel Reports

| Report Name           | Page File                  | Module      |
|-----------------------|----------------------------|-------------|
| Booking Summary       | report-bookings.html       | Hotel       |
| Room Occupancy        | report-occupancy.html      | Hotel       |
| Customer List         | report-customers.html      | Hotel       |
| Payment Collected     | report-hotel-payments.html | Hotel       |

---

## 3. Universal Report Page Layout

Every report page follows this exact layout:

```
┌──────────────────────────────────────────────────────────────────────┐
│  PAGE TITLE: Sales Summary                                           │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  FILTER BAR                                                          │
│  ┌────────────┐ ┌────────────┐ ┌──────────────┐ ┌────────────────┐  │
│  │ Date From  │ │ Date To    │ │ Group By   ▼ │ │ [More Filters] │  │
│  └────────────┘ └────────────┘ └──────────────┘ └────────────────┘  │
│                                                                      │
│  COLUMN SELECTOR                                                     │
│  ☑ Order No  ☑ Date  ☑ Customer  ☑ Total  ☐ Tax  ☐ Discount        │
│                                                                      │
│  [ 🔍 Preview Report ]   [ 🖨 Print ]   [ ⬇ Export CSV ]            │
│                                                                      │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  REPORT PREVIEW (visible after clicking Preview)                     │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  [LOGO]   Hotel & Restaurant Name                            │   │
│  │           📞 +880XXXXXXXXXX  ✉ info@hotel.com               │   │
│  │           📍 123 Main Street, Dhaka                          │   │
│  │           ─────────────────────────────────────────────────  │   │
│  │           SALES SUMMARY REPORT                               │   │
│  │           Period: 01 May 2025 — 31 May 2025                  │   │
│  │           Generated: 20 May 2025 13:42   By: Admin           │   │
│  │  ─────────────────────────────────────────────────────────   │   │
│  │                                                              │   │
│  │  SUMMARY CARDS (if applicable)                               │   │
│  │  Total Orders: 320  │  Revenue: ৳124,500  │  Avg: ৳389      │   │
│  │                                                              │   │
│  │  Order No │ Date       │ Customer  │ Total   │ Status        │   │
│  │  ─────────┼────────────┼───────────┼─────────┼───────────    │   │
│  │  RST-0001 │ 2025-05-01 │ Rahim     │ ৳350    │ Completed     │   │
│  │  RST-0002 │ 2025-05-01 │ Karim     │ ৳180    │ Completed     │   │
│  │  ...                                                         │   │
│  │  ─────────────────────────────────────────────────────────   │   │
│  │  TOTAL                                   ৳124,500            │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 4. Report Header Standard

Printed on every report — sourced from `settings_basic` for the location.

### 4.1 HTML Template (Reusable Component)

```html
<!-- report-header.html — included in every report -->
<div class="report-header" id="reportHeader">
  <div class="report-header-top">

    <!-- Left: Logo -->
    <div class="report-logo">
      <img src="{{ settings.logo }}" alt="Logo"
           onerror="this.style.display='none'">
    </div>

    <!-- Center: Business Info -->
    <div class="report-business-info">
      <h2 class="business-name">{{ settings.website_name }}</h2>
      <div class="business-contacts">
        <span>📞 {{ settings.phone }}</span>
        <span>✉ {{ settings.email }}</span>
      </div>
      <div class="business-address">
        📍 {{ settings.address }}
      </div>
      <div class="social-links" style="display:none" id="socialLinks">
        <!-- shown only if any social URL is set -->
      </div>
    </div>

    <!-- Right: QR (optional, future) -->
    <div class="report-qr" style="display:none" id="reportQr"></div>

  </div>

  <!-- Divider -->
  <hr class="report-divider">

  <!-- Report Identity Row -->
  <div class="report-identity">
    <div>
      <h3 class="report-name" id="reportTitle">{{ reportName }}</h3>
      <div class="report-period" id="reportPeriod">
        Period: {{ dateFrom }} — {{ dateTo }}
      </div>
    </div>
    <div class="report-meta">
      <div>Generated: {{ generatedAt }}</div>
      <div>By: {{ generatedBy }}</div>
      <div class="report-ref" id="reportRef">
        <!-- optional reference number -->
      </div>
    </div>
  </div>
</div>
```

### 4.2 CSS for Report Header

```css
/* ── Report Header ───────────────────────────────────────── */
.report-header {
  padding: 16px 20px 8px;
  font-family: 'Segoe UI', Arial, sans-serif;
}

.report-header-top {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.report-logo img {
  width: 80px;
  height: 80px;
  object-fit: contain;
}

.report-business-info {
  flex: 1;
}

.business-name {
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 4px;
  color: #1a1a1a;
}

.business-contacts {
  font-size: 12px;
  color: #555;
  display: flex;
  gap: 16px;
  margin-bottom: 2px;
}

.business-address {
  font-size: 12px;
  color: #555;
}

.report-divider {
  border: none;
  border-top: 2px solid #333;
  margin: 10px 0 8px;
}

.report-identity {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.report-name {
  font-size: 15px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 2px;
  color: #1a1a1a;
}

.report-period {
  font-size: 12px;
  color: #444;
}

.report-meta {
  font-size: 11px;
  color: #666;
  text-align: right;
  line-height: 1.6;
}

/* ── Report Table ────────────────────────────────────────── */
.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  margin-top: 12px;
}

.report-table thead tr {
  background: #1a1a1a;
  color: #fff;
}

.report-table thead th {
  padding: 8px 10px;
  text-align: left;
  font-weight: 600;
  white-space: nowrap;
}

.report-table tbody tr:nth-child(even) {
  background: #f8f8f8;
}

.report-table tbody tr:hover {
  background: #eef2ff;
}

.report-table tbody td {
  padding: 7px 10px;
  border-bottom: 1px solid #e8e8e8;
  vertical-align: top;
}

.report-table tfoot tr {
  background: #f0f0f0;
  font-weight: 700;
  border-top: 2px solid #333;
}

.report-table tfoot td {
  padding: 8px 10px;
}

/* ── Summary Cards ───────────────────────────────────────── */
.report-summary-cards {
  display: flex;
  gap: 12px;
  margin: 12px 0;
  flex-wrap: wrap;
}

.report-summary-card {
  flex: 1;
  min-width: 120px;
  background: #f5f7ff;
  border: 1px solid #dce3ff;
  border-radius: 6px;
  padding: 10px 14px;
  text-align: center;
}

.report-summary-card .card-label {
  font-size: 11px;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.report-summary-card .card-value {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a1a;
  margin-top: 2px;
}

/* ── Print Overrides ─────────────────────────────────────── */
@media print {
  .filter-bar,
  .column-selector,
  .report-actions,
  .sidebar,
  .topbar,
  .page-title        { display: none !important; }

  .report-preview    { border: none !important; box-shadow: none !important; }
  body               { background: white; }

  .report-table tbody tr:hover { background: transparent; }

  @page {
    size: A4 landscape;
    margin: 10mm 12mm;
  }
}
```

---

## 5. Filter Bar System

### 5.1 Standard Filter Controls

Every report has a filter bar. Controls shown depend on the report type.

```html
<div class="filter-bar card p-3 mb-3" id="filterBar">
  <div class="row g-2 align-items-end">

    <!-- Date From -->
    <div class="col-auto">
      <label class="form-label form-label-sm mb-1">Date From</label>
      <input type="date" class="form-control form-control-sm"
             id="filterDateFrom" name="date_from">
    </div>

    <!-- Date To -->
    <div class="col-auto">
      <label class="form-label form-label-sm mb-1">Date To</label>
      <input type="date" class="form-control form-control-sm"
             id="filterDateTo" name="date_to">
    </div>

    <!-- Report-specific filters injected here -->
    <div class="col-auto" id="extraFilters">
      <!-- e.g. Category, Status, Cashier, Payment Method, etc. -->
    </div>

    <!-- Actions -->
    <div class="col-auto ms-auto">
      <button class="btn btn-primary btn-sm" onclick="generateReport()">
        🔍 Preview Report
      </button>
      <button class="btn btn-outline-secondary btn-sm" onclick="printReport()">
        🖨 Print
      </button>
      <button class="btn btn-outline-success btn-sm" onclick="exportCSV()">
        ⬇ Export CSV
      </button>
    </div>

  </div>
</div>
```

### 5.2 Date Preset Logic

```javascript
function applyDatePreset(preset) {
  const today = new Date();
  let from, to;

  const fmt = d => d.toISOString().split('T')[0]; // YYYY-MM-DD

  document.getElementById('filterDateFrom').value = from;
  document.getElementById('filterDateTo').value   = to;
}
```


---

## 6. Column Selector System

### 6.1 HTML

```html
<div class="column-selector card px-3 py-2 mb-3" id="columnSelector">
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <span class="text-muted small fw-semibold me-1">Show Columns:</span>
    <!-- checkboxes injected by JS per report -->
    <div id="columnCheckboxes" class="d-flex flex-wrap gap-2"></div>
    <button class="btn btn-link btn-sm p-0 ms-auto" onclick="resetColumns()">
      Reset
    </button>
  </div>
</div>
```

### 6.2 Column Definition Per Report

Each report defines its columns as a JS array. The engine renders checkboxes and builds the table dynamically.

```javascript
// Column definition format
const REPORT_COLUMNS = [
  // key: data field name  label: header text  default: shown by default
  // align: left/center/right   format: 'currency'/'date'/'number'/null
  { key: 'order_no',     label: 'Order No',    default: true,  align: 'left'  },
  { key: 'created_at',   label: 'Date',        default: true,  align: 'left',   format: 'date'     },
  { key: 'order_type',   label: 'Type',        default: true,  align: 'center' },
  { key: 'customer',     label: 'Customer',    default: true,  align: 'left'  },
  { key: 'table_name',   label: 'Table',       default: false, align: 'center' },
  { key: 'subtotal',     label: 'Subtotal',    default: false, align: 'right',  format: 'currency' },
  { key: 'discount',     label: 'Discount',    default: false, align: 'right',  format: 'currency' },
  { key: 'tax',          label: 'Tax',         default: false, align: 'right',  format: 'currency' },
  { key: 'final_amount', label: 'Total',       default: true,  align: 'right',  format: 'currency' },
  { key: 'paid_amount',  label: 'Paid',        default: false, align: 'right',  format: 'currency' },
  { key: 'due_amount',   label: 'Due',         default: false, align: 'right',  format: 'currency' },
  { key: 'payment_method', label: 'Payment',   default: true,  align: 'left'  },
  { key: 'cashier',      label: 'Cashier',     default: false, align: 'left'  },
  { key: 'sell_type',    label: 'Sell Type',   default: false, align: 'center' },
  { key: 'order_status', label: 'Status',      default: true,  align: 'center' },
];
```

### 6.3 Column Engine (Reusable JS)

```javascript
// report-engine.js — include in every report page

const ReportEngine = {

  // Active columns (user selection)
  activeColumns: [],

  // Render column checkboxes
  renderColumnSelector(columns) {
    const container = document.getElementById('columnCheckboxes');
    container.innerHTML = '';
    columns.forEach(col => {
      const checked = col.default ? 'checked' : '';
      container.innerHTML += `
        <label class="form-check form-check-inline mb-0">
          <input class="form-check-input" type="checkbox"
                 id="col_${col.key}" value="${col.key}" ${checked}
                 onchange="ReportEngine.updateActiveColumns()">
          <span class="form-check-label small">${col.label}</span>
        </label>`;
    });
    this.updateActiveColumns();
  },

  // Read currently checked columns
  updateActiveColumns() {
    this.activeColumns = REPORT_COLUMNS.filter(col => {
      const cb = document.getElementById(`col_${col.key}`);
      return cb && cb.checked;
    });
  },

  // Build table header
  buildHeader() {
    return `<thead><tr>${
      this.activeColumns.map(col =>
        `<th class="text-${col.align || 'left'}">${col.label}</th>`
      ).join('')
    }</tr></thead>`;
  },

  // Build table body
  buildBody(rows) {
    if (!rows.length) {
      return `<tbody><tr>
        <td colspan="${this.activeColumns.length}" class="text-center text-muted py-4">
          No data found for selected filters.
        </td>
      </tr></tbody>`;
    }

    return `<tbody>${rows.map(row => `
      <tr>${this.activeColumns.map(col => {
        const val = row[col.key] ?? '—';
        const formatted = this.formatValue(val, col.format);
        return `<td class="text-${col.align || 'left'}">${formatted}</td>`;
      }).join('')}</tr>`
    ).join('')}</tbody>`;
  },

  // Build footer totals row
  buildFooter(rows) {
    const hasCurrency = this.activeColumns.some(c => c.format === 'currency');
    if (!hasCurrency) return '';

    return `<tfoot><tr>${
      this.activeColumns.map((col, i) => {
        if (i === 0) return `<td><strong>TOTAL</strong></td>`;
        if (col.format === 'currency') {
          const sum = rows.reduce((s, r) => s + (parseFloat(r[col.key]) || 0), 0);
          return `<td class="text-right"><strong>${this.currency(sum)}</strong></td>`;
        }
        return `<td></td>`;
      }).join('')
    }</tr></tfoot>`;
  },

  // Format cell value
  formatValue(val, format) {
    if (val === null || val === undefined || val === '') return '—';
    switch (format) {
      case 'currency': return this.currency(val);
      case 'date':     return this.formatDate(val);
      case 'number':   return parseFloat(val).toLocaleString();
      default:         return val;
    }
  },

  currency(val) {
    return '৳' + parseFloat(val).toLocaleString('en', {
      minimumFractionDigits: 2, maximumFractionDigits: 2
    });
  },

  formatDate(val) {
    if (!val) return '—';
    const d = new Date(val);
    return d.toLocaleDateString('en-GB', {
      day: '2-digit', month: 'short', year: 'numeric'
    });
  },

  // Render full report into preview area
  render(rows, summaryCards = []) {
    this.updateActiveColumns();

    // Summary cards
    let cardsHTML = '';
    if (summaryCards.length) {
      cardsHTML = `<div class="report-summary-cards">${
        summaryCards.map(c => `
          <div class="report-summary-card">
            <div class="card-label">${c.label}</div>
            <div class="card-value">${c.value}</div>
          </div>`).join('')
      }</div>`;
    }

    // Table
    const tableHTML = `
      <table class="report-table">
        ${this.buildHeader()}
        ${this.buildBody(rows)}
        ${this.buildFooter(rows)}
      </table>`;

    document.getElementById('reportTableArea').innerHTML = cardsHTML + tableHTML;
    document.getElementById('reportPreview').style.display = 'block';

    // Scroll to preview
    document.getElementById('reportPreview')
      .scrollIntoView({ behavior: 'smooth', block: 'start' });
  },

  // Reset columns to defaults
  reset() {
    REPORT_COLUMNS.forEach(col => {
      const cb = document.getElementById(`col_${col.key}`);
      if (cb) cb.checked = col.default;
    });
    this.updateActiveColumns();
  }
};

function resetColumns() { ReportEngine.reset(); }
```

---

## 7. Report Preview Area

### 7.1 HTML Structure

```html
<!-- Report Preview — hidden until generated -->
<div id="reportPreview" class="report-preview card" style="display:none;">

  <!-- Standard Header (loaded dynamically) -->
  <div class="report-header-wrapper p-3 pb-0">
    <div id="reportHeader">
      <!-- Populated by loadReportHeader() -->
    </div>
  </div>

  <!-- Table Area -->
  <div class="p-3" id="reportTableArea">
    <!-- Populated by ReportEngine.render() -->
  </div>

  <!-- Footer -->
  <div class="report-footer p-3 pt-0 text-center">
    <small class="text-muted">
      This report was generated on <span id="footerDate"></span>
      by <span id="footerUser"></span>.
      For internal use only.
    </small>
  </div>

</div>
```

### 7.2 Load Report Header

```javascript
async function loadReportHeader(reportName, dateFrom, dateTo) {
  // Load settings_basic for this location
  const res      = await fetch(`/api/settings/basic?location_id=${LOCATION_ID}`);
  const settings = await res.json();

  const now = new Date();
  const generatedAt = now.toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });

  const fmtDate = d => new Date(d).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric'
  });

  document.getElementById('reportHeader').innerHTML = `
    <div class="report-header">
      <div class="report-header-top">

        <div class="report-logo">
          ${settings.logo
            ? `<img src="${settings.logo}" alt="Logo">`
            : `<div class="logo-placeholder">${(settings.website_name||'')[0]||'H'}</div>`
          }
        </div>

        <div class="report-business-info">
          <h2 class="business-name">${settings.website_name || ''}</h2>
          <div class="business-contacts">
            ${settings.phone   ? `<span>📞 ${settings.phone}</span>` : ''}
            ${settings.email   ? `<span>✉ ${settings.email}</span>`  : ''}
          </div>
          <div class="business-address">
            ${settings.address ? `📍 ${settings.address}` : ''}
          </div>
        </div>

      </div>

      <hr class="report-divider">

      <div class="report-identity">
        <div>
          <h3 class="report-name">${reportName}</h3>
          <div class="report-period">
            Period: ${fmtDate(dateFrom)} — ${fmtDate(dateTo)}
          </div>
        </div>
        <div class="report-meta">
          <div>Generated: ${generatedAt}</div>
          <div>By: ${CURRENT_USER_NAME}</div>
        </div>
      </div>
    </div>`;

  document.getElementById('footerDate').textContent = generatedAt;
  document.getElementById('footerUser').textContent = CURRENT_USER_NAME;
}
```

---

## 8. Generate Report Flow

```javascript
// report-sales.html — example implementation

async function generateReport() {
  const dateFrom = document.getElementById('filterDateFrom').value;
  const dateTo   = document.getElementById('filterDateTo').value;

  if (!dateFrom || !dateTo) {
    showToast('Please select a date range.', 'warning');
    return;
  }

  // Show loader
  showLoader('reportTableArea', 'Loading report...');
  document.getElementById('reportPreview').style.display = 'block';

  try {
    // Load header
    await loadReportHeader('Sales Summary Report', dateFrom, dateTo);

    // Collect all filters
    const params = new URLSearchParams({
      location_id:  LOCATION_ID,
      date_from:    dateFrom,
      date_to:      dateTo,
      status:       document.getElementById('filterStatus')?.value    || '',
      order_type:   document.getElementById('filterOrderType')?.value || '',
      cashier_id:   document.getElementById('filterCashier')?.value   || '',
    });

    // Fetch data
    const res  = await fetch(`/api/reports/sales?${params}`);
    const data = await res.json();

    // Summary cards
    const cards = [
      { label: 'Total Orders',   value: data.summary.total_orders },
      { label: 'Total Revenue',  value: ReportEngine.currency(data.summary.total_revenue) },
      { label: 'Total Discount', value: ReportEngine.currency(data.summary.total_discount) },
      { label: 'Total Tax',      value: ReportEngine.currency(data.summary.total_tax) },
      { label: 'Avg Order Value',value: ReportEngine.currency(data.summary.avg_value) },
    ];

    // Render
    ReportEngine.render(data.rows, cards);

  } catch (e) {
    showError('reportTableArea', 'Failed to load report. Please try again.');
  }
}
```

---

## 9. Print Function

```javascript
function printReport() {
  const preview = document.getElementById('reportPreview');
  if (preview.style.display === 'none') {
    showToast('Please generate the report first.', 'warning');
    return;
  }

  // Temporarily move preview to printable wrapper
  const printArea = document.createElement('div');
  printArea.id    = 'printArea';
  printArea.innerHTML = preview.innerHTML;
  document.body.appendChild(printArea);

  // Apply print-only class
  document.body.classList.add('printing');
  window.print();
  document.body.classList.remove('printing');

  printArea.remove();
}
```

**CSS for print isolation:**

```css
@media print {
  body > *:not(#printArea) { display: none !important; }
  #printArea { display: block !important; }
}
```

---

## 10. Export CSV Function

```javascript
function exportCSV() {
  if (!window.reportData || !window.reportData.length) {
    showToast('Please generate the report first.', 'warning');
    return;
  }

  ReportEngine.updateActiveColumns();
  const cols = ReportEngine.activeColumns;

  // Build CSV header
  const header = cols.map(c => `"${c.label}"`).join(',');

  // Build CSV rows
  const rows = window.reportData.map(row =>
    cols.map(col => {
      const val = row[col.key] ?? '';
      // Escape quotes in string values
      return `"${String(val).replace(/"/g, '""')}"`;
    }).join(',')
  );

  // Combine
  const csv = [header, ...rows].join('\n');

  // Download
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = `${REPORT_FILE_NAME}_${getDateRange()}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

function getDateRange() {
  const from = document.getElementById('filterDateFrom').value || '';
  const to   = document.getElementById('filterDateTo').value   || '';
  return `${from}_to_${to}`;
}
```

---

## 11. API Endpoints — All Reports

### 11.1 Sales Summary

```
GET /api/reports/sales
  ?location_id=1
  &date_from=2025-05-01
  &date_to=2025-05-31
  &status=completed
  &order_type=dine-in
  &cashier_id=5
  &sell_type=pay_fast

Response:
{
  "summary": {
    "total_orders":   320,
    "total_revenue":  124500.00,
    "total_discount": 6200.00,
    "total_tax":      4980.00,
    "avg_value":      389.06
  },
  "rows": [
    {
      "order_no":       "RST-20250501-0001",
      "created_at":     "2025-05-01 13:42:00",
      "order_type":     "dine-in",
      "customer":       "Rahim",
      "table_name":     "T-03",
      "subtotal":       350.00,
      "discount":       20.00,
      "tax":            16.50,
      "final_amount":   346.50,
      "paid_amount":    346.50,
      "due_amount":     0.00,
      "payment_method": "Cash",
      "cashier":        "Ali Ahmed",
      "sell_type":      "pay_later",
      "order_status":   "completed"
    },
    ...
  ]
}
```

### 11.2 Item Sales

```
GET /api/reports/items
  ?location_id=1&date_from=X&date_to=Y&category_id=3&sell_type=recipe

Response rows fields:
  item_name, category, item_sell_type,
  total_qty, total_revenue, cost_price,
  total_cost, gross_profit, margin_pct
```

### 11.3 Category Sales

```
GET /api/reports/category
  ?location_id=1&date_from=X&date_to=Y

Response rows fields:
  category_name, total_items_sold,
  total_revenue, total_cost, gross_profit
```

### 11.4 Payment Summary

```
GET /api/reports/payments
  ?location_id=1&date_from=X&date_to=Y&payment_method_id=2

Response rows fields:
  payment_date, order_no, customer,
  payment_method, amount, transaction_ref, received_by
```

### 11.5 Due Invoices

```
GET /api/reports/due
  ?location_id=1&date_from=X&date_to=Y

Response rows fields:
  order_no, token_no, order_date,
  customer, customer_phone, total_amount,
  paid_amount, due_amount, days_overdue
```

### 11.6 Cashier Summary

```
GET /api/reports/cashier
  ?location_id=1&date_from=X&date_to=Y&cashier_id=5

Response rows fields:
  cashier_name, total_orders, total_revenue,
  cash_collected, card_collected, mobile_collected,
  total_discount_given, avg_order_value
```

### 11.7 Profit & Loss

```
GET /api/reports/profit
  ?location_id=1&date_from=X&date_to=Y&group_by=daily|weekly|monthly

Response rows fields (daily):
  date, total_orders, revenue,
  cost, gross_profit, margin_pct
```

### 11.8 Stock Status

```
GET /api/reports/stock
  ?location_id=1&category_id=2&status=low|out|ok

Response rows fields:
  material_name, category, unit,
  current_stock, min_stock, last_purchase_price,
  stock_value, status
```

### 11.9 Stock Ledger

```
GET /api/reports/ledger
  ?location_id=1&date_from=X&date_to=Y
  &material_id=5&entry_type=IN|OUT|ADJUST_IN|ADJUST_OUT

Response rows fields:
  date, entry_type, material_name, unit,
  quantity, unit_price, total_value,
  reference, reference_id, note, created_by
```

### 11.10 Purchase Summary

```
GET /api/reports/purchase
  ?location_id=1&date_from=X&date_to=Y&supplier_id=3&status=confirmed

Response rows fields:
  purchase_no, purchase_date, supplier_name,
  total_amount, paid_amount, due_amount,
  payment_method, status, created_by
```

### 11.11 Supplier Due

```
GET /api/reports/supplier-due
  ?location_id=1&supplier_id=3

Response rows fields:
  supplier_name, phone, total_purchases,
  total_paid, opening_due, current_due
```

### 11.12 Material Consumption

```
GET /api/reports/consumption
  ?location_id=1&date_from=X&date_to=Y&material_id=5

Response rows fields:
  material_name, category, unit,
  opening_stock, purchased_qty, consumed_qty,
  adjusted_qty, closing_stock
```

### 11.13 Item Cost Analysis

```
GET /api/reports/cost
  ?location_id=1&category_id=2

Response rows fields:
  item_name, category, item_sell_type,
  sell_price, cost_price, unit_profit,
  margin_pct, last_cost_at
```

### 11.14 Booking Summary (Hotel)

```
GET /api/reports/bookings
  ?location_id=1&date_from=X&date_to=Y
  &booking_status=completed&payment_status=paid

Response rows fields:
  booking_id, checkin_date, checkout_date,
  customer_name, room_name, nights,
  total_amount, paid_amount, due_amount,
  payment_status, booking_status
```

---

## 12. Report Column Definitions Per Report

### 12.1 Sales Summary (`report-sales.html`)

```javascript
const REPORT_COLUMNS = [
  { key: 'order_no',       label: 'Order No',     default: true,  align: 'left'   },
  { key: 'created_at',     label: 'Date',         default: true,  align: 'left',  format: 'date'     },
  { key: 'order_type',     label: 'Type',         default: true,  align: 'center' },
  { key: 'sell_type',      label: 'Sell Type',    default: false, align: 'center' },
  { key: 'customer',       label: 'Customer',     default: true,  align: 'left'   },
  { key: 'table_name',     label: 'Table',        default: false, align: 'center' },
  { key: 'subtotal',       label: 'Subtotal',     default: false, align: 'right', format: 'currency' },
  { key: 'discount',       label: 'Discount',     default: false, align: 'right', format: 'currency' },
  { key: 'tax',            label: 'Tax',          default: false, align: 'right', format: 'currency' },
  { key: 'final_amount',   label: 'Total',        default: true,  align: 'right', format: 'currency' },
  { key: 'paid_amount',    label: 'Paid',         default: false, align: 'right', format: 'currency' },
  { key: 'due_amount',     label: 'Due',          default: false, align: 'right', format: 'currency' },
  { key: 'payment_method', label: 'Payment',      default: true,  align: 'left'   },
  { key: 'cashier',        label: 'Cashier',      default: false, align: 'left'   },
  { key: 'order_status',   label: 'Status',       default: true,  align: 'center' },
];
const REPORT_FILE_NAME = 'sales_summary';
```

### 12.2 Item Sales (`report-items.html`)

```javascript
const REPORT_COLUMNS = [
  { key: 'item_name',      label: 'Item',         default: true,  align: 'left'   },
  { key: 'category',       label: 'Category',     default: true,  align: 'left'   },
  { key: 'item_sell_type', label: 'Type',         default: false, align: 'center' },
  { key: 'total_qty',      label: 'Qty Sold',     default: true,  align: 'right', format: 'number'   },
  { key: 'total_revenue',  label: 'Revenue',      default: true,  align: 'right', format: 'currency' },
  { key: 'cost_price',     label: 'Unit Cost',    default: false, align: 'right', format: 'currency' },
  { key: 'total_cost',     label: 'Total Cost',   default: false, align: 'right', format: 'currency' },
  { key: 'gross_profit',   label: 'Profit',       default: true,  align: 'right', format: 'currency' },
  { key: 'margin_pct',     label: 'Margin %',     default: true,  align: 'right'  },
];
const REPORT_FILE_NAME = 'item_sales';
```

### 12.3 Stock Status (`report-stock.html`)

```javascript
const REPORT_COLUMNS = [
  { key: 'material_name',       label: 'Material',    default: true,  align: 'left'   },
  { key: 'category',            label: 'Category',    default: true,  align: 'left'   },
  { key: 'unit',                label: 'Unit',        default: true,  align: 'center' },
  { key: 'current_stock',       label: 'Stock',       default: true,  align: 'right', format: 'number'   },
  { key: 'min_stock',           label: 'Min Stock',   default: true,  align: 'right', format: 'number'   },
  { key: 'last_purchase_price', label: 'Last Price',  default: false, align: 'right', format: 'currency' },
  { key: 'stock_value',         label: 'Stock Value', default: true,  align: 'right', format: 'currency' },
  { key: 'status',              label: 'Status',      default: true,  align: 'center' },
];
const REPORT_FILE_NAME = 'stock_status';
```

### 12.4 Stock Ledger (`report-ledger.html`)

```javascript
const REPORT_COLUMNS = [
  { key: 'date',          label: 'Date',        default: true,  align: 'left',  format: 'date'     },
  { key: 'entry_type',    label: 'Type',        default: true,  align: 'center' },
  { key: 'material_name', label: 'Material',    default: true,  align: 'left'   },
  { key: 'unit',          label: 'Unit',        default: true,  align: 'center' },
  { key: 'quantity',      label: 'Qty',         default: true,  align: 'right', format: 'number'   },
  { key: 'unit_price',    label: 'Unit Price',  default: false, align: 'right', format: 'currency' },
  { key: 'total_value',   label: 'Value',       default: true,  align: 'right', format: 'currency' },
  { key: 'reference',     label: 'Reference',   default: true,  align: 'left'   },
  { key: 'note',          label: 'Note',        default: false, align: 'left'   },
  { key: 'created_by',    label: 'By',          default: true,  align: 'left'   },
];
const REPORT_FILE_NAME = 'stock_ledger';
```

### 12.5 Purchase Summary (`report-purchase.html`)

```javascript
const REPORT_COLUMNS = [
  { key: 'purchase_no',   label: 'Purchase No',  default: true,  align: 'left'   },
  { key: 'purchase_date', label: 'Date',         default: true,  align: 'left',  format: 'date'     },
  { key: 'supplier_name', label: 'Supplier',     default: true,  align: 'left'   },
  { key: 'total_amount',  label: 'Total',        default: true,  align: 'right', format: 'currency' },
  { key: 'paid_amount',   label: 'Paid',         default: true,  align: 'right', format: 'currency' },
  { key: 'due_amount',    label: 'Due',          default: true,  align: 'right', format: 'currency' },
  { key: 'payment_method',label: 'Payment',      default: false, align: 'left'   },
  { key: 'status',        label: 'Status',       default: true,  align: 'center' },
  { key: 'created_by',    label: 'Created By',   default: false, align: 'left'   },
];
const REPORT_FILE_NAME = 'purchase_summary';
```

### 12.6 Profit & Loss (`report-profit.html`)

```javascript
const REPORT_COLUMNS = [
  { key: 'date',         label: 'Date',          default: true,  align: 'left',  format: 'date'     },
  { key: 'total_orders', label: 'Orders',        default: true,  align: 'right', format: 'number'   },
  { key: 'revenue',      label: 'Revenue',       default: true,  align: 'right', format: 'currency' },
  { key: 'cost',         label: 'Cost',          default: true,  align: 'right', format: 'currency' },
  { key: 'gross_profit', label: 'Gross Profit',  default: true,  align: 'right', format: 'currency' },
  { key: 'margin_pct',   label: 'Margin %',      default: true,  align: 'right'  },
];
const REPORT_FILE_NAME = 'profit_loss';
```

---

## 13. Loader and Error Helpers

```javascript
function showLoader(containerId, message = 'Loading...') {
  document.getElementById(containerId).innerHTML = `
    <div class="text-center py-5 text-muted">
      <div class="spinner-border spinner-border-sm me-2"></div>
      ${message}
    </div>`;
}

function showError(containerId, message) {
  document.getElementById(containerId).innerHTML = `
    <div class="alert alert-danger m-3">
      ⚠️ ${message}
    </div>`;
}
```

---

## 14. File Structure

```
/reports/
  ├── js/
  │   ├── report-engine.js      ← reusable engine (ReportEngine object)
  │   └── report-header.js      ← loadReportHeader() function
  ├── css/
  │   └── report.css            ← all report CSS including @media print
  ├── report-sales.html
  ├── report-orders.html
  ├── report-items.html
  ├── report-category.html
  ├── report-payments.html
  ├── report-due.html
  ├── report-cashier.html
  ├── report-modifiers.html
  ├── report-profit.html
  ├── report-stock.html
  ├── report-ledger.html
  ├── report-purchase.html
  ├── report-supplier-due.html
  ├── report-consumption.html
  ├── report-cost.html
  ├── report-bookings.html
  ├── report-occupancy.html
  ├── report-customers.html
  └── report-hotel-payments.html

/api/reports/
  ├── sales.php
  ├── items.php
  ├── category.php
  ├── payments.php
  ├── due.php
  ├── cashier.php
  ├── modifiers.php
  ├── profit.php
  ├── stock.php
  ├── ledger.php
  ├── purchase.php
  ├── supplier-due.php
  ├── consumption.php
  ├── cost.php
  ├── bookings.php
  ├── occupancy.php
  ├── customers.php
  └── hotel-payments.php
```

---

## 15. Implementation Checklist

### Shared Assets
- [ ] Create `report-engine.js` with full `ReportEngine` object
- [ ] Create `report-header.js` with `loadReportHeader()` and `applyDatePreset()`
- [ ] Create `report.css` with all styles including `@media print`
- [ ] Include all three files in every report page

### Each Report Page
- [ ] Filter bar with date from/to + preset selector
- [ ] Report-specific extra filter controls
- [ ] Column selector with `renderColumnSelector(REPORT_COLUMNS)` on page load
- [ ] Preview, Print, Export CSV buttons wired up
- [ ] `generateReport()` function implemented
- [ ] `REPORT_COLUMNS` defined per report
- [ ] `REPORT_FILE_NAME` constant set for CSV filename

### Backend
- [ ] All 18 API endpoints created
- [ ] All endpoints filter by `location_id` from session
- [ ] All endpoints accept `date_from` and `date_to` with SQL `BETWEEN`
- [ ] Summary totals returned separately in response `summary` object
- [ ] Data rows returned as flat array in response `rows` array

### Settings
- [ ] `GET /api/settings/basic?location_id=X` endpoint returns logo, name, phone, email, address
- [ ] Logo served as absolute URL or base64 for print compatibility

---

*End of Specification — Reports Module v1.0.0*