<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();
$in = nlInput();
$id = (int)($in['id'] ?? 0);

$url = nlClean($in['video_url'] ?? '', 500);
if ($url === '' || !preg_match('#^https?://#i', $url)) jsonResponse(false, 'Please enter a valid video URL (YouTube link or .mp4 URL)');
$title = nlClean($in['title'] ?? '', 500);
$desc = nlClean($in['description'] ?? '', 5000);
$platform = youtubeId($url) ? 'youtube' : 'video';
$active = nlFlag($in['is_active'] ?? 1);
$sort = (int)($in['sort_order'] ?? 0);

if ($id > 0) {
    if (!webFind($conn, 'videos', $id)) jsonResponse(false, 'Video not found');
    $stmt = $conn->prepare("UPDATE videos SET title = ?, description = ?, video_url = ?, platform = ?, is_active = ?, sort_order = ? WHERE id = ?");
    $stmt->bind_param('ssssiii', $title, $desc, $url, $platform, $active, $sort, $id);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Video updated');
}
$stmt = $conn->prepare("INSERT INTO videos (title, description, video_url, platform, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssii', $title, $desc, $url, $platform, $active, $sort);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Video added');
