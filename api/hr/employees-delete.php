<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($in['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'ID required');
$conn = getTenantConnection();
// Soft-delete: mark inactive + remove employee flag
$stmt = $conn->prepare("UPDATE users SET isactive = 0, isemployee = 0 WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
jsonResponse(true, 'Employee removed');
