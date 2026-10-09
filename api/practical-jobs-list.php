<?php
require_once __DIR__ . '/practical-common.php';
// Daily Job Register: jobs of one date, of a month (?month=YYYY-MM) or of a student (?student_id=), optional category
$user = requireAuth();
$conn = getNewLifeConnection();

$month = nlClean($_GET['month'] ?? '', 7);
$categoryId = (int)($_GET['category_id'] ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);

$where = []; $params = []; $types = '';
if ($studentId > 0) {
    $where[] = 'j.student_id = ?'; $params[] = $studentId; $types .= 'i';
} elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $where[] = 'j.job_date BETWEEN ? AND ?';
    array_push($params, $month . '-01', date('Y-m-t', strtotime($month . '-01'))); $types .= 'ss';
} else {
    $date = nlDate($_GET['date'] ?? '') ?? date('Y-m-d');
    $where[] = 'j.job_date = ?'; $params[] = $date; $types .= 's';
}
if ($categoryId > 0) { $where[] = 'j.category_id = ?'; $params[] = $categoryId; $types .= 'i'; }

$stmt = $conn->prepare(practicalJobSql() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY j.job_date, j.daily_serial');
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$byCategory = [];
foreach ($items as $it) {
    $k = $it['category_name'];
    if (!isset($byCategory[$k])) $byCategory[$k] = ['name' => $k, 'count' => 0, 'payable' => 0];
    $byCategory[$k]['count']++;
    $byCategory[$k]['payable'] += (float)$it['payable'];
}

jsonResponse(true, 'OK', [
    'items' => $items,
    'summary' => [
        'count' => count($items),
        'price' => array_sum(array_column($items, 'price')),
        'discount' => array_sum(array_column($items, 'discount')),
        'payable' => array_sum(array_column($items, 'payable')),
        'by_category' => array_values($byCategory),
    ],
]);
