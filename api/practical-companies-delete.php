<?php
require_once __DIR__ . '/practical-common.php';
// A company linked to students cannot be deleted (make it inactive instead)
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM student_overseas WHERE company_id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$linked = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
if ($linked > 0) jsonResponse(false, "$linked student(s) are linked to this company. Make it inactive instead.");

$stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$deleted = $stmt->affected_rows; $stmt->close();
if (!$deleted) jsonResponse(false, 'Company not found');
jsonResponse(true, 'Company deleted');
