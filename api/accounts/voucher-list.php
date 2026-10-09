<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$type = $_GET['type'] ?? '';
if (!in_array($type, ['income','expense','liability','transfer'], true)) jsonResponse(false, 'Invalid type');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo   = sanitize($_GET['date_to']   ?? '');

$where = "v.location_id = ? AND v.type = ?";
$types = 'is'; $params = [$locationId, $type];
if ($dateFrom !== '') { $where .= " AND v.voucher_date >= ?"; $types .= 's'; $params[] = $dateFrom; }
if ($dateTo   !== '') { $where .= " AND v.voucher_date <= ?"; $types .= 's'; $params[] = $dateTo; }

if ($type === 'transfer') {
    $sql = "SELECT v.id, v.voucher_no, v.voucher_date, v.from_asset_id, v.to_asset_id, v.total, v.note, v.created_at,
                   fa.name AS from_name, ta.name AS to_name
            FROM acc_vouchers v
            LEFT JOIN acc_asset_categories fa ON fa.id = v.from_asset_id
            LEFT JOIN acc_asset_categories ta ON ta.id = v.to_asset_id
            WHERE $where ORDER BY v.id DESC LIMIT 500";
} else {
    $sql = "SELECT v.id, v.voucher_no, v.voucher_date, v.asset_category_id, v.collected_by, v.total, v.transaction_type, v.want_receipt, v.note, v.created_at,
                   a.name AS asset_name
            FROM acc_vouchers v
            LEFT JOIN acc_asset_categories a ON a.id = v.asset_category_id
            WHERE $where ORDER BY v.id DESC LIMIT 500";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params); $stmt->execute();
$res = $stmt->get_result(); $items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();
jsonResponse(true, 'OK', ['items' => $items]);
