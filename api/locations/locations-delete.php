<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
if (empty($user['issuperadmin'])) jsonResponse(false, 'Super Admin privilege required');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$id    = (int)($input['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid branch id');

$conn = getTenantConnection();

// Don't allow deleting the last branch
$cntRes = $conn->query("SELECT COUNT(*) AS c FROM locations");
$cnt = (int)$cntRes->fetch_assoc()['c'];
$cntRes->free();
if ($cnt <= 1) jsonResponse(false, 'Cannot delete the last branch');

$stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
$stmt->bind_param('i', $id);
if (!$stmt->execute()) jsonResponse(false, 'Delete failed (branch may have related data)');
$stmt->close();
jsonResponse(true, 'Branch deleted');
