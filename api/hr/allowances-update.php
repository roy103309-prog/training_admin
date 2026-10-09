<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id        = (int)($in['id'] ?? 0);
$name      = sanitize($in['name'] ?? '');
$type      = in_array($in['type'] ?? '', ['allowance','deduction'], true) ? $in['type'] : 'allowance';
$valueType = in_array($in['value_type'] ?? '', ['fixed','percentage'], true) ? $in['value_type'] : 'fixed';
$amount    = (float)($in['amount'] ?? 0);
$isactive  = (int)($in['isactive'] ?? 1);
if ($id <= 0) jsonResponse(false, 'ID required');

$stmt = $conn->prepare("UPDATE allowance_deductions SET name = ?, type = ?, value_type = ?, amount = ?, isactive = ? WHERE id = ? AND location_id = ?");
$stmt->bind_param('sssdiii', $name, $type, $valueType, $amount, $isactive, $id, $locationId);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Updated');
