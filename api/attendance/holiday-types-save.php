<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id       = (int)($in['id'] ?? 0);
$name     = sanitize($in['name'] ?? '');
$isactive = (int)($in['isactive'] ?? 1);
if ($name === '') jsonResponse(false, 'Name required');

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE att_holiday_types SET name = ?, isactive = ? WHERE id = ? AND location_id = ?");
    $stmt->bind_param('siii', $name, $isactive, $id, $locationId);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Updated');
}
$stmt = $conn->prepare("INSERT INTO att_holiday_types (location_id, name, isactive) VALUES (?, ?, ?)");
$stmt->bind_param('isi', $locationId, $name, $isactive);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$nid = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Created', ['id' => $nid]);
