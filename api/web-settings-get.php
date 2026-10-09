<?php
require_once __DIR__ . '/web-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$all = [];
$res = $conn->query("SELECT setting_key, setting_value FROM web_settings");
while ($r = $res->fetch_assoc()) $all[$r['setting_key']] = $r['setting_value'];
$res->free();

$out = [];
foreach (array_merge(WEB_SETTING_KEYS, WEB_SETTING_IMAGES) as $k) $out[$k] = $all[$k] ?? '';
jsonResponse(true, 'OK', ['settings' => $out]);
