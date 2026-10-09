<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = (int)(getCurrentLocation() ?: 0);

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id  = (int)($input['id']  ?? 0);
$all = !empty($input['all']);

$conn = getTenantConnection();

if ($all) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE location_id = ? AND is_read = 0");
    $stmt->bind_param('i', $locationId);
    $stmt->execute();
    $stmt->close();
    jsonResponse(true, 'All marked as read');
}

if ($id <= 0) jsonResponse(false, 'Notification id required');

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND location_id = ?");
$stmt->bind_param('ii', $id, $locationId);
$stmt->execute();
$stmt->close();
jsonResponse(true, 'Marked as read');
