<?php
require_once __DIR__ . '/certificate-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("SELECT file_path FROM certificates WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$c = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$c) jsonResponse(false, 'Certificate not found');

$stmt = $conn->prepare("DELETE FROM certificates WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
if ($c['file_path']) nlDeleteFile($c['file_path']);
jsonResponse(true, 'Certificate deleted');
