<?php
require_once __DIR__ . '/../_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to']   ?? '');
if ($to === '')   $to   = date('Y-m-d');
if ($from === '') $from = date('Y-m-01');

function ive_aggregate(mysqli $conn, $locationId, $from, $to, $type, $catTable) {
    $stmt = $conn->prepare("
        SELECT c.id, c.name,
               COALESCE(SUM(i.amount), 0) AS amt
        FROM $catTable c
        LEFT JOIN acc_voucher_items i ON i.category_id = c.id AND i.category_kind = ?
        LEFT JOIN acc_vouchers v      ON v.id = i.voucher_id AND v.type = ?
            AND v.location_id = ? AND v.voucher_date BETWEEN ? AND ?
        WHERE c.location_id = ? AND c.isactive = 1
        GROUP BY c.id, c.name
        ORDER BY c.id");
    $stmt->bind_param('ssissi', $type, $type, $locationId, $from, $to, $locationId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = []; $total = 0;
    while ($r = $res->fetch_assoc()) {
        $rows[] = ['name' => $r['name'], 'amount' => (float)$r['amt']];
        $total += (float)$r['amt'];
    }
    $stmt->close();
    return ['rows' => $rows, 'total' => $total];
}

$income  = ive_aggregate($conn, $locationId, $from, $to, 'income',  'acc_income_categories');
$expense = ive_aggregate($conn, $locationId, $from, $to, 'expense', 'acc_expense_categories');
$net = $income['total'] - $expense['total'];

jsonResponse(true, 'OK', [
    'from' => $from, 'to' => $to,
    'income' => $income, 'expense' => $expense,
    'net' => $net
]);
