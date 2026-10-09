<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

// Previous image paths, removed after a replacement
$oldImages = [];
$res = $conn->query("SELECT setting_key, setting_value FROM web_settings WHERE setting_key IN ('" . implode("','", WEB_SETTING_IMAGES) . "')");
while ($r = $res->fetch_assoc()) $oldImages[$r['setting_key']] = $r['setting_value'];
$res->free();

// multipart/form-data: text keys + optional image files
$stmt = $conn->prepare("INSERT INTO web_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
foreach (WEB_SETTING_KEYS as $k) {
    if (!array_key_exists($k, $_POST)) continue;
    $v = nlClean($_POST[$k], 2000);
    $stmt->bind_param('ss', $k, $v);
    $stmt->execute();
}
foreach (WEB_SETTING_IMAGES as $k) {
    $path = nlUploadImage($k, 'site');
    if ($path === null) continue;
    $stmt->bind_param('ss', $k, $path);
    $stmt->execute();
    if (!empty($oldImages[$k])) nlDeleteFile($oldImages[$k]);
}
$stmt->close();
jsonResponse(true, 'Website settings saved');
