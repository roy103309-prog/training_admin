<?php
require_once '../tenant-config.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(10, (int)($_GET['limit'] ?? 10)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $limit;
$conn = getTenantConnection();

$countQuery = "SELECT COUNT(*) as total FROM payment_methods WHERE location_id = ?";
$params = [$locationId]; $types = 'i';
if ($search) { $countQuery .= " AND name LIKE ?"; $params[] = "%$search%"; $types .= 's'; }

$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc()['total'];
$result->free();
$stmt->close();

$query = "SELECT * FROM payment_methods WHERE location_id = ?";
if ($search) $query .= " AND name LIKE ?";
$query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit; $params[] = $offset; $types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) $items[] = $row;
$result->free();
$stmt->close();

jsonResponse(true, 'Payment methods retrieved', ['items' => $items, 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => (int)$total, 'pages' => ceil($total / $limit)]]);
