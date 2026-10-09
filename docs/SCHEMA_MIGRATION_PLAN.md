# Schema Migration Plan - Align with Previous Schema

## Overview
Migrating from current `tenant_schema.sql` to match `hotel_management_schema_my_previous.sql`

---

## Key Differences Identified

### 1. **Settings Module** ✅ FIXED
**Previous Schema:** `settings_basic` table with columns
**Current Schema:** `settings` key-value table

**Table Structure:**
```sql
CREATE TABLE settings_basic (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_id INT UNSIGNED NOT NULL,
  website_name VARCHAR(255),
  phone VARCHAR(50),
  email VARCHAR(100),
  address TEXT,
  facebook_url VARCHAR(255),
  twitter_url VARCHAR(255),
  instagram_url VARCHAR(255),
  youtube_url VARCHAR(255),
  UNIQUE KEY uq_location (location_id)
);
```

**APIs Fixed:**
- ✅ `api/settings/basic-get.php` - Now uses `settings_basic` table
- ✅ `api/settings/basic-update.php` - Now uses `settings_basic` table

---

### 2. **Blogs Module** ⚠️ NEEDS FIX
**Previous Schema:** `blogs` table with `description` column
**Current Schema:** `blog_posts` table with `content` column

**Previous Structure:**
```sql
CREATE TABLE blogs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  image VARCHAR(255),
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Current Structure:**
```sql
CREATE TABLE blog_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255),
  content TEXT,
  image VARCHAR(255),
  author VARCHAR(100),
  isactive TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Required Changes:**
- Rename `blog_posts` → `blogs`
- Rename `content` → `description`
- Remove `slug`, `author`, `isactive` columns (or keep them)

**APIs to Update:**
- `api/settings/blog-list.php`
- `api/settings/blog-create.php`
- `api/settings/blog-update.php`
- `api/settings/blog-delete.php`

---

### 3. **Rooms Module** ⚠️ NEEDS FIX
**Previous Schema:** `rooms.name` and `rooms.bed_id`
**Current Schema:** `rooms.room_number` and `rooms.room_type_id`

**Previous Structure:**
```sql
CREATE TABLE rooms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) DEFAULT 0.00,
  bed_id INT UNSIGNED,
  isactive TINYINT(1) DEFAULT 1,
  FOREIGN KEY (bed_id) REFERENCES beds(id)
);
```

**Current Structure:**
```sql
CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  room_number VARCHAR(50) NOT NULL,
  room_type_id INT,
  floor_number INT,
  status ENUM('available', 'occupied', 'maintenance', 'reserved'),
  price DECIMAL(10,2),
  description TEXT,
  isactive TINYINT(1) DEFAULT 1,
  FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);
```

**Required Changes:**
- Change `room_number` → `name`
- Change `room_type_id` → `bed_id`
- Remove `floor_number`, `status` columns
- Change foreign key from `room_types` → `beds`

**APIs to Update:**
- ✅ `api/rooms/rooms-list.php` - Already updated but needs revert
- `api/rooms/rooms-create.php`
- `api/rooms/rooms-update.php`
- `api/rooms/rooms-delete.php`

**Frontend to Update:**
- ✅ `rooms-list.html` - Already updated but needs revert

---

### 4. **Bookings Module** ⚠️ NEEDS FIX
**Previous Schema:** Different column names and enums
**Current Schema:** Modified column names

**Previous Structure:**
```sql
CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id VARCHAR(50) NOT NULL,
  location_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  room_id INT UNSIGNED NOT NULL,
  checkin_date DATE NOT NULL,
  checkout_date DATE NOT NULL,
  total_amount DECIMAL(10,2) DEFAULT 0.00,
  payment_status ENUM('Pending','Paid','Partial','Refunded') DEFAULT 'Pending',
  booking_status ENUM('Upcoming','Running','Completed','Cancelled') DEFAULT 'Upcoming',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Current Structure:**
```sql
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id VARCHAR(100),
  location_id INT NOT NULL,
  customer_id INT NOT NULL,
  room_id INT NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  adults INT DEFAULT 1,
  children INT DEFAULT 0,
  total_amount DECIMAL(10,2),
  paid_amount DECIMAL(10,2) DEFAULT 0.00,
  status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Required Changes:**
- Rename `check_in` → `checkin_date`
- Rename `check_out` → `checkout_date`
- Remove `adults`, `children`, `paid_amount` columns
- Add back `payment_status` column
- Rename `status` → `booking_status` with different ENUM values
- Change ENUM values to match previous schema

**APIs to Update:**
- ✅ `api/booking/bookings-list.php` - Partially updated
- `api/booking/bookings-create.php`
- `api/booking/bookings-update.php`
- `api/booking/bookings-delete.php`
- ✅ `api/dashboard/stats.php` - Already updated but may need adjustments
- ✅ `api/dashboard/chart-data.php` - Already updated but may need adjustments

---

### 5. **Missing Tables in Current Schema**

**Previous schema has but current doesn't:**
- `about_us` table (current has settings for this)
- Proper `INT UNSIGNED` data types throughout

---

## Migration Steps

### Phase 1: Update Schema ⚠️ PENDING
1. Create migration SQL to convert current tables to previous structure
2. Update `tenant_schema.sql` to match previous schema exactly

### Phase 2: Fix Settings APIs ✅ COMPLETED
- ✅ Fixed `basic-get.php`
- ✅ Fixed `basic-update.php`

### Phase 3: Fix Blog APIs ⚠️ PENDING
- Revert `blog_posts` → `blogs`
- Change `content` → `description`
- Update all blog APIs

### Phase 4: Fix Rooms APIs ⚠️ PENDING
- Revert `room_number` → `name`
- Revert `room_type_id` → `bed_id`
- Update all room APIs
- Update frontend

### Phase 5: Fix Bookings APIs ⚠️ PENDING
- Update column names
- Fix ENUM values
- Update all booking APIs
- Update dashboard APIs

### Phase 6: Testing ⚠️ PENDING
- Test all CRUD operations
- Verify data integrity
- Check all forms

---

## Status Summary

**Completed:** Settings module (basic-get, basic-update)
**In Progress:** Schema analysis
**Pending:** Blog, Rooms, Bookings modules, Schema update

**Next Action:** Update tenant_schema.sql to match previous schema structure
