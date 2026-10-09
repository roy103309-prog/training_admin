<?php
require_once __DIR__ . '/practical-common.php';
// Out of Student register: optional date, category and search; totals for the page cards
$user = requireAuth();
$conn = getNewLifeConnection();

$search = nlClean($_GET['search'] ?? '', 100);
$categoryId = (int)($_GET['category_id'] ?? 0);
$date = nlDate($_GET['date'] ?? '');

$where = []; $params = []; $types = '';
if ($date) { $where[] = 'o.job_date = ?'; $params[] = $date; $types .= 's'; }
if ($categoryId > 0) { $where[] = 'o.category_id = ?'; $params[] = $categoryId; $types .= 'i'; }
if ($search !== '') {
    $where[] = '(o.candidate_name LIKE ? OR o.token_no LIKE ? OR o.mobile LIKE ? OR o.agent_reference LIKE ? OR o.passport_number LIKE ? OR o.payment_ref LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like, $like); $types .= 'ssssss';
}
$stmt = $conn->prepare(practicalOutSql() . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.job_date DESC, o.daily_serial DESC LIMIT 1000');
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(paid), 0) AS collected, COALESCE(SUM(job_date = ?), 0) AS today FROM out_jobs");
$stmt->bind_param('s', $today); $stmt->execute();
$all = $stmt->get_result()->fetch_assoc(); $stmt->close();

jsonResponse(true, 'OK', [
    'items' => $items,
    'summary' => [
        'total' => (int)$all['total'], 'today' => (int)$all['today'], 'collected' => (float)$all['collected'],
        'shown_count' => count($items),
        'shown_price' => array_sum(array_column($items, 'price')),
        'shown_paid' => array_sum(array_column($items, 'paid')),
    ],
]);
