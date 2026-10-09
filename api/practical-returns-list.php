<?php
require_once __DIR__ . '/practical-common.php';
// Return & retrain history register, with the counts shown on the page
$user = requireAuth();
$conn = getNewLifeConnection();

$search = nlClean($_GET['search'] ?? '', 100);
$categoryId = (int)($_GET['category_id'] ?? 0);
$type = (string)($_GET['classification'] ?? '');

$where = []; $params = []; $types = '';
if ($categoryId > 0) { $where[] = 'j.category_id = ?'; $params[] = $categoryId; $types .= 'i'; }
if (isset(PRACTICAL_RETURN_TYPES[$type])) { $where[] = 'r.classification = ?'; $params[] = $type; $types .= 's'; }
if ($search !== '') {
    $where[] = '(s.name LIKE ? OR s.student_id LIKE ? OR j.job_no LIKE ? OR r.remarks LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like); $types .= 'ssss';
}
$sql = "SELECT r.*, j.job_no, j.job_date, j.category_name, s.name AS student_name, s.student_id AS student_code, u.name AS created_by_name
        FROM job_returns r
        JOIN jobs j ON j.id = r.job_id
        LEFT JOIN students s ON s.id = r.student_id
        LEFT JOIN " . nlCoreTable('users') . " u ON u.id = r.created_by"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY r.return_date DESC, r.id DESC LIMIT 1000";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$counts = ['retrain' => 0, 'plate_returned' => 0, 'failed' => 0];
$res = $conn->query("SELECT classification, COUNT(*) AS c FROM job_returns GROUP BY classification");
while ($r = $res->fetch_assoc()) $counts[$r['classification']] = (int)$r['c'];
// "Today" is PHP's date, the same one returns-save.php checks against
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM jobs WHERE job_date = ?");
$stmt->bind_param('s', $today); $stmt->execute();
$todayJobs = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();

jsonResponse(true, 'OK', ['items' => $items, 'counts' => $counts, 'today_jobs' => $todayJobs, 'today' => $today, 'types' => PRACTICAL_RETURN_TYPES]);
