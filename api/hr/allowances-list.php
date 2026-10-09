<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$stmt = $conn->prepare("SELECT * FROM allowance_deductions WHERE location_id = ? ORDER BY id DESC");
$stmt->bind_param('i', $locationId);
$stmt->execute();
$res = $stmt->get_result();
$items = []; while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();
jsonResponse(true, 'OK', ['items' => $items]);
