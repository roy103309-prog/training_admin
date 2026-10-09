<?php
require_once __DIR__ . '/student-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(200, max(10, (int)($_GET['limit'] ?? 25)));
$search = nlClean($_GET['search'] ?? '', 100);
$status = nlClean($_GET['status'] ?? '');
$course = nlClean($_GET['course'] ?? '');
$sort   = nlClean($_GET['sort'] ?? '');
$overseas = nlClean($_GET['overseas'] ?? '');
$offset = ($page - 1) * $limit;

$where = []; $params = []; $types = '';
if ($status !== '' && $status !== 'all') { $where[] = 's.status = ?'; $params[] = $status; $types .= 's'; }
if ($course !== '' && $course !== 'all') { $where[] = 's.course_name = ?'; $params[] = $course; $types .= 's'; }
if ($overseas !== '' && $overseas !== 'all') { $where[] = "COALESCE(o.overseas_status, 'not_applied') = ?"; $params[] = $overseas; $types .= 's'; }
if ($search !== '') {
    $where[] = '(s.name LIKE ? OR s.student_id LIKE ? OR s.phone_number LIKE ? OR s.father_name LIKE ? OR s.passport_number LIKE ? OR s.nid LIKE ? OR o.destination_country LIKE ? OR oc.name LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like, $like, $like, $like); $types .= 'ssssssss';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$orders = [
    'oldest' => 's.admission_date ASC, s.id ASC',
    'name_asc' => 's.name ASC', 'name_desc' => 's.name DESC',
    'due_desc' => 'due_amount DESC', 'due_asc' => 'due_amount ASC',
];
$order = $orders[$sort] ?? 's.admission_date DESC, s.id DESC';

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM students s" . studentOverseasJoin() . $whereSql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$params[] = $limit; $params[] = $offset; $types .= 'ii';
$stmt = $conn->prepare(studentBaseSql() . $whereSql . " ORDER BY $order LIMIT ? OFFSET ?");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Certificate counts for this page
if ($items) {
    $ids = array_map('intval', array_column($items, 'id'));
    $cres = $conn->query("SELECT student_id, SUM(source = 'generated') AS g, SUM(source = 'uploaded') AS u FROM certificates
                          WHERE student_id IN (" . implode(',', $ids) . ") GROUP BY student_id");
    $cert = [];
    while ($c = $cres->fetch_assoc()) $cert[$c['student_id']] = $c;
    foreach ($items as &$it) {
        $it['cert_generated'] = (int)($cert[$it['id']]['g'] ?? 0);
        $it['cert_uploaded'] = (int)($cert[$it['id']]['u'] ?? 0);
    }
    unset($it);
}

$sum = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active FROM students")->fetch_assoc();

jsonResponse(true, 'OK', [
    'items' => $items,
    'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)],
    'summary' => ['total' => (int)$sum['total'], 'active' => (int)$sum['active']]
]);
