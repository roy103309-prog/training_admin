<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId      = (int)($in['user_id'] ?? 0);
$periodType  = in_array($in['period_type'] ?? '', ['daily','weekly','monthly'], true) ? $in['period_type'] : 'monthly';
$periodLabel = sanitize($in['period_label'] ?? '');
$periodStart = sanitize($in['period_start'] ?? '');
$periodEnd   = sanitize($in['period_end']   ?? '');
$allowances  = is_array($in['allowances'] ?? null) ? $in['allowances'] : [];
$deductions  = is_array($in['deductions'] ?? null) ? $in['deductions'] : [];
$note        = sanitize($in['note'] ?? '');
$status      = in_array($in['status'] ?? '', ['pending','paid'], true) ? $in['status'] : 'pending';

if ($userId <= 0 || $periodLabel === '') jsonResponse(false, 'Employee and period are required');

// Fetch employee basic salary
$stmt = $conn->prepare("SELECT basic_salary FROM users WHERE id = ? AND isemployee = 1 LIMIT 1");
$stmt->bind_param('i', $userId); $stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) jsonResponse(false, 'Employee not found');
$basic = (float)$row['basic_salary'];

// Compute totals (each entry: {name, amount, value_type})
$totalAllow = 0; $resolvedAllow = [];
foreach ($allowances as $a) {
    $amt = (float)($a['amount'] ?? 0);
    if (($a['value_type'] ?? 'fixed') === 'percentage') $amt = $basic * $amt / 100;
    $resolvedAllow[] = ['name' => trim($a['name'] ?? ''), 'amount' => $amt];
    $totalAllow += $amt;
}
$totalDeduct = 0; $resolvedDeduct = [];
foreach ($deductions as $d) {
    $amt = (float)($d['amount'] ?? 0);
    if (($d['value_type'] ?? 'fixed') === 'percentage') $amt = $basic * $amt / 100;
    $resolvedDeduct[] = ['name' => trim($d['name'] ?? ''), 'amount' => $amt];
    $totalDeduct += $amt;
}
$net = $basic + $totalAllow - $totalDeduct;

$conn->begin_transaction();
try {
    $ps = ($periodStart !== '') ? $periodStart : null;
    $pe = ($periodEnd   !== '') ? $periodEnd   : null;
    $stmt = $conn->prepare("INSERT INTO salaries
        (location_id, user_id, period_type, period_label, period_start, period_end,
         basic_salary, total_allowance, total_deduction, net_amount, status, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iissssddddss',
        $locationId, $userId, $periodType, $periodLabel, $ps, $pe,
        $basic, $totalAllow, $totalDeduct, $net, $status, $note);
    if (!$stmt->execute()) {
        $err = $stmt->error; $stmt->close(); $conn->rollback();
        if (strpos($err, 'Duplicate') !== false) jsonResponse(false, 'Salary already generated for this period');
        jsonResponse(false, 'Failed: ' . $err);
    }
    $salaryId = $conn->insert_id; $stmt->close();

    if ($resolvedAllow || $resolvedDeduct) {
        $ins = $conn->prepare("INSERT INTO salary_items (salary_id, kind, name, amount) VALUES (?, ?, ?, ?)");
        foreach ($resolvedAllow as $a) {
            if ($a['name'] === '') continue;
            $kind = 'allowance';
            $ins->bind_param('issd', $salaryId, $kind, $a['name'], $a['amount']);
            $ins->execute();
        }
        foreach ($resolvedDeduct as $d) {
            if ($d['name'] === '') continue;
            $kind = 'deduction';
            $ins->bind_param('issd', $salaryId, $kind, $d['name'], $d['amount']);
            $ins->execute();
        }
        $ins->close();
    }

    $conn->commit();
    jsonResponse(true, 'Salary generated', [
        'id' => $salaryId,
        'basic_salary' => $basic,
        'total_allowance' => $totalAllow,
        'total_deduction' => $totalDeduct,
        'net_amount' => $net
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed: ' . $e->getMessage());
}
