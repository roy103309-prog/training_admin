<?php
require_once '../tenant-config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in'])) {
    jsonResponse(false, 'Not authenticated');
}

// Refresh avatar from DB so header stays in sync after profile update
$avatar = $_SESSION['user_avatar'] ?? null;
try {
    $conn = getTenantConnection();
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $avatar = $row['avatar'];
        $_SESSION['user_avatar'] = $avatar;
    }
    $stmt->close();
} catch (Exception $e) { /* keep session value */ }

jsonResponse(true, 'Session valid', [
    'user' => [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'mobile' => $_SESSION['user_mobile'],
        'role_name' => $_SESSION['role_name'] ?? '',
        'avatar' => $avatar,
        'issuperadmin' => (bool)($_SESSION['issuperadmin'] ?? 0)
    ],
    'current_location_id' => getCurrentLocation(),
    'tenant_id' => $_SESSION['tenant_id'] ?? null,
    'tenant_database' => $_SESSION['tenant_database'] ?? null
]);
