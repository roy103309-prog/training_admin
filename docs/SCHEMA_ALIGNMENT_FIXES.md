# API Schema Alignment Fixes

## Issues Fixed

Multiple APIs were using incorrect column names and table structures that didn't match the tenant schema.

---

## 1. ✅ Bookings Table - Column Name Mismatches

### Tenant Schema (Correct):
```sql
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_id INT NOT NULL,
    customer_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,          -- NOT checkin_date
    check_out DATE NOT NULL,          -- NOT checkout_date
    adults INT DEFAULT 1,
    children INT DEFAULT 0,
    total_amount DECIMAL(10,2),
    paid_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'),  -- NOT booking_status
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Key Points:**
- Column is `status` NOT `booking_status`
- Column is `check_in` NOT `checkin_date`
- Column is `check_out` NOT `checkout_date`
- NO `payment_status` column - calculated from `paid_amount` vs `total_amount`

### Status Values:
- `pending` - Booking created, awaiting confirmation
- `confirmed` - Booking confirmed, upcoming
- `checked_in` - Guest has checked in
- `checked_out` - Guest has checked out (completed)
- `cancelled` - Booking cancelled

---

## 2. ✅ Fixed: api/dashboard/stats.php

**Errors Fixed:**
- `booking_status` → `status`
- `checkin_date` → `check_in`
- Status value mapping updated

**Changes:**

```php
// OLD (WRONG)
WHERE booking_status = 'Upcoming' AND checkin_date > CURDATE()
WHERE booking_status = 'Completed'
WHERE booking_status IN ('Completed', 'Running')

// NEW (CORRECT)
WHERE status = 'confirmed' AND check_in > CURDATE()
WHERE status = 'checked_out'
WHERE status IN ('checked_out', 'checked_in')
```

---

## 3. ✅ Fixed: api/dashboard/chart-data.php

**Errors Fixed:**
- `booking_status` → `status`
- `payment_status` column doesn't exist - calculate from paid_amount

**Changes:**

```php
// OLD (WRONG) - payment_status column doesn't exist
WHERE payment_status = 'Paid'
SELECT booking_status, COUNT(*) as count...
SELECT payment_status, COUNT(*) as count...

// NEW (CORRECT)
WHERE paid_amount > 0  // Use paid_amount instead
SELECT status, COUNT(*) as count...
SELECT
    CASE
        WHEN paid_amount >= total_amount THEN 'Paid'
        WHEN paid_amount > 0 THEN 'Partial'
        ELSE 'Unpaid'
    END as payment_status,
    COUNT(*) as count
FROM bookings...
```

**Payment Status Calculation:**
- **Paid**: `paid_amount >= total_amount`
- **Partial**: `paid_amount > 0` AND `paid_amount < total_amount`
- **Unpaid**: `paid_amount = 0` OR `paid_amount IS NULL`

---

## 4. ✅ Settings Table - Structure Mismatch

### Tenant Schema (Correct):
```sql
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    FOREIGN KEY (location_id) REFERENCES locations(id),
    UNIQUE KEY unique_location_setting (location_id, setting_key)
);
```

**Key Points:**
- Table is `settings` NOT `settings_basic`
- Uses **key-value** structure, not columnar structure
- Each setting is a separate row

**Example Data:**
```
| id | location_id | setting_key   | setting_value        |
|----|-------------|---------------|----------------------|
| 1  | 1           | website_name  | Grand Hotel          |
| 2  | 1           | phone         | +880123456789        |
| 3  | 1           | email         | info@grandhotel.com  |
| 4  | 1           | address       | Dhaka, Bangladesh    |
```

---

## 5. ✅ Fixed: api/settings/basic-get.php

**Error Fixed:**
- `settings_basic` table doesn't exist → use `settings` table

**Changes:**

```php
// OLD (WRONG)
SELECT * FROM settings_basic WHERE location_id = ?

// NEW (CORRECT)
SELECT setting_key, setting_value FROM settings WHERE location_id = ?

// Then build array
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
```

---

## 6. ✅ Fixed: api/settings/basic-update.php

**Error Fixed:**
- `settings_basic` table doesn't exist → use `settings` table with key-value structure

**Changes:**

```php
// OLD (WRONG)
UPDATE settings_basic
SET website_name = ?, phone = ?, email = ?...
WHERE location_id = ?

// NEW (CORRECT)
// Delete old settings
DELETE FROM settings WHERE location_id = ? AND setting_key IN (...)

// Insert new settings
INSERT INTO settings (location_id, setting_key, setting_value)
VALUES (?, ?, ?)
// Loop for each setting field
```

---

## Complete List of Schema Alignment Issues

### Bookings Table:
| API Used (Wrong) | Schema Has (Correct) |
|------------------|----------------------|
| `booking_status` | `status` |
| `checkin_date` | `check_in` |
| `checkout_date` | `check_out` |
| `payment_status` | (calculated from `paid_amount`) |

### Settings:
| API Used (Wrong) | Schema Has (Correct) |
|------------------|----------------------|
| `settings_basic` table | `settings` table |
| Columnar structure | Key-value structure |

---

## Files Modified

1. ✅ `api/dashboard/stats.php`
   - Fixed column names: booking_status → status
   - Fixed date column: checkin_date → check_in
   - Updated status values to match ENUM

2. ✅ `api/dashboard/chart-data.php`
   - Fixed column: booking_status → status
   - Removed payment_status column reference
   - Calculate payment status from paid_amount
   - Added proper result cleanup

3. ✅ `api/settings/basic-get.php`
   - Changed from settings_basic to settings table
   - Implemented key-value retrieval
   - Added proper result cleanup

4. ✅ `api/settings/basic-update.php`
   - Changed from settings_basic to settings table
   - Implemented key-value insert/update
   - Added proper result cleanup

---

## Testing After Fixes

### Test Dashboard Stats
```
http://localhost/HotelAdmin/api/dashboard/stats.php
```
Should return:
```json
{
  "success": true,
  "message": "Dashboard statistics retrieved",
  "data": {
    "total_rooms": 0,
    "total_bookings": 0,
    "upcoming_bookings": 0,
    "completed_bookings": 0,
    "total_customers": 0,
    "total_revenue": "0.00"
  }
}
```

### Test Chart Data
```
http://localhost/HotelAdmin/api/dashboard/chart-data.php
```
Should return chart data without errors.

### Test Basic Settings
```
http://localhost/HotelAdmin/api/settings/basic-get.php
```
Should return:
```json
{
  "success": true,
  "message": "Settings retrieved",
  "data": {
    "website_name": "",
    "phone": "",
    "email": "",
    ...
  }
}
```

---

## Common Schema Patterns in Tenant Database

### Tables with location_id:
- All data tables are scoped by `location_id`
- Always filter by `getCurrentLocation()` in APIs

### Naming Conventions:
- Date columns: `check_in`, `check_out`, `created_at`
- Status columns: `status` (not `*_status`)
- Amount columns: `total_amount`, `paid_amount`
- Boolean columns: `isactive` (not `is_active`)

### Key-Value Tables:
- `settings` - uses setting_key/setting_value
- Store as separate rows, not columns

### ENUM Columns:
- `bookings.status` - ENUM type, use exact values
- Always check schema for allowed values

---

## Summary

All schema mismatches have been fixed. The APIs now correctly align with the tenant schema defined in `database/tenant_schema.sql`.

**Status:** All critical schema errors resolved.
