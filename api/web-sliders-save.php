<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$id = (int)($_POST['id'] ?? 0);
$old = $id ? webFind($conn, 'sliders', $id) : null;
if ($id && !$old) jsonResponse(false, 'Slide not found');
if (!$old && (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE)) jsonResponse(false, 'Please choose an image');

$title = nlClean($_POST['title'] ?? '', 255);
$subtitle = nlClean($_POST['subtitle'] ?? '', 255);
$active = nlFlag($_POST['is_active'] ?? 1);
$sort = (int)($_POST['sort_order'] ?? 0);
$new = nlUploadImage('image', 'sliders');
$image = $new ?? $old['image_url'];

if ($old) {
    $stmt = $conn->prepare("UPDATE sliders SET title = ?, subtitle = ?, image_url = ?, is_active = ?, sort_order = ? WHERE id = ?");
    $stmt->bind_param('sssiii', $title, $subtitle, $image, $active, $sort, $id);
    $stmt->execute(); $stmt->close();
    if ($new && $old['image_url']) nlDeleteFile($old['image_url']);
    jsonResponse(true, 'Slide updated');
}
$stmt = $conn->prepare("INSERT INTO sliders (title, subtitle, image_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssii', $title, $subtitle, $image, $active, $sort);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Slide added');
