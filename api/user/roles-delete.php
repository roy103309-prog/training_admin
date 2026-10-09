<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid ID');

$conn = getTenantConnection();
$stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute() && $stmt->affected_rows > 0) jsonResponse(true, 'Role deleted successfully');
else jsonResponse(false, 'Failed to delete role');
