<?php
require_once __DIR__ . '/student-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$course = nlClean($_GET['course'] ?? '');
$status = nlClean($_GET['status'] ?? 'active');
$onlyDue = !isset($_GET['only_due']) || $_GET['only_due'] === '1';

$where = []; $params = []; $types = '';
if ($status !== '' && $status !== 'all') { $where[] = 's.status = ?'; $params[] = $status; $types .= 's'; }
if ($course !== '' && $course !== 'all') { $where[] = 's.course_name = ?'; $params[] = $course; $types .= 's'; }
$sql = "SELECT * FROM (" . studentBaseSql() . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ") x"
     . ($onlyDue ? " WHERE x.due_amount > 0" : "") . " ORDER BY x.due_amount DESC, x.name";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totals = ['fee' => 0, 'paid' => 0, 'due' => 0];
foreach ($items as $r) { $totals['fee'] += $r['course_fee']; $totals['paid'] += $r['paid_amount']; $totals['due'] += $r['due_amount']; }

jsonResponse(true, 'OK', ['items' => $items, 'totals' => $totals]);
