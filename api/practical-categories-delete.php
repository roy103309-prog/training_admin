<?php
require_once __DIR__ . '/practical-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
$id = (int)($in['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid ID');

$conn = getNewLifeConnection();
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM jobs WHERE category_id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$used = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
if ($used > 0) jsonResponse(false, "{$used} job(s) were issued in this category. Make it inactive instead.");

$stmt = $conn->prepare("DELETE FROM job_categories WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
jsonResponse(true, 'Job category deleted');
