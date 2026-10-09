<?php
require_once '../tenant-config.php';
require_once __DIR__ . '/_ensure-columns.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$user = requireAuth();
$locationId = getCurrentLocation();

if (!$locationId) {
    jsonResponse(false, 'No location selected');
}

$conn = getTenantConnection();
ensureSettingsBasicColumns($conn);

$websiteName  = sanitize($_POST['website_name']  ?? '');
$tagline      = sanitize($_POST['tagline']       ?? '');
$phone        = sanitize($_POST['phone']         ?? '');
$email        = sanitize($_POST['email']         ?? '');
$address      = sanitize($_POST['address']       ?? '');
$facebookUrl  = sanitize($_POST['facebook_url']  ?? '');
$twitterUrl   = sanitize($_POST['twitter_url']   ?? '');
$instagramUrl = sanitize($_POST['instagram_url'] ?? '');
$youtubeUrl   = sanitize($_POST['youtube_url']   ?? '');
$websiteUrl   = sanitize($_POST['website_url']   ?? '');

// Load current values for fallback (so uploads don't blank existing files)
$stmt = $conn->prepare("SELECT logo, website_logo, favicon FROM settings_basic WHERE location_id = ?");
$stmt->bind_param('i', $locationId);
$stmt->execute();
$result = $stmt->get_result();
$exists = $result->num_rows > 0;
$current = $exists ? $result->fetch_assoc() : ['logo' => '', 'website_logo' => '', 'favicon' => ''];
$result->free();
$stmt->close();

// Helper to process a single uploaded file field; returns new path or existing
function handleAssetUpload(string $field, string $subfolder, string $current): string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $current;
    }
    $upload = uploadFile($_FILES[$field], $subfolder);
    if (!$upload['success']) {
        return $current;
    }
    if ($current && file_exists($current)) {
        deleteFile($current);
    }
    return $upload['path'];
}

$logoPath        = handleAssetUpload('logo',         'logo',         $current['logo'] ?? '');
$websiteLogoPath = handleAssetUpload('website_logo', 'website_logo', $current['website_logo'] ?? '');
$faviconPath     = handleAssetUpload('favicon',      'favicon',      $current['favicon'] ?? '');

if ($exists) {
    $stmt = $conn->prepare("UPDATE settings_basic SET website_name=?, tagline=?, logo=?, website_logo=?, favicon=?, phone=?, email=?, address=?, facebook_url=?, twitter_url=?, instagram_url=?, youtube_url=?, website_url=? WHERE location_id = ?");
    $stmt->bind_param('sssssssssssssi', $websiteName, $tagline, $logoPath, $websiteLogoPath, $faviconPath, $phone, $email, $address, $facebookUrl, $twitterUrl, $instagramUrl, $youtubeUrl, $websiteUrl, $locationId);
} else {
    $stmt = $conn->prepare("INSERT INTO settings_basic (location_id, website_name, tagline, logo, website_logo, favicon, phone, email, address, facebook_url, twitter_url, instagram_url, youtube_url, website_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssssssssssss', $locationId, $websiteName, $tagline, $logoPath, $websiteLogoPath, $faviconPath, $phone, $email, $address, $facebookUrl, $twitterUrl, $instagramUrl, $youtubeUrl, $websiteUrl);
}

if ($stmt->execute()) {
    $stmt->close();
    jsonResponse(true, 'Settings updated successfully');
} else {
    $error = $stmt->error;
    $stmt->close();
    jsonResponse(false, 'Failed to update settings: ' . $error);
}
