<?php
require_once __DIR__ . '/certificate-common.php';
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

// multipart/form-data: student_id, certificate_file, description, issue_date
$student = certStudent($conn, (int)($_POST['student_id'] ?? 0));
if (empty($_FILES['certificate_file']) || $_FILES['certificate_file']['error'] === UPLOAD_ERR_NO_FILE) {
    jsonResponse(false, 'Please select a file to upload');
}
// A website may show this file to visitors, so images and PDF only
$up = nlUploadFile($_FILES['certificate_file'], 'certificates', ['jpg', 'jpeg', 'png', 'webp', 'pdf']);
if (!$up['success']) jsonResponse(false, $up['message']);

$title = mb_substr((string)$_FILES['certificate_file']['name'], 0, 255);
$desc = nlClean($_POST['description'] ?? '', 255);
$issue = nlDate($_POST['issue_date'] ?? '') ?? date('Y-m-d');

$stmt = $conn->prepare("INSERT INTO certificates (location_id, student_id, title, issue_date, file_path, description, source) VALUES (?, ?, ?, ?, ?, ?, 'uploaded')");
$stmt->bind_param('iissss', $locationId, $student['id'], $title, $issue, $up['path'], $desc);
$stmt->execute();
$id = (int)$conn->insert_id;
$stmt->close();

jsonResponse(true, 'Certificate uploaded', ['id' => $id, 'file_path' => $up['path']]);
