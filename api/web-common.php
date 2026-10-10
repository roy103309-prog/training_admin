<?php
require_once __DIR__ . '/newlife-common.php';

/**
 * Website content tables (sliders, videos, reviews, programs, web_settings,
 * contact_messages) — a public website reads them,
 * so keep their columns.
 */

// Website settings editable on Settings -> General Settings -> Logo & Branding (templates/web-branding.html)
const WEB_SETTING_KEYS = [
    'site_name', 'site_tagline', 'site_tagline_bn', 'site_title', 'site_description', 'site_keywords',
    'phone_primary', 'phone_secondary', 'call_number', 'email', 'address_bn', 'address_en',
    'whatsapp_number', 'whatsapp_message', 'facebook_url', 'youtube_url', 'website_url', 'map_url',
    'developer_name', 'developer_url', 'facility_title', 'facility_subtitle', 'chairman_name_bn', 'chairman_name_en',
];
const WEB_SETTING_IMAGES = ['logo', 'favicon', 'chairman_image', 'about_image'];

function webAll(mysqli $conn, $table) {
    $res = $conn->query("SELECT * FROM `$table` ORDER BY sort_order, id");
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    return $rows;
}

function webFind(mysqli $conn, $table, $id) {
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row;
}

// Delete a row and the file it points to
function webDelete(mysqli $conn, $table, $id, $fileColumn = null) {
    $row = webFind($conn, $table, $id);
    if (!$row) jsonResponse(false, 'Record not found');
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    if ($fileColumn && !empty($row[$fileColumn])) nlDeleteFile($row[$fileColumn]);
}

function youtubeId($url) {
    return preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', (string)$url, $m) ? $m[1] : '';
}
