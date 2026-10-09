<?php
require_once __DIR__ . '/student-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
$id = (int)($in['id'] ?? 0);

$category  = nlClean($in['category'] ?? 'Welding', 40) ?: 'Welding';
$name      = nlClean($in['name'] ?? '', 255);
$fee       = max(0, (float)($in['fee'] ?? 0));
$duration  = nlClean($in['duration'] ?? '', 60);
$isActive  = nlFlag($in['is_active'] ?? 1);
$sortOrder = (int)($in['sort_order'] ?? 0);
if ($name === '') jsonResponse(false, 'Course name is required');

$conn = getNewLifeConnection();
if ($id > 0) {
    $stmt = $conn->prepare("UPDATE courses SET category = ?, name = ?, fee = ?, duration = ?, is_active = ?, sort_order = ? WHERE id = ?");
    $stmt->bind_param('ssdsiii', $category, $name, $fee, $duration, $isActive, $sortOrder, $id);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Course updated');
}
$locationId = nlLocationId();
$stmt = $conn->prepare("INSERT INTO courses (location_id, category, name, fee, duration, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('issdsii', $locationId, $category, $name, $fee, $duration, $isActive, $sortOrder);
$stmt->execute();
$newId = (int)$conn->insert_id;
$stmt->close();
jsonResponse(true, 'Course created', ['id' => $newId]);
