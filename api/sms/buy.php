<?php
/**
 * POST api/sms/buy.php
 *
 * Buy SMS credit — proxies to FamilyPortal topup API.
 * This endpoint is designed for payment gateway integration.
 *
 * Body (JSON):
 * {
 *     "amount":       100.00,       // required, BDT amount (credited to the
 *                                   //           single shared wallet — global)
 *     "payment_ref":  "bkash_TXN_001", // optional gateway reference
 *     "note":         "Payment via bKash"
 * }
 *
 * Response:
 * {
 *     "status": true,
 *     "message": "Top-up successful",
 *     "data": { "user_id": 9, "amount_added": 100, "balance": 250, "currency": "BDT" }
 * }
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/PortalSms.php';

$conn = SmsService::boot();
SmsService::requireMethod(['POST']);

$body = SmsService::requestData();
$amount = (float)($body['amount'] ?? 0);
$paymentRef = trim((string)($body['payment_ref'] ?? ''));
$note = trim((string)($body['note'] ?? 'SMS credit purchase'));

if ($amount <= 0) {
    SmsResponse::error('amount must be greater than 0', 400);
}

$tenantId = SmsService::tenantId();
if ($tenantId === '' || $tenantId === '0') {
    SmsResponse::error('No tenant_id available', 400);
}

$portal = PortalSms::instance();
if (!$portal->isEnabled()) {
    SmsResponse::error('Family Portal SMS is disabled', 500);
}

$result = $portal->topup($tenantId, $amount, $paymentRef, $note);

if (empty($result['ok'])) {
    $msg = $result['data']['message'] ?? 'Top-up failed';
    $http = $result['http'] ?: 502;
    SmsResponse::error($msg, $http);
}

// Log local transaction record
$now = SmsService::now();
$actor = SmsService::userId();
$uid = SmsService::ensureUid('', 'buy');
$invoiceNo = 'SMS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

$stmt = $conn->prepare(
    "INSERT INTO sms_transactions
     (uid, invoice_no, transaction_ref, package_uid, package_name, sms_count, bonus_sms, total_sms, amount, payment_method, status, balance_applied, created_by, created_at)
     VALUES (?, ?, ?, '', 'Direct Purchase', 0, 0, 0, ?, 'Gateway', 'Completed', 1, ?, ?)"
);
$stmt->bind_param('sssdis', $uid, $invoiceNo, $paymentRef, $amount, $actor, $now);
$stmt->execute();
$stmt->close();

SmsResponse::success(
    $result['data']['message'] ?? 'Top-up successful',
    $result['data']['data'] ?? $result['data']
);
