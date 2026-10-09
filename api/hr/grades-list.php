<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

$conn = getTenantConnection();


$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(200, max(10, (int)($_GET['limit'] ?? 50)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $limit;

$cq = "SELECT COUNT(*) AS total FROM grades WHERE location_id = ?";
$params = [$locationId]; $types = 'i';
if ($search) { $cq .= " AND (name LIKE ? OR short_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }

$stmt = $conn->prepare($cq); $stmt->bind_param($types, ...$params); $stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total']; $stmt->close();

$q = "SELECT * FROM grades WHERE location_id = ?";
if ($search) $q .= " AND (name LIKE ? OR short_name LIKE ?)";
$q .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit; $params[] = $offset; $types .= 'ii';

$stmt = $conn->prepare($q); $stmt->bind_param($types, ...$params); $stmt->execute();
$res = $stmt->get_result();
$items = []; while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();

jsonResponse(true, 'OK', ['items' => $items, 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)]]);
