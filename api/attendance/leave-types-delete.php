<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($in['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'ID required');
$conn = getTenantConnection();
$stmt = $conn->prepare("DELETE FROM att_leave_types WHERE id = ? AND location_id = ?");
$stmt->bind_param('ii', $id, $locationId); $stmt->execute(); $stmt->close();
jsonResponse(true, 'Deleted');
