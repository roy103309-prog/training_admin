<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$name = sanitize($input['name'] ?? '');
$icon = sanitize($input['icon'] ?? '');
$url = sanitize($input['url'] ?? '');
$parentId = (int)($input['parent_id'] ?? 0);
$sortOrder = (int)($input['sort_order'] ?? 0);
$isactive = (int)($input['isactive'] ?? 1);
if ($id <= 0 || empty($name)) jsonResponse(false, 'Invalid data');

$parentIdVal = $parentId > 0 ? $parentId : null;
$conn = getTenantConnection();
$stmt = $conn->prepare("UPDATE menus SET name = ?, icon = ?, url = ?, parent_id = ?, sort_order = ?, isactive = ? WHERE id = ?");
$stmt->bind_param('sssiiii', $name, $icon, $url, $parentIdVal, $sortOrder, $isactive, $id);
if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(true, 'Menu updated successfully');
} else {
    $error = $stmt->error;
    $stmt->close();
    jsonResponse(false, 'Failed to update menu: ' . $error);
}
