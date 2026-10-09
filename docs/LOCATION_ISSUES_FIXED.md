# Location Selection Issues - Complete Fix

## Issues Identified

### 1. ✅ Fatal Error in get-user-menu.php (Line 93)
**Error:** `mysqli_result object is already closed`

**Cause:** When using `$conn->query()` for superadmin, it returns a result object directly (not a statement). Calling `$stmt->close()` on it caused the error.

**Fix Applied:**
- File: `api/menu/get-user-menu.php`
- For superadmin: Set `$stmt = null` since no prepared statement is used
- Cleanup now checks if objects exist before calling methods

---

### 2. ✅ "No location selected" Error
**Error:** `{"success":false,"message":"No location selected","data":[]}`

**Cause:** The default admin user was created WITHOUT `role_id` and `location_id`:
```sql
-- OLD (WRONG)
INSERT INTO users (name, mobile, password_hash, issuperadmin, isactive)
VALUES ('Administrator', '01700000000', '...', 1, 1);
```

This means:
- After login, `$_SESSION['location_id']` = NULL
- `getCurrentLocation()` returns NULL
- All APIs that need location fail

**Fix Applied:**
- File: `database/tenant_schema.sql`
- Now creates admin user WITH role and location:
```sql
-- NEW (CORRECT)
INSERT INTO users (name, mobile, password_hash, role_id, location_id, issuperadmin, isactive)
VALUES ('Administrator', '01700000000', '...', 1, 1, 1, 1);
```

---

## How Location Selection Works

### Flow:

1. **User Logs In** → `api/auth/login.php`
   - Sets `$_SESSION['location_id']` from user's database record
   - Sets `$_SESSION['current_location_id']` to same value
   - Returns list of locations user can access

2. **If Single Location:**
   - Frontend (`index.html`) automatically redirects to `new-dashboard.html`
   - Location already set in session

3. **If Multiple Locations:**
   - Frontend shows modal with location dropdown
   - User selects location
   - Calls `api/auth/switch-location.php`
   - Updates `$_SESSION['current_location_id']`
   - Redirects to `new-dashboard.html`

4. **In APIs:**
   - Call `getCurrentLocation()` to get active location
   - Returns `$_SESSION['current_location_id']` or `$_SESSION['location_id']`

### Session Variables:
- `$_SESSION['location_id']` = User's default/assigned location (from users table)
- `$_SESSION['current_location_id']` = Currently active working location (can be changed)

---

## For Existing Tenant Databases

If you already registered and created a tenant database **BEFORE this fix**, you need to update the admin user:

### Option 1: Run SQL Update Script

**File:** `database/fix_admin_user_location.sql`

```bash
mysql -u root -p [your_tenant_database_name] < database/fix_admin_user_location.sql
```

### Option 2: Run SQL Directly

```sql
-- Login to MySQL
mysql -u root -p

-- Select your tenant database
USE hotel_[your_mobile]_[timestamp];

-- Update admin user
UPDATE users
SET role_id = 1, location_id = 1
WHERE mobile = '01700000000' AND issuperadmin = 1;

-- Verify
SELECT id, name, mobile, role_id, location_id, issuperadmin
FROM users
WHERE mobile = '01700000000';
```

**Expected Result:**
```
+----+---------------+-------------+---------+-------------+--------------+
| id | name          | mobile      | role_id | location_id | issuperadmin |
+----+---------------+-------------+---------+-------------+--------------+
|  1 | Administrator | 01700000000 |       1 |           1 |            1 |
+----+---------------+-------------+---------+-------------+--------------+
```

---

## Testing After Fix

### 1. Test Login
```
1. Logout (if logged in)
2. Go to: http://localhost/HotelAdmin/index.html
3. Login with your credentials
4. Should see location selection or auto-redirect
```

### 2. Test Menu Loading
```
1. After login, open browser console
2. Run: $.get('api/menu/get-user-menu.php', console.log)
3. Should return success with menus array
4. Should NOT return "No location selected"
```

### 3. Test Any Settings Page
```
1. Navigate to any settings page (e.g., settings-basic.html)
2. Should load without "No location selected" error
3. Check browser console and network tab
```

---

## Files Modified

### API Files:
1. ✅ `api/menu/get-user-menu.php`
   - Fixed mysqli_result close error
   - Proper cleanup for both query() and prepared statements

### Database Schema:
2. ✅ `database/tenant_schema.sql`
   - Admin user now includes `role_id = 1` and `location_id = 1`
   - Order changed: roles and locations created BEFORE admin user

### Fix Scripts:
3. ✅ `database/fix_admin_user_location.sql`
   - Updates existing databases to add role and location to admin user

---

## Location Selection Modal

The location selection modal is in `index.html` (login page):

```html
<!-- Location Selection Modal -->
<div class="modal fade" id="locationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Location</h5>
            </div>
            <div class="modal-body">
                <p class="mb-3">Please select your working location to continue</p>
                <select class="form-select" id="locationSelect">
                    <option value="">-- Select Location --</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnSelectLocation">Continue</button>
            </div>
        </div>
    </div>
</div>
```

**When it appears:**
- Only if user has access to multiple locations (superadmin or multi-location user)
- Shows after successful login
- Populated with locations from login API response

**When it doesn't appear:**
- If user has only 1 location → auto-redirects to dashboard
- Location is automatically set in session

---

## Summary

**Root Cause:** Default admin user was created without `role_id` and `location_id`, causing:
- `getCurrentLocation()` to return NULL
- All location-dependent APIs to fail with "No location selected"

**Solution:**
1. ✅ Fixed tenant schema for new registrations
2. ✅ Fixed mysqli error in menu loading
3. ✅ Created update script for existing databases
4. ✅ Updated dashboard redirect to correct page

**Next Steps:**
1. Run the SQL fix on your existing tenant database
2. Logout and login again
3. Everything should work now

---

**Status:** All location issues resolved. Ready for testing.
