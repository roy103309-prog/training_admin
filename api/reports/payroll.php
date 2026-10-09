<?php
require_once '../tenant-config.php';
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../hr/_bootstrap.php';

requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

list($from, $to) = rep_dateRange(true);
$periodType = trim($_GET['period_type'] ?? '');
$status     = trim($_GET['status']      ?? '');
$userId     = (int)($_GET['user_id']    ?? 0);

$conn = getTenantConnection();

$where  = "s.location_id = ? AND DATE(s.created_at) BETWEEN ? AND ?";
$types  = 'iss';
$params = [$locationId, $from, $to];

if (in_array($periodType, ['daily','weekly','monthly'], true)) {
    $where .= " AND s.period_type = ?"; $types .= 's'; $params[] = $periodType;
}
if (in_array($status, ['pending','paid'], true)) {
    $where .= " AND s.status = ?"; $types .= 's'; $params[] = $status;
}
if ($userId > 0) {
    $where .= " AND s.user_id = ?"; $types .= 'i'; $params[] = $userId;
}

$sql = "SELECT s.id,
               s.created_at,
               u.employee_code,
               u.name AS employee_name,
               r.name AS role_name,
               g.name AS grade_name,
               s.period_type,
               s.period_label,
               s.basic_salary,
               s.total_allowance,
               s.total_deduction,
               s.net_amount,
               s.status
          FROM salaries s
          JOIN users u  ON u.id = s.user_id
          LEFT JOIN roles  r ON r.id = u.role_id
          LEFT JOIN grades g ON g.id = u.grade_id
         WHERE $where
         ORDER BY s.created_at DESC, s.id DESC
         LIMIT 5000";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Attach allowance/deduction line items per salary
$ids = array_map(function ($r) { return (int)$r['id']; }, $rows);
$itemsBySalary = [];
if ($ids) {
    $place = implode(',', array_fill(0, count($ids), '?'));
    $it = $conn->prepare("SELECT salary_id, kind, name, amount FROM salary_items WHERE salary_id IN ($place) ORDER BY id");
    $it->bind_param(str_repeat('i', count($ids)), ...$ids);
    $it->execute();
    $ir = $it->get_result();
    while ($x = $ir->fetch_assoc()) {
        $sid = (int)$x['salary_id'];
        if (!isset($itemsBySalary[$sid])) $itemsBySalary[$sid] = ['allowance' => [], 'deduction' => []];
        $itemsBySalary[$sid][$x['kind']][] = ['name' => $x['name'], 'amount' => (float)$x['amount']];
    }
    $it->close();
}

// Each item on its own line so the cell grows vertically (row height responsive)
$fmtItems = function ($arr) {
    if (!$arr) return '';
    return implode('<br>', array_map(function ($i) {
        return htmlspecialchars($i['name'], ENT_QUOTES) . ': ' . number_format($i['amount'], 2);
    }, $arr));
};

foreach ($rows as &$r) {
    $sid = (int)$r['id'];
    $al = $itemsBySalary[$sid]['allowance'] ?? [];
    $de = $itemsBySalary[$sid]['deduction'] ?? [];
    $r['allowances']        = $al;
    $r['deductions']        = $de;
    $r['allowance_detail']  = $fmtItems($al);
    $r['deduction_detail']  = $fmtItems($de);
}
unset($r);

$summary = [
    'total_count'      => count($rows),
    'total_basic'      => 0.0,
    'total_allowance'  => 0.0,
    'total_deduction'  => 0.0,
    'total_net'        => 0.0,
    'paid_count'       => 0,
    'pending_count'    => 0
];
foreach ($rows as $r) {
    $summary['total_basic']     += (float)$r['basic_salary'];
    $summary['total_allowance'] += (float)$r['total_allowance'];
    $summary['total_deduction'] += (float)$r['total_deduction'];
    $summary['total_net']       += (float)$r['net_amount'];
    if ($r['status'] === 'paid') $summary['paid_count']++; else $summary['pending_count']++;
}

jsonResponse(true, 'OK', ['summary' => $summary, 'rows' => $rows]);
