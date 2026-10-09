<?php
require_once __DIR__ . '/practical-common.php';
// Practical dashboard: totals, today's jobs by category, today's out-student jobs / returns, recent jobs
$user = requireAuth();
$conn = getNewLifeConnection();
$today = date('Y-m-d');

$students = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active, COALESCE(SUM(course_fee), 0) AS contract FROM students")->fetch_assoc();
$deposit = (float)$conn->query("SELECT COALESCE(SUM(amount), 0) AS d FROM student_payments")->fetch_assoc()['d'];
$due = (float)$conn->query("SELECT COALESCE(SUM(GREATEST(s.course_fee - COALESCE(p.paid, 0), 0)), 0) AS due FROM students s
                            LEFT JOIN (SELECT student_id, SUM(amount) AS paid FROM student_payments GROUP BY student_id) p ON p.student_id = s.id")->fetch_assoc()['due'];
$allJobs = (int)$conn->query("SELECT COUNT(*) AS c FROM jobs")->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT c.id, c.name, c.is_active, COUNT(j.id) AS count, COALESCE(SUM(j.payable), 0) AS payable
                        FROM job_categories c LEFT JOIN jobs j ON j.category_id = c.id AND j.job_date = ?
                        GROUP BY c.id HAVING c.is_active = 1 OR count > 0 ORDER BY c.sort_order, c.id");
$stmt->bind_param('s', $today); $stmt->execute();
$todayCats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(paid), 0) AS paid FROM out_jobs WHERE job_date = ?");
$stmt->bind_param('s', $today); $stmt->execute();
$out = $stmt->get_result()->fetch_assoc(); $stmt->close();

$returns = ['retrain' => 0, 'plate_returned' => 0, 'failed' => 0];
$stmt = $conn->prepare("SELECT classification, COUNT(*) AS c FROM job_returns WHERE return_date = ? GROUP BY classification");
$stmt->bind_param('s', $today); $stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $returns[$r['classification']] = (int)$r['c'];
$stmt->close();

$recent = $conn->query(practicalJobSql() . " ORDER BY j.job_date DESC, j.id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, 'OK', [
    'today' => $today,
    'cards' => [
        'students' => (int)$students['total'], 'active_students' => (int)$students['active'],
        'today_jobs' => array_sum(array_column($todayCats, 'count')), 'today_payable' => array_sum(array_column($todayCats, 'payable')),
        'contract' => (float)$students['contract'], 'deposit' => $deposit, 'due' => $due, 'all_jobs' => $allJobs,
        'today_out' => (int)$out['c'], 'today_out_paid' => (float)$out['paid'],
    ],
    'today_categories' => $todayCats,
    'today_returns' => $returns,
    'recent' => $recent,
]);
