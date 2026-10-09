<?php
require_once __DIR__ . '/student-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = min(200, max(10, (int)($_GET['limit'] ?? 25)));
$search = nlClean($_GET['search'] ?? '', 100);
$from   = nlDate($_GET['date_from'] ?? '');
$to     = nlDate($_GET['date_to'] ?? '');
$offset = ($page - 1) * $limit;

$where = []; $params = []; $types = '';
if ($from) { $where[] = 'p.payment_date >= ?'; $params[] = $from . ' 00:00:00'; $types .= 's'; }
if ($to)   { $where[] = 'p.payment_date <= ?'; $params[] = $to . ' 23:59:59'; $types .= 's'; }
if ($search !== '') { $where[] = '(s.name LIKE ? OR s.student_id LIKE ? OR s.phone_number LIKE ?)'; $like = "%$search%"; array_push($params, $like, $like, $like); $types .= 'sss'; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$base = " FROM student_payments p JOIN students s ON s.id = p.student_id
          LEFT JOIN " . nlCoreTable('payment_methods') . " pm ON pm.id = p.payment_method_id
          LEFT JOIN " . nlCoreTable('acc_vouchers') . " v ON v.id = p.voucher_id" . $w;

$stmt = $conn->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(p.amount), 0) AS total" . $base);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$sum = $stmt->get_result()->fetch_assoc(); $stmt->close();

$params[] = $limit; $params[] = $offset; $types .= 'ii';
$stmt = $conn->prepare("SELECT p.id, p.amount, p.payment_date, p.notes, s.id AS sid, s.student_id, s.name, s.course_name,
                               pm.name AS payment_method, v.voucher_no" . $base . " ORDER BY p.payment_date DESC, p.id DESC LIMIT ? OFFSET ?");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$total = (int)$sum['c'];
jsonResponse(true, 'OK', ['items' => $items, 'total_amount' => (float)$sum['total'],
    'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int)ceil($total / $limit)]]);
