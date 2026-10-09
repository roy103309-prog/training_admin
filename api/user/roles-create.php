<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$name = sanitize($input['name'] ?? '');
$isactive = (int)($input['isactive'] ?? 1);
if (empty($name)) jsonResponse(false, 'Name is required');

$conn = getTenantConnection();
$stmt = $conn->prepare("INSERT INTO roles (name, isactive) VALUES (?, ?)");
$stmt->bind_param('si', $name, $isactive);
if ($stmt->execute()) jsonResponse(true, 'Role created successfully', ['id' => $conn->insert_id]);
else jsonResponse(false, 'Failed to create role');
