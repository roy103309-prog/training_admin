<?php
require_once 'tenant-config.php';
$user = requireAuth();

$conn = getTenantConnection();
$stmt = $conn->prepare("SELECT u.id, u.name, u.mobile, u.avatar, u.role_id, u.location_id,
                               u.issuperadmin, u.isactive, u.created_at, u.updated_at,
                               r.name AS role_name, l.name AS location_name
                        FROM users u
                        LEFT JOIN roles r     ON r.id = u.role_id
                        LEFT JOIN locations l ON l.id = u.location_id
                        WHERE u.id = ?");
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) jsonResponse(false, 'Profile not found');

jsonResponse(true, 'OK', ['profile' => $profile]);
