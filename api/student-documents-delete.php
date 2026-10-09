<?php
require_once __DIR__ . '/student-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("SELECT file_path FROM student_documents WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$doc = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$doc) jsonResponse(false, 'Document not found');

$stmt = $conn->prepare("DELETE FROM student_documents WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
nlDeleteFile($doc['file_path']);
jsonResponse(true, 'Document deleted');
