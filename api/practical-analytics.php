<?php
require_once __DIR__ . '/practical-common.php';
/**
 * Analytics & Reports. ?from=&to= limit jobs, out-student jobs, returns, store and money received
 * to a period; overseas status and contract / due are always for all students.
 */
$user = requireAuth();
$conn = getNewLifeConnection();

$from = nlDate($_GET['from'] ?? '') ?? '1970-01-01';
$to = nlDate($_GET['to'] ?? '') ?? '9999-12-31';
if ($from > $to) jsonResponse(false, 'From date is after To date');

// Jobs per category (students and out-students)
$stmt = $conn->prepare("SELECT c.id, c.name, c.is_active,
                               (SELECT COUNT(*) FROM jobs j WHERE j.category_id = c.id AND j.job_date BETWEEN ? AND ?) AS student_jobs,
                               (SELECT COALESCE(SUM(j.payable), 0) FROM jobs j WHERE j.category_id = c.id AND j.job_date BETWEEN ? AND ?) AS student_payable,
                               (SELECT COUNT(*) FROM out_jobs o WHERE o.category_id = c.id AND o.job_date BETWEEN ? AND ?) AS out_jobs,
                               (SELECT COALESCE(SUM(o.paid), 0) FROM out_jobs o WHERE o.category_id = c.id AND o.job_date BETWEEN ? AND ?) AS out_paid
                        FROM job_categories c ORDER BY c.sort_order, c.id");
$stmt->bind_param('ssssssss', $from, $to, $from, $to, $from, $to, $from, $to);
$stmt->execute();
$categories = array_values(array_filter($stmt->get_result()->fetch_all(MYSQLI_ASSOC), function ($c) {
    return (int)$c['is_active'] === 1 || $c['student_jobs'] > 0 || $c['out_jobs'] > 0;
}));
$stmt->close();

$returns = ['retrain' => 0, 'plate_returned' => 0, 'failed' => 0];
$stmt = $conn->prepare("SELECT classification, COUNT(*) AS c FROM job_returns WHERE return_date BETWEEN ? AND ? GROUP BY classification");
$stmt->bind_param('ss', $from, $to); $stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $returns[$r['classification']] = (int)$r['c'];
$stmt->close();

// Overseas placement of all students
$overseas = array_fill_keys(array_keys(PRACTICAL_OVERSEAS_STATUSES), 0);
$res = $conn->query("SELECT COALESCE(o.overseas_status, 'not_applied') AS st, COUNT(*) AS c FROM students s
                     LEFT JOIN student_overseas o ON o.student_id = s.id GROUP BY st");
while ($r = $res->fetch_assoc()) $overseas[$r['st']] = (int)$r['c'];
$countries = $conn->query("SELECT destination_country AS country, COUNT(*) AS c FROM student_overseas
                           WHERE overseas_status = 'selected' AND destination_country <> '' GROUP BY destination_country ORDER BY c DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Money
$all = $conn->query("SELECT COALESCE(SUM(s.course_fee), 0) AS contract, COALESCE(SUM(COALESCE(p.paid, 0)), 0) AS deposit,
                            COALESCE(SUM(GREATEST(s.course_fee - COALESCE(p.paid, 0), 0)), 0) AS due
                     FROM students s LEFT JOIN (SELECT student_id, SUM(amount) AS paid FROM student_payments GROUP BY student_id) p ON p.student_id = s.id")->fetch_assoc();
$fromDt = $from . ' 00:00:00'; $toDt = $to . ' 23:59:59';
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS d FROM student_payments WHERE payment_date BETWEEN ? AND ?");
$stmt->bind_param('ss', $fromDt, $toDt); $stmt->execute();
$periodDeposit = (float)$stmt->get_result()->fetch_assoc()['d']; $stmt->close();

// Store movement in the period
$stmt = $conn->prepare("SELECT p.name, p.unit, COALESCE(SUM(CASE WHEN l.movement = 'in' THEN l.quantity END), 0) AS qty_in,
                               COALESCE(SUM(CASE WHEN l.movement = 'out' THEN l.quantity END), 0) AS qty_out, p.current_stock
                        FROM store_products p JOIN store_ledger l ON l.product_id = p.id AND l.txn_date BETWEEN ? AND ?
                        GROUP BY p.id ORDER BY qty_out DESC, p.name");
$stmt->bind_param('ss', $from, $to); $stmt->execute();
$store = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

jsonResponse(true, 'OK', [
    'categories' => $categories,
    'totals' => [
        'student_jobs' => array_sum(array_column($categories, 'student_jobs')),
        'student_payable' => array_sum(array_column($categories, 'student_payable')),
        'out_jobs' => array_sum(array_column($categories, 'out_jobs')),
        'out_paid' => array_sum(array_column($categories, 'out_paid')),
    ],
    'returns' => $returns,
    'overseas' => $overseas,
    'overseas_labels' => PRACTICAL_OVERSEAS_STATUSES,
    'countries' => $countries,
    'finance' => ['contract' => (float)$all['contract'], 'deposit' => (float)$all['deposit'], 'due' => (float)$all['due'], 'period_deposit' => $periodDeposit],
    'store' => $store,
]);
