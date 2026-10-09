# SMS Module Integration Guide

## Overview

The SMS module has been fully integrated into the HotelAdmin project. This guide explains how to set up and use the SMS system.

## Files Structure

```
api/sms/
  ├── config.php              (SMS service configuration and classes)
  ├── send.php                (Send SMS endpoint)
  ├── balance.php             (Get SMS balance endpoint)
  ├── templates.php           (Manage SMS templates)
  ├── contacts.php            (Manage contacts)
  ├── groups.php              (Manage contact groups)
  ├── campaigns.php           (Manage SMS campaigns)
  └── logs.php                (View SMS logs)

templates/
  ├── sms-dashboard.html      (SMS dashboard/overview)
  ├── sms-send.html           (Send SMS interface)
  ├── sms-templates.html      (Template management)
  ├── sms-contacts.html       (Contact management)
  ├── sms-logs.html           (SMS transaction logs)
  └── sms-settings.html       (Configuration)

database/
  └── sms_migration.sql       (Database schema)
```

## Database Setup

1. Execute the migration SQL in each tenant database:

```sql
SOURCE /path/to/database/sms_migration.sql;
```

Or copy and paste the contents of `database/sms_migration.sql` into your database client.

2. This creates the following tables:
   - `sms_templates` - SMS message templates
   - `contacts` - Contact information
   - `contact_groups` - Contact groups
   - `sms_campaigns` - Campaign definitions
   - `sms_logs` - Send logs and history
   - `sms_balance` - SMS balance tracking
   - `sms_settings` - Configuration settings
   - `sms_packages` - Package definitions
   - `sms_transactions` - Payment transactions

## Add SMS to Menu

Add SMS menu items to your navigation. Execute this SQL in the master database:

```sql
-- Insert SMS main menu and submenus
INSERT INTO menus (name, icon, url, parent_id, sort_order, isactive) VALUES
('SMS Management', 'bx bxs-message-square-detail', '/admin/sms-dashboard', NULL, 15, 1);

SET @sms_parent_id = LAST_INSERT_ID();

INSERT INTO menus (name, icon, url, parent_id, sort_order, isactive) VALUES
('Dashboard', 'bx bx-home', 'sms-dashboard', @sms_parent_id, 1, 1),
('Send SMS', 'bx bx-send', 'sms-send', @sms_parent_id, 2, 1),
('Templates', 'bx bx-file', 'sms-templates', @sms_parent_id, 3, 1),
('Contacts', 'bx bx-user', 'sms-contacts', @sms_parent_id, 4, 1),
('Logs', 'bx bx-list-ul', 'sms-logs', @sms_parent_id, 5, 1),
('Settings', 'bx bx-cog', 'sms-settings', @sms_parent_id, 6, 1);
```

## Authentication & Authorization

The SMS APIs use the existing HotelAdmin authentication system:

- All SMS endpoints require an authenticated user session
- User permissions are checked automatically
- Sub-users can access SMS if they have appropriate role permissions

## API Endpoints

### Send SMS
```
POST /api/sms/send.php
Body: {
  "message": "Hello {name}",
  "recipients": [{"phone": "1234567890", "name": "John"}],
  "source": "Booking Confirmation",
  "template_id": "optional"
}
```

### Get Balance
```
GET /api/sms/balance.php
Response: {
  "remaining_sms": 150,
  "used_sms": 50,
  "total_sms": 200
}
```

### Templates (CRUD)
```
GET /api/sms/templates.php              (List all)
POST /api/sms/templates.php             (Create)
PUT /api/sms/templates.php              (Update)
DELETE /api/sms/templates.php           (Delete)
```

### Contacts (CRUD)
```
GET /api/sms/contacts.php               (List)
POST /api/sms/contacts.php              (Create)
PUT /api/sms/contacts.php               (Update)
DELETE /api/sms/contacts.php            (Delete)
```

### Groups (CRUD)
```
GET /api/sms/groups.php
POST /api/sms/groups.php
PUT /api/sms/groups.php
DELETE /api/sms/groups.php
```

### Campaigns (CRUD)
```
GET /api/sms/campaigns.php
POST /api/sms/campaigns.php
PUT /api/sms/campaigns.php
DELETE /api/sms/campaigns.php
```

### Logs
```
GET /api/sms/logs.php?status=Sent&phone=1234567890&limit=100
```

### Settings
```
GET /api/sms/settings.php               (Get current settings)
POST /api/sms/settings.php              (Update settings)
PUT /api/sms/settings.php               (Update settings)
```

## Configuration

SMS settings are stored in the `sms_settings` table. Configure via the SMS Settings page or API:

```json
{
  "gateway_name": "SMS_GATEWAY",
  "api_key": "your_api_key",
  "api_secret": "your_api_secret",
  "sender_id": "YourBrand",
  "sms_rate": 0.25
}
```

## Usage Examples

### Send SMS from PHP

```php
$data = [
    'message' => 'Hello, your booking is confirmed!',
    'recipients' => [
        ['phone' => '1234567890', 'name' => 'John']
    ],
    'source' => 'Booking System'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://yourhost/api/sms/send.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
```

### Integration Points

The SMS module integrates naturally with:
- **Booking System** - Send confirmation/cancellation messages
- **Restaurant Orders** - Order status notifications
- **User Management** - Account notifications
- **Admin Alerts** - Low balance warnings

## Performance Considerations

- SMS logs are indexed by phone, status, and campaign_uid for fast queries
- Balance is cached and updated on each send
- Large batch sends (>1000) should use campaigns instead of direct API calls

## Security Notes

- All API calls require authentication via existing HotelAdmin session
- API credentials stored in sms_settings table
- SMS messages logged for audit trail
- Balance tracked to prevent overspending

## Troubleshooting

1. **SMS not sending?**
   - Check SMS balance: `GET /api/sms/balance.php`
   - Verify settings: `GET /api/sms/settings.php`
   - Check logs for errors: `GET /api/sms/logs.php?status=Failed`

2. **Database errors?**
   - Run migration SQL to create tables
   - Ensure sms_balance has row with id=1
   - Check table collation (should be utf8mb4_unicode_ci)

3. **Permission errors?**
   - User must have "SMS Management" role permission
   - Check role_permissions table for user's role
