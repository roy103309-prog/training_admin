<?php
require_once __DIR__ . '/certificate-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("SELECT CrtImg FROM old_certificates WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$r = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$r) jsonResponse(false, 'Record not found');

$stmt = $conn->prepare("DELETE FROM old_certificates WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
if ($r['CrtImg']) nlDeleteFile($r['CrtImg']);
jsonResponse(true, 'Certificate deleted');
