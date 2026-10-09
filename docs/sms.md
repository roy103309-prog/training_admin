# sms-specification.md

# SMS Module Specification

## Hotel + Restaurant SaaS System

---

# 1. Module Overview

The SMS Module is a dynamic notification and campaign management system for the Hotel + Restaurant SaaS platform.

The system supports:

* Dynamic SMS Templates
* Auto Variable Parsing
* Single SMS Sending
* Bulk SMS Campaigns
* Automation Rules
* Scheduled SMS
* Contact Groups
* SMS Logs
* Voice SMS
* Multi-language SMS
* Multi-location SMS
* Queue-based Delivery

The system is fully tenant-based.

---

# 2. Core Architecture

```text
Event Trigger
    ↓
Automation Rule
    ↓
Load SMS Template
    ↓
Resolve Dynamic Variables
    ↓
Parse Template Content
    ↓
Create SMS Campaign / Log
    ↓
Queue SMS
    ↓
Send Gateway API
    ↓
Update Delivery Status
```

---

# 3. SMS Types

## 3.1 Booking SMS

| Type                      | Description                |
| ------------------------- | -------------------------- |
| booking_confirmation      | Booking successful         |
| booking_pending           | Booking awaiting payment   |
| booking_cancelled         | Booking cancelled          |
| booking_checkin_reminder  | Upcoming check-in reminder |
| booking_checkout_reminder | Upcoming checkout reminder |
| booking_completed         | Booking completed          |

---

## 3.2 Payment SMS

| Type             | Description                   |
| ---------------- | ----------------------------- |
| payment_received | Payment successfully received |
| payment_due      | Payment due reminder          |
| payment_partial  | Partial payment reminder      |
| payment_refund   | Refund processed              |

---

## 3.3 Restaurant SMS

| Type                        | Description      |
| --------------------------- | ---------------- |
| restaurant_order_confirmed  | Order confirmed  |
| restaurant_order_preparing  | Food preparing   |
| restaurant_order_ready      | Food ready       |
| restaurant_order_completed  | Order completed  |
| restaurant_delivery_started | Delivery started |

---

## 3.4 Customer SMS

| Type            | Description        |
| --------------- | ------------------ |
| welcome_sms     | Welcome message    |
| birthday_sms    | Birthday greeting  |
| promotional_sms | Marketing campaign |
| membership_sms  | Membership update  |

---

## 3.5 Staff SMS

| Type             | Description              |
| ---------------- | ------------------------ |
| staff_salary     | Salary notification      |
| staff_attendance | Attendance notification  |
| staff_leave      | Leave approval/rejection |

---

# 4. Entity Types

Each SMS template belongs to an entity type.

| Entity Type        | Data Source               |
| ------------------ | ------------------------- |
| booking            | bookings                  |
| customer           | customers                 |
| payment            | payments                  |
| restaurant_order   | restaurant_orders         |
| restaurant_payment | restaurant_order_payments |
| user               | users                     |
| location           | locations                 |

---

# 5. SMS Template Rules

## sms_templates Table Usage

| Column    | Description                |
| --------- | -------------------------- |
| uid       | Unique template identifier |
| name      | Template display name      |
| type      | SMS type slug              |
| language  | SMS language               |
| content   | Template body              |
| variables | JSON variable list         |
| status    | Active / Inactive          |

---

# 6. Dynamic Variables

Variables use this format:

```text
{VARIABLE_NAME}
```

Example:

```text
Dear {CUSTOMER_NAME},
Your booking #{BOOKING_ID} confirmed.
```

---

# 7. Booking Variables

| Variable         | Source                      |
| ---------------- | --------------------------- |
| {BOOKING_ID}     | bookings.booking_id         |
| {CHECKIN_DATE}   | bookings.checkin_date       |
| {CHECKOUT_DATE}  | bookings.checkout_date      |
| {TOTAL_AMOUNT}   | bookings.total_amount       |
| {PAYMENT_STATUS} | bookings.payment_status     |
| {BOOKING_STATUS} | bookings.booking_status     |
| {ROOM_NAME}      | rooms.name                  |
| {CUSTOMER_NAME}  | customers.name              |
| {CUSTOMER_PHONE} | customers.phone             |
| {HOTEL_NAME}     | settings_basic.website_name |
| {HOTEL_PHONE}    | settings_basic.phone        |

---

# 8. Restaurant Variables

| Variable         | Source                           |
| ---------------- | -------------------------------- |
| {ORDER_NO}       | restaurant_orders.order_no       |
| {ORDER_AMOUNT}   | restaurant_orders.final_amount   |
| {ORDER_STATUS}   | restaurant_orders.order_status   |
| {TABLE_NAME}     | restaurant_tables.name           |
| {CUSTOMER_NAME}  | restaurant_orders.customer_name  |
| {CUSTOMER_PHONE} | restaurant_orders.customer_phone |

---

# 9. Payment Variables

| Variable         | Source                  |
| ---------------- | ----------------------- |
| {PAYMENT_AMOUNT} | payments.amount         |
| {PAYMENT_DATE}   | payments.payment_date   |
| {TRANSACTION_ID} | payments.transaction_id |

---

# 10. System Variables

| Variable       | Description         |
| -------------- | ------------------- |
| {CURRENT_DATE} | Current system date |
| {CURRENT_TIME} | Current system time |
| {CURRENT_YEAR} | Current year        |

---

# 11. Template Save Flow

## Step 1

Admin creates template.

Example:

```text
Dear {CUSTOMER_NAME},
Your booking #{BOOKING_ID} confirmed.
```

---

## Step 2

System detects variables automatically.

Regex:

```php
preg_match_all('/\{(.*?)\}/', $content, $matches);
```

---

## Step 3

Save variables JSON into:

```text
sms_templates.variables
```

Example:

```json
[
  "CUSTOMER_NAME",
  "BOOKING_ID"
]
```

---

# 12. Autocomplete System

## Flow

```text
Select SMS Type
    ↓
Load Entity Type
    ↓
Load Allowed Variables
    ↓
Show Autocomplete Suggestions
```

---

# 13. Single SMS Send

## Features

* Manual phone input
* Template selection
* Dynamic variable parsing
* Preview before send
* Instant send
* Schedule send

---

## Flow

```text
Select Template
    ↓
Load Recipient Data
    ↓
Resolve Variables
    ↓
Generate Final Message
    ↓
Send SMS
    ↓
Create sms_logs Record
```

---

# 14. Bulk SMS Campaign

## Supported Sources

* Contact Group
* Customer List
* Booking List
* Restaurant Customers
* Manual Numbers
* Uploaded CSV

---

## Campaign Flow

```text
Create Campaign
    ↓
Select Template
    ↓
Select Recipients
    ↓
Generate Messages
    ↓
Queue SMS
    ↓
Send Batch
    ↓
Update Logs
```

---

# 15. sms_campaigns Table Usage

| Column            | Description                 |
| ----------------- | --------------------------- |
| template_uid      | Selected template           |
| group_uid         | Target group                |
| numbers_json      | Manual numbers              |
| contact_uids_json | Contact list                |
| group_uids_json   | Multiple groups             |
| send_mode         | Instant / Scheduled         |
| status            | Draft / Running / Completed |

---

# 16. SMS Parsing Engine

## Core Logic

```php
foreach($variables as $key => $value){

   $message = str_replace(
      '{'.$key.'}',
      $value,
      $message
   );

}
```

---

# 17. Resolver System

Each entity has resolver class.

Examples:

```text
BookingVariableResolver
RestaurantOrderResolver
PaymentResolver
CustomerResolver
```

---

# 18. Booking Resolver Example

```php
[
  'CUSTOMER_NAME' => $booking->customer->name,
  'BOOKING_ID' => $booking->booking_id,
  'ROOM_NAME' => $booking->room->name,
  'CHECKIN_DATE' => $booking->checkin_date
]
```

---

# 19. Automation Rules

## sms_automation_rules Usage

| Event            | Action                    |
| ---------------- | ------------------------- |
| booking_created  | Send booking confirmation |
| payment_received | Send payment SMS          |
| order_ready      | Send food ready SMS       |

---

# 20. Automation Flow

```text
Event Triggered
    ↓
Find Active Rule
    ↓
Load Template
    ↓
Resolve Variables
    ↓
Queue SMS
```

---

# 21. SMS Queue System

Recommended future table:

```sql
CREATE TABLE sms_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20),
    message TEXT,
    status VARCHAR(30) DEFAULT 'Pending',
    retry_count INT DEFAULT 0,
    scheduled_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

# 22. SMS Logs

All outgoing SMS must create logs.

## sms_logs Responsibilities

* Delivery history
* Gateway response
* Failed SMS tracking
* Cost tracking
* Segment tracking

---

# 23. SMS Length Calculation

## English

160 characters = 1 segment

## Unicode/Bangla

70 characters = 1 segment

---

# 24. Multi-language Support

Supported:

* Bangla
* English
* Arabic
* Hindi

---

# 25. Multi-location Support

Templates support location-based variables.

Example:

```text
{HOTEL_NAME}
{HOTEL_PHONE}
{HOTEL_ADDRESS}
```

---

# 26. Security Rules

* Validate all variables
* Sanitize template content
* Prevent invalid variables
* Restrict template editing by role permissions

---

# 27. Future Upgrade Support

The same template engine should support:

* SMS
* WhatsApp
* Email
* Push Notifications
* In-App Notifications

Only delivery channel changes.

---

# 28. Recommended Services

```text
SmsTemplateParser
SmsVariableResolver
SmsCampaignService
SmsQueueWorker
SmsGatewayService
SmsAutomationService
```

---

# 29. Recommended API Endpoints

| Endpoint                    | Purpose         |
| --------------------------- | --------------- |
| GET /sms/templates          | Template list   |
| POST /sms/templates         | Create template |
| GET /sms/variables/{entity} | Variables       |
| POST /sms/send-single       | Single send     |
| POST /sms/send-bulk         | Bulk campaign   |
| GET /sms/logs               | SMS logs        |
| POST /sms/campaigns         | Create campaign |

---

# 30. Final Goal

The SMS Module must work as a reusable universal notification engine for the entire SaaS platform.

The system should support:

* Dynamic template parsing
* Automation
* Scalable campaigns
* Multi-entity notifications
* Multi-channel communication
* Enterprise SaaS architecture
