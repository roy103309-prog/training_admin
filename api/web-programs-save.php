<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$id = (int)($_POST['id'] ?? 0);
$old = $id ? webFind($conn, 'programs', $id) : null;
if ($id && !$old) jsonResponse(false, 'Program not found');
$title = nlClean($_POST['title'] ?? '', 255);
if ($title === '') jsonResponse(false, 'Program title is required');
$slugIn = nlClean($_POST['slug'] ?? '') ?: $title;
$slug = substr(strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $slugIn), '-')), 0, 120) ?: 'program-' . time();
$stmt = $conn->prepare("SELECT 1 FROM programs WHERE slug = ? AND id <> ?");
$stmt->bind_param('si', $slug, $id); $stmt->execute();
$taken = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
if ($taken) jsonResponse(false, 'Another program already uses this URL slug');

$f = [
    'title_bn' => nlClean($_POST['title_bn'] ?? '', 255),
    'subtitle' => nlClean($_POST['subtitle'] ?? '', 255),
    'level' => nlClean($_POST['level'] ?? '', 60),
    'duration' => nlClean($_POST['duration'] ?? '', 60),
    'price' => nlClean($_POST['price'] ?? '', 60),
    'description' => nlClean($_POST['description'] ?? '', 10000),
];
$home = nlFlag($_POST['show_on_home'] ?? 1);
$active = nlFlag($_POST['is_active'] ?? 1);
$sort = (int)($_POST['sort_order'] ?? 0);
$new = nlUploadImage('image', 'programs');
$image = $new ?? ($old['image'] ?? '');

if ($old) {
    $stmt = $conn->prepare("UPDATE programs SET slug = ?, title = ?, title_bn = ?, subtitle = ?, level = ?, duration = ?, price = ?, description = ?, image = ?,
                            show_on_home = ?, is_active = ?, sort_order = ? WHERE id = ?");
    $stmt->bind_param('sssssssssiiii', $slug, $title, $f['title_bn'], $f['subtitle'], $f['level'], $f['duration'], $f['price'], $f['description'], $image, $home, $active, $sort, $id);
    $stmt->execute(); $stmt->close();
    if ($new && $old['image']) nlDeleteFile($old['image']);
    jsonResponse(true, 'Program updated');
}
$stmt = $conn->prepare("INSERT INTO programs (slug, title, title_bn, subtitle, level, duration, price, description, image, show_on_home, is_active, sort_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssssssiii', $slug, $title, $f['title_bn'], $f['subtitle'], $f['level'], $f['duration'], $f['price'], $f['description'], $image, $home, $active, $sort);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Program added');
