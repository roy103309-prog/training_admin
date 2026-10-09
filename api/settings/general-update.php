<?php
require_once __DIR__ . '/../newlife-common.php';
nlRequirePost();
$user = requireAuth();
$input = nlInput();

// Validate every submitted key against the definitions
$clean = [];
foreach (nlSettingDefinitions() as $group) {
    foreach ($group['fields'] as $key => $f) {
        if (!array_key_exists($key, $input)) continue;
        $val = nlClean(is_bool($input[$key]) ? (int)$input[$key] : $input[$key]);
        switch ($f['type']) {
            case 'switch':
                $val = ($val === '1' || $val === 'true' || $val === 'on') ? '1' : '0';
                break;
            case 'number':
                if (!is_numeric($val)) jsonResponse(false, $f['label'] . ' must be a number');
                if (isset($f['min']) && $val < $f['min']) jsonResponse(false, $f['label'] . ' must be at least ' . $f['min']);
                if (isset($f['max']) && $val > $f['max']) jsonResponse(false, $f['label'] . ' must be at most ' . $f['max']);
                break;
            case 'select':
                if (!array_key_exists($val, $f['options'])) jsonResponse(false, 'Invalid value for ' . $f['label']);
                break;
            default:
                if (isset($f['max']) && mb_strlen($val) > $f['max']) jsonResponse(false, $f['label'] . ' is too long (max ' . $f['max'] . ')');
        }
        $clean[$key] = $val;
    }
}
if (!$clean) jsonResponse(false, 'Nothing to save');

$conn = getNewLifeConnection();
$stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
foreach ($clean as $key => $val) {
    $stmt->bind_param('ss', $key, $val);
    $stmt->execute();
}
$stmt->close();

jsonResponse(true, 'General settings saved');
