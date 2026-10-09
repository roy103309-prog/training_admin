<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name      = sanitize($in['name']       ?? '');
$shortName = sanitize($in['short_name'] ?? '');
$isactive  = (int)($in['isactive'] ?? 1);

if ($name === '') jsonResponse(false, 'Name is required');

$stmt = $conn->prepare("INSERT INTO grades (location_id, name, short_name, isactive) VALUES (?, ?, ?, ?)");
$stmt->bind_param('issi', $locationId, $name, $shortName, $isactive);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$id = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Grade created', ['id' => $id]);
