<?php
require_once '../tenant-config.php';

header('Content-Type: application/json');

$user = requireAuth();
$locationId = getCurrentLocation();

if (!$locationId) {
    jsonResponse(false, 'No location selected');
}

$conn = getTenantConnection();

function statScalar(mysqli $conn, $sql) {
    $res = @$conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_row();
    $res->free();
    return $row ? $row[0] : 0;
}

$stats = [];
$stats['total_users']     = (int) statScalar($conn, "SELECT COUNT(*) FROM users");
$stats['total_employees'] = (int) statScalar($conn, "SELECT COUNT(*) FROM users WHERE isactive = 1");
$stats['total_roles']     = (int) statScalar($conn, "SELECT COUNT(*) FROM roles WHERE isactive = 1");
$stats['total_branches']  = (int) statScalar($conn, "SELECT COUNT(*) FROM locations WHERE isactive = 1");

jsonResponse(true, 'Dashboard statistics retrieved', $stats);
