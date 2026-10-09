<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name      = sanitize($in['name'] ?? '');
$type      = in_array($in['type'] ?? '', ['allowance','deduction'], true) ? $in['type'] : 'allowance';
$valueType = in_array($in['value_type'] ?? '', ['fixed','percentage'], true) ? $in['value_type'] : 'fixed';
$amount    = (float)($in['amount'] ?? 0);
$isactive  = (int)($in['isactive'] ?? 1);
if ($name === '') jsonResponse(false, 'Name required');

$stmt = $conn->prepare("INSERT INTO allowance_deductions (location_id, name, type, value_type, amount, isactive) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('isssdi', $locationId, $name, $type, $valueType, $amount, $isactive);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$id = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Saved', ['id' => $id]);
