<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid ID');

$conn = getTenantConnection();
$stmt = $conn->prepare("DELETE FROM menus WHERE id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    if ($affectedRows > 0) {
        jsonResponse(true, 'Menu deleted successfully');
    } else {
        jsonResponse(false, 'Menu not found or already deleted');
    }
} else {
    $error = $stmt->error;
    $stmt->close();
    jsonResponse(false, 'Failed to delete menu: ' . $error);
}
