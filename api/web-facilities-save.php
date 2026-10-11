<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$id = (int)($_POST['id'] ?? 0);
$old = $id ? webFind($conn, 'web_facilities', $id) : null;
if ($id && !$old) jsonResponse(false, 'Facility not found');
$titleBn = nlClean($_POST['title_bn'] ?? '', 255);
$titleEn = nlClean($_POST['title_en'] ?? '', 255);
if ($titleBn === '' && $titleEn === '') jsonResponse(false, 'Title (Bangla or English) is required');
if (!$old && (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE)) jsonResponse(false, 'Please choose an image');

$descBn = nlClean($_POST['description_bn'] ?? '', 2000);
$descEn = nlClean($_POST['description_en'] ?? '', 2000);
$specsBn = nlClean($_POST['specs_bn'] ?? '', 2000);
$specsEn = nlClean($_POST['specs_en'] ?? '', 2000);
$icon = (string)($_POST['icon'] ?? '');
if (!in_array($icon, WEB_FACILITY_ICONS, true)) $icon = 'star';
$active = nlFlag($_POST['is_active'] ?? 1);
$sort = (int)($_POST['sort_order'] ?? 0);
// New images go to the institute's existing "site" upload folder
$new = nlUploadImage('image', 'site');
$image = $new ?? $old['image'];

if ($old) {
    $stmt = $conn->prepare("UPDATE web_facilities SET title_bn = ?, title_en = ?, description_bn = ?, description_en = ?, specs_bn = ?, specs_en = ?, icon = ?, image = ?, is_active = ?, sort_order = ? WHERE id = ?");
    $stmt->bind_param('ssssssssiii', $titleBn, $titleEn, $descBn, $descEn, $specsBn, $specsEn, $icon, $image, $active, $sort, $id);
    $stmt->execute(); $stmt->close();
    if ($new && $old['image']) nlDeleteFile($old['image']);
    jsonResponse(true, 'Facility updated');
}
$stmt = $conn->prepare("INSERT INTO web_facilities (title_bn, title_en, description_bn, description_en, specs_bn, specs_en, icon, image, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssssssii', $titleBn, $titleEn, $descBn, $descEn, $specsBn, $specsEn, $icon, $image, $active, $sort);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Facility added');
