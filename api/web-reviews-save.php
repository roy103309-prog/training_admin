<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$id = (int)($_POST['id'] ?? 0);
$old = $id ? webFind($conn, 'reviews', $id) : null;
if ($id && !$old) jsonResponse(false, 'Review not found');
$name = nlClean($_POST['student_name'] ?? '', 150);
$msg = nlClean($_POST['message'] ?? '', 5000);
if ($name === '' || $msg === '') jsonResponse(false, 'Student name and message are required');

$trade = nlClean($_POST['trade'] ?? '', 150);
$country = nlClean($_POST['country'] ?? '', 100);
$rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$active = nlFlag($_POST['is_active'] ?? 1);
$sort = (int)($_POST['sort_order'] ?? 0);
$new = nlUploadImage('photo', 'reviews');
$photo = $new ?? ($old['photo_url'] ?? '');
if (!$new && nlFlag($_POST['remove_photo'] ?? 0)) $photo = '';

if ($old) {
    $stmt = $conn->prepare("UPDATE reviews SET student_name = ?, trade = ?, country = ?, message = ?, rating = ?, photo_url = ?, is_active = ?, sort_order = ? WHERE id = ?");
    $stmt->bind_param('ssssisiii', $name, $trade, $country, $msg, $rating, $photo, $active, $sort, $id);
    $stmt->execute(); $stmt->close();
    if ($old['photo_url'] && $old['photo_url'] !== $photo) nlDeleteFile($old['photo_url']);
    jsonResponse(true, 'Review updated');
}
$stmt = $conn->prepare("INSERT INTO reviews (student_name, trade, country, message, rating, photo_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssisii', $name, $trade, $country, $msg, $rating, $photo, $active, $sort);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Review added');
