<?php
/**
 * Idempotently ensure new columns exist on settings_basic.
 * Safe to call on every settings request — uses INFORMATION_SCHEMA check.
 */
function ensureSettingsBasicColumns(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $dbRes = $conn->query("SELECT DATABASE() AS db");
    if (!$dbRes) return;
    $db = $dbRes->fetch_assoc()['db'] ?? '';
    $dbRes->free();
    if (!$db) return;

    $cols = [
        'tagline'      => "ALTER TABLE settings_basic ADD COLUMN tagline VARCHAR(255) NULL AFTER website_name",
        'website_logo' => "ALTER TABLE settings_basic ADD COLUMN website_logo VARCHAR(255) NULL AFTER logo",
        'favicon'      => "ALTER TABLE settings_basic ADD COLUMN favicon VARCHAR(255) NULL AFTER website_logo",
    ];

    foreach ($cols as $col => $alter) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='settings_basic' AND COLUMN_NAME=?");
        if (!$stmt) continue;
        $stmt->bind_param('ss', $db, $col);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $res->free();
        $stmt->close();
        if ((int)($row['c'] ?? 0) === 0) {
            @$conn->query($alter);
        }
    }
}
