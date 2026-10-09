<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$voucherDate = sanitize($in['voucher_date'] ?? date('Y-m-d'));
$fromId      = (int)($in['from_asset_id'] ?? 0);
$toId        = (int)($in['to_asset_id'] ?? 0);
$amount      = (float)($in['amount'] ?? 0);
$note        = sanitize($in['note'] ?? '');
$createdBy   = (int)($user['user_id'] ?? 0);
if ($fromId <= 0 || $toId <= 0) jsonResponse(false, 'From and To accounts required');
if ($fromId === $toId)          jsonResponse(false, 'From and To must differ');
if ($amount <= 0)               jsonResponse(false, 'Amount must be > 0');

$voucherNo = accGenVoucherNo($conn, $locationId, 'TRF');
$type = 'transfer';
$stmt = $conn->prepare("INSERT INTO acc_vouchers
    (location_id, voucher_no, type, voucher_date, from_asset_id, to_asset_id, total, note, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('isssiidsi', $locationId, $voucherNo, $type, $voucherDate, $fromId, $toId, $amount, $note, $createdBy);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$id = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Transfer recorded', ['id' => $id, 'voucher_no' => $voucherNo]);
