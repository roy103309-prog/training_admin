<?php
require_once __DIR__ . '/../_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to']   ?? '');
$fromId = (int)($_GET['from_asset_id'] ?? 0);
$toId   = (int)($_GET['to_asset_id']   ?? 0);
if ($to === '')   $to   = date('Y-m-d');
if ($from === '') $from = date('Y-m-01');

$where  = "v.location_id = ? AND v.type = 'transfer' AND v.voucher_date BETWEEN ? AND ?";
$types  = 'iss';
$params = [$locationId, $from, $to];
if ($fromId > 0) { $where .= " AND v.from_asset_id = ?"; $types .= 'i'; $params[] = $fromId; }
if ($toId   > 0) { $where .= " AND v.to_asset_id = ?";   $types .= 'i'; $params[] = $toId; }

$sql = "
    SELECT v.id, v.voucher_no, v.voucher_date,
           fa.name AS from_name, ta.name AS to_name,
           v.total, v.collected_by, v.note
    FROM acc_vouchers v
    LEFT JOIN acc_asset_categories fa ON fa.id = v.from_asset_id
    LEFT JOIN acc_asset_categories ta ON ta.id = v.to_asset_id
    WHERE $where
    ORDER BY v.voucher_date DESC, v.id DESC
    LIMIT 5000";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params); $stmt->execute();
$res = $stmt->get_result();
$rows = []; $total = 0;
while ($r = $res->fetch_assoc()) { $rows[] = $r; $total += (float)$r['total']; }
$stmt->close();

jsonResponse(true, 'OK', ['from' => $from, 'to' => $to, 'rows' => $rows,
                          'summary' => ['count' => count($rows), 'total' => $total]]);
