<?php
require_once __DIR__ . '/config.php';

$conn = SmsService::boot();
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$data = SmsService::requestData();

try {
    switch ($method) {
        case 'GET':  handleList($conn, $data); break;
        case 'POST': handlePurchase($conn, $data); break;
        case 'PUT':  handleApprove($conn, $data); break;
        default: SmsResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}

function handleList(mysqli $conn, array $data): void {
    $result = $conn->query("SELECT id, uid, invoice_no, package_name, sms_count, bonus_sms, total_sms, amount, payment_method, status, balance_applied, paid_at, created_at FROM sms_transactions ORDER BY id DESC LIMIT 200");
    $txns = [];
    while ($row = $result->fetch_assoc()) {
        $txns[] = [
            'id' => $row['uid'] ?? $row['id'],
            'invoice_no' => $row['invoice_no'],
            'package_name' => $row['package_name'],
            'sms_count' => (int)$row['sms_count'],
            'bonus_sms' => (int)$row['bonus_sms'],
            'total_sms' => (int)$row['total_sms'],
            'amount' => (float)$row['amount'],
            'payment_method' => $row['payment_method'],
            'status' => $row['status'],
            'balance_applied' => (int)$row['balance_applied'],
            'paid_at' => $row['paid_at'],
            'created_at' => $row['created_at']
        ];
    }
    $result->free();
    SmsResponse::success('Transactions retrieved', ['transactions' => $txns]);
}

function handlePurchase(mysqli $conn, array $data): void {
    $packageUid = trim($data['package_uid'] ?? '');
    $paymentMethod = trim($data['payment_method'] ?? 'Cash');
    $transactionRef = trim($data['transaction_ref'] ?? '');

    if ($packageUid === '') SmsResponse::error('Package is required', 400);

    // Fetch package
    $stmt = $conn->prepare("SELECT uid, name, sms_count, bonus_sms, price FROM sms_packages WHERE (uid=? OR id=?) AND status='Active' LIMIT 1");
    $stmt->bind_param('ss', $packageUid, $packageUid);
    $stmt->execute();
    $res = $stmt->get_result();
    $pkg = $res->fetch_assoc();
    $res->free();
    $stmt->close();

    if (!$pkg) SmsResponse::error('Package not found', 404);

    $uid = SmsService::ensureUid('', 'txn');
    $invoice = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $totalSms = (int)$pkg['sms_count'] + (int)$pkg['bonus_sms'];
    $amount = (float)$pkg['price'];
    $now = SmsService::now();
    $userId = SmsService::userId();
    $status = 'Pending';

    $stmt = $conn->prepare("INSERT INTO sms_transactions (uid, invoice_no, transaction_ref, package_uid, package_name, sms_count, bonus_sms, total_sms, amount, payment_method, status, balance_applied, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
    $stmt->bind_param('sssssiiidssss', $uid, $invoice, $transactionRef, $pkg['uid'], $pkg['name'], $pkg['sms_count'], $pkg['bonus_sms'], $totalSms, $amount, $paymentMethod, $status, $userId, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Purchase request submitted', ['id' => $uid, 'invoice_no' => $invoice, 'total_sms' => $totalSms, 'amount' => $amount]);
    } else {
        SmsResponse::error('Failed to create transaction: ' . $stmt->error, 500);
    }
    $stmt->close();
}

function handleApprove(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if ($id === '') SmsResponse::error('Transaction ID is required', 400);

    // Fetch txn
    $stmt = $conn->prepare("SELECT uid, total_sms, balance_applied, status FROM sms_transactions WHERE uid=? OR id=? LIMIT 1");
    $stmt->bind_param('ss', $id, $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $txn = $res->fetch_assoc();
    $res->free();
    $stmt->close();

    if (!$txn) SmsResponse::error('Transaction not found', 404);
    if ((int)$txn['balance_applied'] === 1) SmsResponse::error('Already applied', 400);

    $totalSms = (int)$txn['total_sms'];
    $now = SmsService::now();

    $conn->begin_transaction();
    try {
        $u1 = $conn->prepare("UPDATE sms_balance SET total_sms = total_sms + ?, remaining_sms = remaining_sms + ?, updated_at=? WHERE id=1");
        $u1->bind_param('iis', $totalSms, $totalSms, $now);
        $u1->execute();
        $u1->close();

        $u2 = $conn->prepare("UPDATE sms_transactions SET status='Paid', balance_applied=1, paid_at=?, applied_at=? WHERE uid=? OR id=?");
        $u2->bind_param('ssss', $now, $now, $id, $id);
        $u2->execute();
        $u2->close();

        $conn->commit();
        SmsResponse::success('Transaction approved', ['id' => $id, 'balance' => SmsService::getBalance()]);
    } catch (Exception $e) {
        $conn->rollback();
        SmsResponse::error('Failed to approve: ' . $e->getMessage(), 500);
    }
}
