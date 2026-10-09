<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(100, max(10, (int)($_GET['limit'] ?? 25)));
$offset = ($page - 1) * $limit;

$cs = $conn->prepare("SELECT COUNT(*) AS c FROM salaries WHERE location_id = ?");
$cs->bind_param('i', $locationId); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();

$stmt = $conn->prepare("
    SELECT s.*, u.name AS employee_name, u.employee_code
    FROM salaries s
    JOIN users u ON u.id = s.user_id
    WHERE s.location_id = ?
    ORDER BY s.id DESC LIMIT ? OFFSET ?
");
$stmt->bind_param('iii', $locationId, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$items = []; while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();

jsonResponse(true, 'OK', ['items' => $items, 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)]]);
