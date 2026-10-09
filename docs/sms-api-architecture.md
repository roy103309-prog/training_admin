# sms-api-architecture.md

# SaaS SMS API Architecture Specification

## Multi-Tenant SMS Gateway System

---

# 1. Overview

This SMS API architecture is designed for a centralized SaaS SMS gateway portal.

The system supports:

* Multi-project SMS management
* Multi-tenant SMS balance
* Dynamic SMS templates
* Single SMS
* Bulk SMS
* Automation SMS
* SMS logging
* Request tracking
* Queue processing
* Multi-language SMS
* Masking / Non-Masking SMS

---

# 2. Architecture Overview

```text id="1wy24j"
Tenant SaaS App
      ↓
Template Parser
      ↓
SMS API Request
      ↓
Central SMS Portal
      ↓
Balance Validation
      ↓
Queue System
      ↓
SMS Gateway
      ↓
Delivery Response
      ↓
SMS Logs
```

---

# 3. Main Components

| Component           | Responsibility                 |
| ------------------- | ------------------------------ |
| Tenant Application  | Generate SMS request           |
| Template Engine     | Parse dynamic variables        |
| Central SMS Portal  | Manage all projects & balances |
| Queue Worker        | Process SMS queue              |
| SMS Gateway Service | Send to provider               |
| SMS Logs            | Store all SMS history          |

---

# 4. Tenant SMS Flow

## Step 1

Event occurs.

Example:

```text id="jlwmm1"
Booking Created
```

---

## Step 2

Load SMS template.

Example:

```text id="jlwmm2"
booking_confirmation
```

---

## Step 3

Resolve variables.

Example:

```php id="jlwmm3"
[
  'CUSTOMER_NAME' => 'John',
  'BOOKING_ID' => 'BK-101'
]
```

---

## Step 4

Parse template.

Result:

```text id="jlwmm4"
Dear John,
Your booking #BK-101 confirmed.
```

---

## Step 5

Send API request to central portal.

---

# 5. SMS API Request

## Endpoint

```text id="jlwmm5"
POST /api/send-sms
```

---

## Request Body

```json id="jlwmm6"
{
  "project_id": 5,
  "tenant_id": "8",
  "sms": "Your OTP is 123456",
  "to": "8801XXXXXXXXX",
  "language": "english",
  "sms_type": "Non-Masking",
  "request_id": "uuid-xxxx"
}
```

---

# 6. Field Specification

| Field      | Required | Description                |
| ---------- | -------- | -------------------------- |
| project_id | YES      | Master SaaS project id     |
| tenant_id  | YES      | Tenant database owner id   |
| sms        | YES      | Final parsed SMS text      |
| to         | YES      | Receiver mobile number     |
| language   | NO       | english / bangla           |
| sms_type   | YES      | Masking / Non-Masking      |
| request_id | NO       | Unique request tracking id |

---

# 7. Recommended Improvements

Add these fields:

```json id="jlwmm7"
{
  "template_uid": "tmp_booking_confirm",
  "campaign_uid": "cmp_001",
  "source": "booking_confirmation",
  "priority": "normal",
  "schedule_at": null
}
```

---

# 8. Final Recommended Request Structure

```json id="jlwmm8"
{
  "project_id": 5,

  "tenant_id": "8",

  "template_uid": "tmp_booking_confirm",

  "campaign_uid": null,

  "source": "booking_confirmation",

  "sms": "Dear John, Booking confirmed.",

  "to": "8801XXXXXXXXX",

  "language": "english",

  "sms_type": "Non-Masking",

  "priority": "normal",

  "schedule_at": null,

  "request_id": "uuid-xxxx"
}
```

---

# 9. SMS Portal Responsibilities

The central SMS portal must handle:

* Tenant validation
* SMS balance validation
* Duplicate request prevention
* Queue processing
* Gateway communication
* SMS logs
* Billing
* Retry system

---

# 10. Tenant Validation

```text id="jlwmm9"
project_id + tenant_id
```

must match valid SaaS tenant.

---

# 11. Duplicate Request Prevention

Use:

```text id="jlwmma"
request_id
```

to prevent duplicate SMS sending.

---

# 12. Queue-Based Architecture

Never directly send SMS from tenant app.

Instead:

```text id="jlwmmb"
Tenant
  ↓
Portal API
  ↓
sms_queue
  ↓
Worker
  ↓
Gateway
```

---

# 13. Recommended Central Portal Tables

---

## sms_requests

Stores incoming tenant requests.

```sql id="jlwmmc"
CREATE TABLE sms_requests (

   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

   request_id VARCHAR(150) UNIQUE,

   project_id INT NOT NULL,

   tenant_id VARCHAR(80) NOT NULL,

   template_uid VARCHAR(80) NULL,

   campaign_uid VARCHAR(80) NULL,

   source VARCHAR(100) NULL,

   phone VARCHAR(30) NOT NULL,

   sms LONGTEXT NOT NULL,

   language VARCHAR(30) NULL,

   sms_type VARCHAR(30) NOT NULL,

   priority VARCHAR(30) DEFAULT 'normal',

   status VARCHAR(30) DEFAULT 'Pending',

   response LONGTEXT NULL,

   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## sms_queue

```sql id="jlwmmd"
CREATE TABLE sms_queue (

   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

   request_id VARCHAR(150),

   phone VARCHAR(30),

   sms LONGTEXT,

   status VARCHAR(30) DEFAULT 'Pending',

   retry_count INT DEFAULT 0,

   scheduled_at DATETIME NULL,

   processed_at DATETIME NULL,

   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## tenant_sms_balance

```sql id="jlwmme"
CREATE TABLE tenant_sms_balance (

   tenant_id VARCHAR(80) PRIMARY KEY,

   total_sms BIGINT DEFAULT 0,

   used_sms BIGINT DEFAULT 0,

   remaining_sms BIGINT DEFAULT 0,

   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

# 14. SMS Balance Flow

```text id="jlwmmf"
Receive Request
     ↓
Validate Balance
     ↓
Deduct SMS Units
     ↓
Insert Queue
     ↓
Send SMS
```

---

# 15. Language Detection

If language omitted:

## Detect Unicode

```php id="jlwmmg"
$isUnicode = preg_match('/[^\x00-\x7F]/', $sms);
```

---

## Result

| Condition     | Language |
| ------------- | -------- |
| Unicode found | bangla   |
| ASCII only    | english  |

---

# 16. SMS Segment Calculation

## English

160 chars = 1 SMS

## Bangla / Unicode

70 chars = 1 SMS

---

# 17. Single SMS Send

Single send directly creates:

```text id="jlwmmh"
sms_requests
sms_queue
sms_logs
```

---

# 18. Bulk SMS Send

Bulk SMS creates:

```text id="jlwmmi"
sms_campaigns
sms_requests
sms_queue
sms_logs
```

---

# 19. Bulk Campaign Flow

```text id="jlwmmj"
Select Recipients
      ↓
Generate Final SMS Per User
      ↓
Create Queue Batch
      ↓
Worker Sends SMS
```

---

# 20. Dynamic Template Send Flow

```text id="jlwmmk"
Template
   ↓
Variable Resolver
   ↓
Parsed Final SMS
   ↓
API Request
   ↓
Central SMS Portal
```

---

# 21. Important Rule

Tenant application should NEVER send raw template variables to portal.

BAD:

```text id="jlwmml"
Hello {CUSTOMER_NAME}
```

GOOD:

```text id="jlwmmm"
Hello John
```

The tenant app must send final parsed SMS.

---

# 22. Why This Is Best

Because:

* Portal stays lightweight
* Portal independent from tenant schema
* Faster processing
* Easier scaling
* Easier gateway switching

---

# 23. Recommended Tenant Services

```text id="jlwmmn"
SmsTemplateParser
SmsVariableResolver
SmsAutomationService
SmsCampaignService
SmsApiClient
```

---

# 24. Recommended Portal Services

```text id="jlwmmo"
SmsRequestValidator
SmsQueueService
SmsGatewayService
SmsBalanceService
SmsRetryWorker
SmsLogService
```

---

# 25. Retry System

If gateway fails:

```text id="jlwmmp"
retry_count++
```

Retry max:

```text id="jlwmmq"
3 attempts
```

---

# 26. Security Recommendations

* API token validation
* Request signature validation
* IP whitelist
* Request rate limiting
* Duplicate request blocking

---

# 27. Recommended Future Features

* WhatsApp API
* Email API
* Push Notification API
* Delivery webhook
* Real-time SMS reports
* Analytics dashboard

---

# 28. Final Architecture Goal

The central SMS portal should become:

```text id="jlwmmr"
Universal Multi-Tenant Notification Gateway
```

Supporting:

* SMS
* WhatsApp
* Email
* Push Notifications
* Voice SMS

using the same architecture.
