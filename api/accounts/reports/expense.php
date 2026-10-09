<?php
require_once __DIR__ . '/../_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$from    = trim($_GET['from'] ?? '');
$to      = trim($_GET['to']   ?? '');
$catId   = (int)($_GET['expense_category_id'] ?? 0);
$assetId = (int)($_GET['asset_category_id'] ?? 0);
if ($to === '')   $to   = date('Y-m-d');
if ($from === '') $from = date('Y-m-01');

$where  = "v.location_id = ? AND v.type = 'expense' AND i.category_kind = 'expense' AND v.voucher_date BETWEEN ? AND ?";
$types  = 'iss';
$params = [$locationId, $from, $to];
if ($catId   > 0) { $where .= " AND i.category_id = ?";       $types .= 'i'; $params[] = $catId; }
if ($assetId > 0) { $where .= " AND v.asset_category_id = ?"; $types .= 'i'; $params[] = $assetId; }

$sql = "
    SELECT v.id AS voucher_id, v.voucher_no, v.voucher_date, v.collected_by,
           a.name  AS asset_name,
           ec.name AS expense_category,
           i.description,
           pm.name AS payment_method,
           i.amount
    FROM acc_voucher_items i
    JOIN acc_vouchers v                ON v.id = i.voucher_id
    LEFT JOIN acc_asset_categories   a  ON a.id = v.asset_category_id
    LEFT JOIN acc_expense_categories ec ON ec.id = i.category_id
    LEFT JOIN payment_methods        pm ON pm.id = i.payment_method_id
    WHERE $where
    ORDER BY v.voucher_date DESC, v.id DESC, i.id ASC
    LIMIT 5000";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params); $stmt->execute();
$res = $stmt->get_result();
$rows = []; $total = 0;
while ($r = $res->fetch_assoc()) { $rows[] = $r; $total += (float)$r['amount']; }
$stmt->close();

jsonResponse(true, 'OK', [
    'from' => $from, 'to' => $to,
    'rows' => $rows,
    'summary' => ['count' => count($rows), 'total' => $total]
]);
