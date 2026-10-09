<?php
require_once '../tenant-config.php';
require_once __DIR__ . '/_ensure-columns.php';

$user = requireAuth();
$locationId = getCurrentLocation();

if (!$locationId) {
    jsonResponse(false, 'No location selected');
}

$conn = getTenantConnection();
ensureSettingsBasicColumns($conn);

// Get settings for this location
$stmt = $conn->prepare("SELECT website_name, tagline, logo, website_logo, favicon, phone, email, address, facebook_url, twitter_url, instagram_url, youtube_url, website_url FROM settings_basic WHERE location_id = ?");
$stmt->bind_param('i', $locationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    // Return defaults if no settings exist
    $settings = [
        'website_name' => '',
        'tagline' => '',
        'logo' => '',
        'website_logo' => '',
        'favicon' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'facebook_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'youtube_url' => '',
        'website_url' => ''
    ];
}
$result->free();
$stmt->close();

jsonResponse(true, 'Settings retrieved', $settings);
