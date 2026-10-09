<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid ID');
if ($id == 1) jsonResponse(false, 'Cannot delete default super admin');

$conn = getTenantConnection();
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute() && $stmt->affected_rows > 0) jsonResponse(true, 'User deleted successfully');
else jsonResponse(false, 'Failed to delete user');
