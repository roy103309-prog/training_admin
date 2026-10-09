<?php
require_once __DIR__ . '/../newlife-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

// multipart/form-data: user_id + documents[]
$userId = (int)($_POST['user_id'] ?? 0);
$stmt = $conn->prepare("SELECT id FROM " . nlCoreTable('users') . " WHERE id = ? AND isemployee = 1");
$stmt->bind_param('i', $userId); $stmt->execute();
$found = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
if (!$found) jsonResponse(false, 'Employee not found');

$files = nlUploadedFiles('documents');
if (!$files) jsonResponse(false, 'Please choose at least one file');

$saved = [];
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO employee_documents (user_id, file_path, original_name) VALUES (?, ?, ?)");
    foreach ($files as $f) {
        $up = nlUploadFile($f, 'employee-docs', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx']);
        if (!$up['success']) throw new NlUserError($f['name'] . ': ' . $up['message']);
        $saved[] = $up['path'];
        $name = mb_substr((string)$f['name'], 0, 255);
        $stmt->bind_param('iss', $userId, $up['path'], $name);
        $stmt->execute();
    }
    $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    foreach ($saved as $p) nlDeleteFile($p);
    throw $e;
}

jsonResponse(true, count($saved) . ' document(s) uploaded');
