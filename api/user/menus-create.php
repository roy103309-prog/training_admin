<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$name = sanitize($input['name'] ?? '');
$icon = sanitize($input['icon'] ?? '');
$url = sanitize($input['url'] ?? '');
$parentId = (int)($input['parent_id'] ?? 0);
$sortOrder = (int)($input['sort_order'] ?? 0);
$isactive = (int)($input['isactive'] ?? 1);
if (empty($name)) jsonResponse(false, 'Name is required');

$parentIdVal = $parentId > 0 ? $parentId : null;
$conn = getTenantConnection();
$stmt = $conn->prepare("INSERT INTO menus (name, icon, url, parent_id, sort_order, isactive) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssiii', $name, $icon, $url, $parentIdVal, $sortOrder, $isactive);
if ($stmt->execute()) {
    $insertId = $conn->insert_id;
    $stmt->close();
    jsonResponse(true, 'Menu created successfully', ['id' => $insertId]);
} else {
    $error = $stmt->error;
    $stmt->close();
    jsonResponse(false, 'Failed to create menu: ' . $error);
}
