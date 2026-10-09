<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'ID required');

$stmt = $conn->prepare("SELECT v.*, a.name AS asset_name FROM acc_vouchers v
                        LEFT JOIN acc_asset_categories a ON a.id = v.asset_category_id
                        WHERE v.id = ? AND v.location_id = ?");
$stmt->bind_param('ii', $id, $locationId); $stmt->execute();
$v = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$v) jsonResponse(false, 'Not found');

$stmt = $conn->prepare("SELECT id, category_kind, category_id, description, payment_method_id, amount FROM acc_voucher_items WHERE voucher_id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$res = $stmt->get_result(); $items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();

jsonResponse(true, 'OK', ['voucher' => $v, 'items' => $items]);
