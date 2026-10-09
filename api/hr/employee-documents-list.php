<?php
require_once __DIR__ . '/../newlife-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$userId = (int)($_GET['user_id'] ?? 0);
$stmt = $conn->prepare("SELECT id, file_path, original_name, created_at FROM employee_documents WHERE user_id = ? ORDER BY id");
$stmt->bind_param('i', $userId); $stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

jsonResponse(true, 'OK', ['items' => $items]);
