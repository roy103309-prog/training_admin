# Hotel Management System - SAAS Setup Guide

## Multi-Tenant Architecture Overview

This Hotel Management System is now a **SAAS (Software as a Service)** application where:
- Each registered user gets their own isolated database
- Users register with business name, owner name, and mobile number
- Upon registration, a new database is automatically created
- Login connects to the user's specific database

---

## Installation Steps

### 1. Database Setup

Run these SQL files in order:

#### Step 1: Create Master Database
```bash
# Import the master database schema
mysql -u root -p < database/master_schema.sql
```

This creates:
- `hotel_saas_master` database
- `tenants` table (stores all registered businesses)
- `tenant_settings` table
- `activity_log` table

#### Step 2: Tenant Template Database
The `database/tenant_schema.sql` file is used as a template to create new databases automatically when users register. You don't need to import it manually.

### 2. Configuration

The system uses two configuration files:

**`api/tenant-config.php`** - Multi-tenant configuration
- Manages master database connection
- Creates tenant-specific database connections
- Handles database creation on registration

**`api/config.php`** - Legacy (kept for reference, not used)

### 3. Update All API Files

Run this script to update all API endpoints to use the tenant configuration:

```bash
php api/update-to-tenant-config.php
```

This will:
- Replace `config.php` with `tenant-config.php` in all API files
- Update `getDbConnection()` to `getTenantConnection()`

---

## User Registration Flow

### 1. New User Registration

**URL:** `register.html`

User provides:
- Business/Hotel Name
- Owner Name
- Mobile Number (used for login)
- Email (optional)
- Password

**API Endpoint:** `api/auth/register.php`

**Process:**
1. Validates input
2. Checks if mobile number already exists
3. Generates unique database name (e.g., `hotel_1234567890_1234567890`)
4. Creates tenant record in master database
5. Creates new database with full schema
6. Updates default admin user in new database
7. Logs activity

### 2. User Login

**URL:** `new-index.html`

User provides:
- Mobile Number
- Password

**API Endpoint:** `api/auth/login.php`

**Process:**
1. Checks master database for tenant using mobile number
2. Verifies password
3. Connects to tenant's specific database
4. Validates user in tenant database
5. Creates session with tenant database info
6. Returns user data and business info

---

## Database Naming Convention

Each tenant gets a unique database name:
```
hotel_[last_10_digits_of_mobile]_[timestamp]
```

Example: User with mobile `01712345678` registering at timestamp `1234567890`:
```
hotel_1712345678_1234567890
```

---

## Session Management

After login, the session contains:
```php
$_SESSION['tenant_id']          // Tenant ID from master DB
$_SESSION['tenant_database']    // Tenant's database name
$_SESSION['business_name']      // Business/Hotel name
$_SESSION['user_id']            // User ID in tenant DB
$_SESSION['user_name']          // User name
$_SESSION['user_mobile']        // User mobile
$_SESSION['role_id']            // User role
$_SESSION['location_id']        // User location
$_SESSION['issuperadmin']       // Is super admin flag
```

---

## Multi-Tenant Features

### 1. Automatic Database Creation
- Each registration creates a new isolated database
- Full schema with default data (admin user, default location)
- No data sharing between tenants

### 2. Default Admin Credentials
Each new tenant database comes with:
- **Username:** As provided during registration
- **Mobile:** As provided during registration
- **Password:** As provided during registration
- **Role:** Administrator (Super Admin)

### 3. Trial Period
- New registrations get 30 days free trial
- Trial period tracked in `trial_ends_at` column

### 4. Tenant Status
- **active:** Tenant can login and use the system
- **suspended:** Login blocked
- **cancelled:** Account terminated

---

## Security Features

1. **Password Hashing:** All passwords hashed using `password_hash()`
2. **SQL Injection Protection:** Prepared statements throughout
3. **Session Management:** Secure session handling
4. **Database Isolation:** Complete data separation per tenant
5. **Input Sanitization:** All inputs validated and sanitized

---

## Maintenance

### View All Tenants
```sql
USE hotel_saas_master;
SELECT id, business_name, owner_name, mobile, database_name, status, created_at
FROM tenants
ORDER BY created_at DESC;
```

### View Tenant Activity
```sql
USE hotel_saas_master;
SELECT * FROM activity_log
WHERE tenant_id = [TENANT_ID]
ORDER BY created_at DESC;
```

### Suspend a Tenant
```sql
UPDATE tenants SET status = 'suspended' WHERE id = [TENANT_ID];
```

### Activate a Tenant
```sql
UPDATE tenants SET status = 'active' WHERE id = [TENANT_ID];
```

---

## Default Credentials

**For Testing:** After registration, use:
- Mobile: [Your registered mobile]
- Password: [Your registered password]

---

## File Organization

```
HotelAdmin/
├── database/
│   ├── master_schema.sql          # Master database schema
│   └── tenant_schema.sql          # Template for tenant databases
├── api/
│   ├── tenant-config.php          # Multi-tenant configuration
│   ├── config.php                 # Legacy config (not used)
│   ├── auth/
│   │   ├── register.php           # Registration API
│   │   └── login.php              # Login API (multi-tenant)
│   └── [other API endpoints]
├── new-index.html                 # Login page (with register link)
├── register.html                  # Registration page
└── [other files]
```

---

## Troubleshooting

### Issue: "Master database connection failed"
**Solution:** Verify master database exists:
```bash
mysql -u root -p -e "SHOW DATABASES LIKE 'hotel_saas_master';"
```

### Issue: "Failed to create tenant database"
**Solution:** Check MySQL user permissions:
```sql
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### Issue: Login not working
**Solution:**
1. Check tenant exists in master database
2. Verify tenant status is 'active'
3. Check tenant database exists
4. Verify user exists in tenant database

---

## Support

For issues or questions, refer to:
- `modifications.md` - List of fixes and improvements
- `COMPLETE_PROJECT_GUIDE.md` - Full project documentation
- `FIXES_APPLIED.md` - Applied fixes log

---

**End of SAAS Setup Guide**
