<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(200, max(10, (int)($_GET['limit'] ?? 25)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $limit;

$cq = "SELECT COUNT(*) AS c FROM users WHERE isemployee = 1 AND (location_id = ? OR location_id IS NULL)";
$params = [$locationId]; $types = 'i';
if ($search) { $cq .= " AND (name LIKE ? OR mobile LIKE ? OR email LIKE ? OR employee_code LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]); $types .= 'ssss'; }
$stmt = $conn->prepare($cq); $stmt->bind_param($types, ...$params); $stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();

$q = "SELECT u.id, u.employee_code, u.name, u.mobile, u.email, u.gender, u.joining_date, u.username,
             u.shift, u.grade_id, u.salary_type, u.basic_salary, u.salary_status,
             u.role_id, u.location_id, u.isactive,
             r.name AS role_name, g.name AS grade_name
      FROM users u
      LEFT JOIN roles  r ON r.id = u.role_id
      LEFT JOIN grades g ON g.id = u.grade_id
      WHERE u.isemployee = 1 AND (u.location_id = ? OR u.location_id IS NULL)";
if ($search) $q .= " AND (u.name LIKE ? OR u.mobile LIKE ? OR u.email LIKE ? OR u.employee_code LIKE ?)";
$q .= " ORDER BY u.id DESC LIMIT ? OFFSET ?";
$params[] = $limit; $params[] = $offset; $types .= 'ii';

$stmt = $conn->prepare($q); $stmt->bind_param($types, ...$params); $stmt->execute();
$res = $stmt->get_result();
$items = []; while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();

jsonResponse(true, 'OK', ['items' => $items, 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)]]);
