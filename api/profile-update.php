<?php
require_once 'tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$name   = trim($input['name']   ?? '');
$mobile = trim($input['mobile'] ?? '');
$avatar = isset($input['avatar']) ? (string)$input['avatar'] : null; // base64 data URL or '' to clear; null = leave unchanged

if ($name === '')   jsonResponse(false, 'Name is required');
if ($mobile === '') jsonResponse(false, 'Mobile is required');

$conn = getTenantConnection();
$userId = (int)$user['user_id'];

// Uniqueness check (mobile)
$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ? AND id != ? LIMIT 1");
$stmt->bind_param('si', $mobile, $userId);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    jsonResponse(false, 'Mobile number already in use by another user');
}
$stmt->close();

if ($avatar === null) {
    $stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ? WHERE id = ?");
    $stmt->bind_param('ssi', $name, $mobile, $userId);
} else {
    // Fetch old avatar path so we can clean up any previous file
    $oldStmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $oldStmt->bind_param('i', $userId);
    $oldStmt->execute();
    $oldAvatar = ($oldStmt->get_result()->fetch_assoc()['avatar'] ?? null);
    $oldStmt->close();

    // Decode base64 -> save under uploads/<tenant_db>/users/ ; '' clears; URL passes through
    $saved = saveBase64Image($avatar, 'users', $oldAvatar);
    $avatarVal = ($saved === '' || $saved === null) ? null : $saved;

    $stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ?, avatar = ? WHERE id = ?");
    $stmt->bind_param('sssi', $name, $mobile, $avatarVal, $userId);
}
if (!$stmt->execute()) {
    jsonResponse(false, 'Update failed');
}
$stmt->close();

// Refresh session values so UI reflects immediately
$_SESSION['user_name']   = $name;
$_SESSION['user_mobile'] = $mobile;
if ($avatar !== null) {
    $_SESSION['user_avatar'] = isset($avatarVal) ? ($avatarVal ?? '') : '';
}

jsonResponse(true, 'Profile updated successfully', [
    'avatar' => $_SESSION['user_avatar'] ?? null
]);
