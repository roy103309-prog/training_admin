<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$periodType  = in_array($in['period_type'] ?? '', ['daily','weekly','monthly'], true) ? $in['period_type'] : 'monthly';
$periodLabel = sanitize($in['period_label'] ?? '');
$periodStart = sanitize($in['period_start'] ?? '');
$periodEnd   = sanitize($in['period_end']   ?? '');
$status      = in_array($in['status'] ?? '', ['pending','paid'], true) ? $in['status'] : 'pending';
$allowIds    = is_array($in['allowance_ids'] ?? null) ? array_map('intval', $in['allowance_ids']) : [];
$deductIds   = is_array($in['deduction_ids'] ?? null) ? array_map('intval', $in['deduction_ids']) : [];
$onlySalaryTypes = is_array($in['salary_types'] ?? null) ? $in['salary_types'] : [];

if ($periodLabel === '') jsonResponse(false, 'Period label required');

// Resolve allowance/deduction templates
$applyAllow = []; $applyDeduct = [];
$ids = array_values(array_unique(array_filter(array_merge($allowIds, $deductIds), function($x){ return $x > 0; })));
if ($ids) {
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT id, name, type, value_type, amount FROM allowance_deductions
                            WHERE location_id = ? AND isactive = 1 AND id IN ($place)");
    $bindTypes = 'i' . str_repeat('i', count($ids));
    $stmt->bind_param($bindTypes, ...array_merge([$locationId], $ids));
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $row = ['name' => $r['name'], 'value_type' => $r['value_type'], 'amount' => (float)$r['amount']];
        if ($r['type'] === 'allowance' && in_array((int)$r['id'], $allowIds, true))  $applyAllow[]  = $row;
        if ($r['type'] === 'deduction' && in_array((int)$r['id'], $deductIds, true)) $applyDeduct[] = $row;
    }
    $stmt->close();
}

// Eligible employees
$sql = "SELECT id, name, employee_code, basic_salary, salary_type FROM users
        WHERE isemployee = 1 AND isactive = 1 AND salary_status = 1
          AND (location_id = ? OR location_id IS NULL)";
$params = [$locationId]; $types = 'i';
$valid = array_values(array_intersect($onlySalaryTypes, ['daily','weekly','monthly']));
if ($valid) {
    $place = implode(',', array_fill(0, count($valid), '?'));
    $sql .= " AND salary_type IN ($place)";
    foreach ($valid as $v) { $params[] = $v; $types .= 's'; }
}
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$employees = []; while ($e = $res->fetch_assoc()) $employees[] = $e;
$stmt->close();

if (!$employees) jsonResponse(false, 'No active employees found');

$inserted = 0; $skipped = 0; $details = [];
$conn->begin_transaction();
try {
    $insSalary = $conn->prepare("INSERT IGNORE INTO salaries
        (location_id, user_id, period_type, period_label, period_start, period_end,
         basic_salary, total_allowance, total_deduction, net_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insItem = $conn->prepare("INSERT INTO salary_items (salary_id, kind, name, amount) VALUES (?, ?, ?, ?)");

    $ps = $periodStart !== '' ? $periodStart : null;
    $pe = $periodEnd   !== '' ? $periodEnd   : null;

    foreach ($employees as $e) {
        $uid   = (int)$e['id'];
        $basic = (float)$e['basic_salary'];
        $tAllow = 0; $tDeduct = 0; $items = [];
        foreach ($applyAllow as $a) {
            $amt = $a['amount']; if ($a['value_type'] === 'percentage') $amt = $basic * $amt / 100;
            $tAllow += $amt; $items[] = ['allowance', $a['name'], $amt];
        }
        foreach ($applyDeduct as $d) {
            $amt = $d['amount']; if ($d['value_type'] === 'percentage') $amt = $basic * $amt / 100;
            $tDeduct += $amt; $items[] = ['deduction', $d['name'], $amt];
        }
        $net = $basic + $tAllow - $tDeduct;

        $insSalary->bind_param('iissssddddss',
            $locationId, $uid, $periodType, $periodLabel, $ps, $pe,
            $basic, $tAllow, $tDeduct, $net, $status);
        if (!$insSalary->execute()) { $skipped++; continue; }
        if ($insSalary->affected_rows === 0) { $skipped++; continue; } // duplicate (UNIQUE)

        $salaryId = $conn->insert_id;
        foreach ($items as $it) {
            $kind = $it[0]; $nm = $it[1]; $amt = $it[2];
            $insItem->bind_param('issd', $salaryId, $kind, $nm, $amt);
            $insItem->execute();
        }
        $inserted++;
        $details[] = ['user_id' => $uid, 'name' => $e['name'], 'code' => $e['employee_code'], 'net' => $net];
    }

    $insSalary->close(); $insItem->close();
    $conn->commit();
    jsonResponse(true, "Generated $inserted / Skipped $skipped (already exists)", [
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'details'  => $details
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed: ' . $e->getMessage());
}
