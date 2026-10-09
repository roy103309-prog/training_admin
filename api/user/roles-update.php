<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$name = sanitize($input['name'] ?? '');
$isactive = (int)($input['isactive'] ?? 1);
if ($id <= 0 || empty($name)) jsonResponse(false, 'Invalid data');

$conn = getTenantConnection();
$stmt = $conn->prepare("UPDATE roles SET name = ?, isactive = ? WHERE id = ?");
$stmt->bind_param('sii', $name, $isactive, $id);
if ($stmt->execute()) jsonResponse(true, 'Role updated successfully');
else jsonResponse(false, 'Failed to update role');
