<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$type = $in['type'] ?? '';
if (!in_array($type, ['income','expense','liability'], true)) jsonResponse(false, 'Invalid type');

$voucherDate    = sanitize($in['voucher_date'] ?? date('Y-m-d'));
$assetCatId     = (int)($in['asset_category_id'] ?? 0);
// Collected By auto = logged-in user
$collectedBy    = sanitize($user['name'] ?? ($_SESSION['user_name'] ?? ''));
$wantReceipt    = (int)($in['want_receipt'] ?? 0);
$note           = sanitize($in['note'] ?? '');
$items          = is_array($in['items'] ?? null) ? $in['items'] : [];
$txType         = in_array($in['transaction_type'] ?? '', ['pay','receive'], true) ? $in['transaction_type'] : null;
$createdBy      = (int)($user['user_id'] ?? 0);
if ($assetCatId <= 0) jsonResponse(false, 'Asset category required');
if (!$items)         jsonResponse(false, 'Add at least one detail row');
if ($type === 'liability' && !$txType) jsonResponse(false, 'Transaction type (pay/receive) required for liability');

$prefixMap = ['income' => 'INC', 'expense' => 'EXP', 'liability' => 'LIA'];
$voucherNo = accGenVoucherNo($conn, $locationId, $prefixMap[$type]);

$total = 0;
foreach ($items as $it) $total += (float)($it['amount'] ?? 0);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO acc_vouchers
        (location_id, voucher_no, type, voucher_date, asset_category_id, collected_by, total, transaction_type, want_receipt, note, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssisdsisi', $locationId, $voucherNo, $type, $voucherDate, $assetCatId, $collectedBy, $total, $txType, $wantReceipt, $note, $createdBy);
    if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); $conn->rollback(); jsonResponse(false, 'Failed: ' . $err); }
    $vid = $conn->insert_id; $stmt->close();

    $ins = $conn->prepare("INSERT INTO acc_voucher_items (voucher_id, category_kind, category_id, description, payment_method_id, amount) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($items as $it) {
        $catId  = (int)($it['category_id'] ?? 0);
        $desc   = trim($it['description'] ?? '');
        $pmId   = isset($it['payment_method_id']) && (int)$it['payment_method_id'] > 0 ? (int)$it['payment_method_id'] : null;
        $amt    = (float)($it['amount'] ?? 0);
        if ($catId <= 0 || $amt <= 0) continue;
        $kind = $type; // matches category_kind enum
        $ins->bind_param('isissd', $vid, $kind, $catId, $desc, $pmId, $amt);
        $ins->execute();
    }
    $ins->close();
    $conn->commit();
    jsonResponse(true, 'Voucher saved', ['id' => $vid, 'voucher_no' => $voucherNo, 'total' => $total]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed: ' . $e->getMessage());
}
