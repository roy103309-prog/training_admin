<?php
require_once __DIR__ . '/certificate-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(200, max(10, (int)($_GET['limit'] ?? 25)));
$search = nlClean($_GET['search'] ?? '', 100);
$offset = ($page - 1) * $limit;

$w = ''; $params = []; $types = '';
if ($search !== '') { $w = " WHERE CrtName LIKE ? OR CrtRoll LIKE ? OR CrtRegi LIKE ?"; $like = "%$search%"; $params = [$like, $like, $like]; $types = 'sss'; }

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM old_certificates" . $w);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();

$params[] = $limit; $params[] = $offset; $types .= 'ii';
$stmt = $conn->prepare("SELECT * FROM old_certificates" . $w . " ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

jsonResponse(true, 'OK', ['items' => $items, 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)]]);
