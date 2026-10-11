<?php
require_once __DIR__ . '/web-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

// Edited texts (web_settings text_bn:<key> / text_en:<key>)
$edited = [];
$res = $conn->query("SELECT setting_key, setting_value FROM web_settings WHERE setting_key LIKE 'text\\_%'");
while ($r = $res->fetch_assoc()) $edited[$r['setting_key']] = (string)$r['setting_value'];
$res->free();

$defaults = webTextDefaults();
$tabs = [];
foreach (WEB_TEXT_TABS as $id => $tab) {
    $items = [];
    foreach ($tab['keys'] as $key) {
        $item = ['key' => $key];
        foreach (['bn', 'en'] as $lang) {
            $default = (string)($defaults[$lang][$key] ?? '');
            $saved = $edited['text_' . $lang . ':' . $key] ?? '';
            $item[$lang] = $saved !== '' ? $saved : $default;
            $item[$lang . '_default'] = $default;
        }
        $items[] = $item;
    }
    $tabs[] = ['id' => $id, 'title' => $tab['title'], 'items' => $items];
}
jsonResponse(true, 'OK', ['tabs' => $tabs]);
