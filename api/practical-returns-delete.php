<?php
require_once __DIR__ . '/practical-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("DELETE FROM job_returns WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$deleted = $stmt->affected_rows; $stmt->close();
if (!$deleted) jsonResponse(false, 'Entry not found');
jsonResponse(true, 'Return / retrain entry deleted');
