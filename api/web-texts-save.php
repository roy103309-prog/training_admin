<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

// JSON: {"texts": {"<key>": {"bn": "...", "en": "..."}}}
// A text equal to the default (or empty) removes the edit, so the default shows again.
$in = nlInput();
$texts = is_array($in['texts'] ?? null) ? $in['texts'] : [];
$allowed = [];
foreach (WEB_TEXT_TABS as $tab) foreach ($tab['keys'] as $key) $allowed[$key] = true;
$defaults = webTextDefaults();

$upsert = $conn->prepare("INSERT INTO web_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
$delete = $conn->prepare("DELETE FROM web_settings WHERE setting_key = ?");
foreach ($texts as $key => $pair) {
    if (!isset($allowed[$key]) || !is_array($pair)) continue;
    foreach (['bn', 'en'] as $lang) {
        if (!array_key_exists($lang, $pair)) continue;
        $value = nlClean($pair[$lang], 5000);
        $settingKey = 'text_' . $lang . ':' . $key;
        if ($value === '' || $value === trim((string)($defaults[$lang][$key] ?? ''))) {
            $delete->bind_param('s', $settingKey);
            $delete->execute();
        } else {
            $upsert->bind_param('ss', $settingKey, $value);
            $upsert->execute();
        }
    }
}
$upsert->close();
$delete->close();
jsonResponse(true, 'Website texts saved');
