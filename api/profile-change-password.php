<?php
require_once 'tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input           = json_decode(file_get_contents('php://input'), true) ?: [];
$currentPassword = (string)($input['current_password'] ?? '');
$newPassword     = (string)($input['new_password']     ?? '');
$confirmPassword = (string)($input['confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '') jsonResponse(false, 'All fields required');
if (strlen($newPassword) < 6)                        jsonResponse(false, 'New password must be at least 6 characters');
if ($newPassword !== $confirmPassword)               jsonResponse(false, 'Passwords do not match');

$conn = getTenantConnection();
$userId = (int)$user['user_id'];

$stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) jsonResponse(false, 'User not found');

if ($currentPassword !== $row['password_hash']) {
    jsonResponse(false, 'Current password is incorrect');
}

$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$stmt->bind_param('si', $newPassword, $userId);
if (!$stmt->execute()) jsonResponse(false, 'Update failed');
$stmt->close();

jsonResponse(true, 'Password changed successfully');
