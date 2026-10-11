<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$id = (int)($_POST['id'] ?? 0);
$old = $id ? webFind($conn, 'web_team', $id) : null;
if ($id && !$old) jsonResponse(false, 'Team member not found');
$name = nlClean($_POST['name'] ?? '', 150);
if ($name === '') jsonResponse(false, 'Name is required');

$designation = nlClean($_POST['designation'] ?? '', 150);
$category = (string)($_POST['category'] ?? '');
if (!isset(WEB_TEAM_CATEGORIES[$category])) jsonResponse(false, 'Please choose a valid section');
$phone = nlClean($_POST['phone'] ?? '', 40);
$email = nlClean($_POST['email'] ?? '', 150);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Invalid email address');
$active = nlFlag($_POST['is_active'] ?? 1);
$sort = (int)($_POST['sort_order'] ?? 0);
$new = nlUploadImage('image', 'team');
$image = $new ?? ($old['image'] ?? '');
if (!$new && nlFlag($_POST['remove_image'] ?? 0)) $image = '';

if ($old) {
    $stmt = $conn->prepare("UPDATE web_team SET name = ?, designation = ?, category = ?, phone = ?, email = ?, image = ?, is_active = ?, sort_order = ? WHERE id = ?");
    $stmt->bind_param('ssssssiii', $name, $designation, $category, $phone, $email, $image, $active, $sort, $id);
    $stmt->execute(); $stmt->close();
    if ($old['image'] && $old['image'] !== $image) nlDeleteFile($old['image']);
    jsonResponse(true, 'Team member updated');
}
$stmt = $conn->prepare("INSERT INTO web_team (name, designation, category, phone, email, image, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssssii', $name, $designation, $category, $phone, $email, $image, $active, $sort);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Team member added');
