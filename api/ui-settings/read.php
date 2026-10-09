<?php
require_once '../tenant-config.php';

requireAuth();

$conn = getTenantConnection();

$out = [];
$res = $conn->query("SELECT setting_key, setting_value FROM ui_color_settings");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[$row['setting_key']] = (string)($row['setting_value'] ?? '');
    }
    $res->free();
}

jsonResponse(true, 'OK', $out);
