<?php
require_once __DIR__ . '/../newlife-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$stored = [];
$res = $conn->query("SELECT setting_key, setting_value FROM app_settings");
while ($r = $res->fetch_assoc()) $stored[$r['setting_key']] = $r['setting_value'];
$res->free();

$defs = nlSettingDefinitions();
$values = [];
foreach ($defs as $group) {
    foreach ($group['fields'] as $key => $f) {
        $values[$key] = array_key_exists($key, $stored) ? $stored[$key] : (string)($f['default'] ?? '');
    }
}

jsonResponse(true, 'OK', ['groups' => $defs, 'values' => $values]);
