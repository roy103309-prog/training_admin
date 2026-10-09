<?php
// Delete one weekday rule for an employee. payload: { user_id, day_of_week }
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = (int)($in['user_id'] ?? 0);
$day    = (int)($in['day_of_week'] ?? -1);
if ($userId <= 0 || $day < 0 || $day > 6) jsonResponse(false, 'Invalid data');
$conn = getTenantConnection();
$stmt = $conn->prepare("DELETE FROM att_settings WHERE location_id = ? AND user_id = ? AND day_of_week = ?");
$stmt->bind_param('iii', $locationId, $userId, $day); $stmt->execute(); $stmt->close();
jsonResponse(true, 'Deleted');
