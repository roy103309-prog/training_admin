<?php
require_once '../tenant-config.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed');
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

// Whitelist of allowed setting keys
$ALLOWED = [
    // Common root
    'ui_root_primary',
    // Sidebar
    'ui_sidebar_bg', 'ui_sidebar_bg_2', 'ui_sidebar_gradient',
    'ui_sidebar_text', 'ui_sidebar_active', 'ui_sidebar_active_text',
    'ui_sidebar_submenu_bg',
    // Header
    'ui_header_bg', 'ui_header_bg_2', 'ui_header_gradient',
    'ui_header_text', 'ui_header_border',
    'ui_btn_header_bg', 'ui_btn_header_hover_bg',
    'ui_btn_header_text', 'ui_btn_header_border',
    // Footer
    'ui_footer_bg', 'ui_footer_bg_2', 'ui_footer_gradient',
    'ui_footer_text', 'ui_footer_border', 'ui_footer_accent',
    // Report
    'ui_report_header', 'ui_report_header_text', 'ui_report_title',
    'ui_report_summary_box', 'ui_report_summary_text',
    'ui_report_table_head', 'ui_report_table_head_text', 'ui_report_row_alt',
    // Forms
    'ui_form_border', 'ui_form_focus_border',
    // Tables
    'ui_table_header_bg', 'ui_table_header_text',
    'ui_table_border', 'ui_table_row_hover', 'ui_table_text',
];

$conn = getTenantConnection();


$stmt = $conn->prepare(
    "INSERT INTO ui_color_settings (setting_key, setting_value)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);
if (!$stmt) jsonResponse(false, 'Prepare failed: ' . $conn->error);

$gradientKeys = ['ui_sidebar_gradient', 'ui_header_gradient', 'ui_footer_gradient'];
$saved = 0;
foreach ($ALLOWED as $key) {
    if (!array_key_exists($key, $data)) continue;
    $value = trim((string)$data[$key]);
    if (in_array($key, $gradientKeys, true)) {
        $value = ($value === '1' || $value === 1 || $value === true) ? '1' : '0';
    }
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
    $saved++;
}
$stmt->close();

// Return the fresh state
$out = [];
$res = $conn->query("SELECT setting_key, setting_value FROM ui_color_settings");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out[$row['setting_key']] = (string)($row['setting_value'] ?? '');
    }
    $res->free();
}

jsonResponse(true, 'UI settings saved', $out);
