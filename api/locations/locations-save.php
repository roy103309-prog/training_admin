<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

// Only super admin can manage branches
if (empty($user['issuperadmin'])) jsonResponse(false, 'Super Admin privilege required');

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$id       = (int)($input['id'] ?? 0);
$name     = trim($input['name'] ?? '');
$address  = trim($input['address'] ?? '');
$phone    = trim($input['phone'] ?? '');
$email    = trim($input['email'] ?? '');
$isactive = (int)($input['isactive'] ?? 1);

if ($name === '') jsonResponse(false, 'Name is required');

$conn = getTenantConnection();

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE locations SET name=?, address=?, phone=?, email=?, isactive=? WHERE id=?");
    $stmt->bind_param('ssssii', $name, $address, $phone, $email, $isactive, $id);
    if (!$stmt->execute()) jsonResponse(false, 'Update failed');
    $stmt->close();
    jsonResponse(true, 'Branch updated');
} else {
    $stmt = $conn->prepare("INSERT INTO locations (name, address, phone, email, isactive) VALUES (?,?,?,?,?)");
    $stmt->bind_param('ssssi', $name, $address, $phone, $email, $isactive);
    if (!$stmt->execute()) jsonResponse(false, 'Create failed');
    $newId = $conn->insert_id;
    $stmt->close();
    jsonResponse(true, 'Branch created', ['id' => $newId]);
}
