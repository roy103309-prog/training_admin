<?php
// Sync a month's holidays. payload: { year, month, items:[{date, holiday_type_id}] }
// Replaces existing holidays in that month with provided list.
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in    = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$year  = (int)($in['year']  ?? 0);
$month = (int)($in['month'] ?? 0);
$items = is_array($in['items'] ?? null) ? $in['items'] : [];
if ($year < 2000 || $month < 1 || $month > 12) jsonResponse(false, 'Invalid year/month');

$start = sprintf('%04d-%02d-01', $year, $month);
$end   = date('Y-m-t', strtotime($start));

$conn->begin_transaction();
try {
    // Clear existing month holidays
    $del = $conn->prepare("DELETE FROM att_holidays WHERE location_id = ? AND holiday_date BETWEEN ? AND ?");
    $del->bind_param('iss', $locationId, $start, $end);
    $del->execute(); $del->close();

    $ins = $conn->prepare("INSERT INTO att_holidays (location_id, holiday_type_id, holiday_date, reason, isactive) VALUES (?, ?, ?, ?, 1)");
    $count = 0;
    foreach ($items as $it) {
        $date = sanitize($it['date'] ?? '');
        if ($date === '' || strpos($date, $start === '' ? '' : sprintf('%04d-%02d', $year, $month)) !== 0) continue;
        $typeId = (int)($it['holiday_type_id'] ?? 0);
        $typeVal = $typeId > 0 ? $typeId : null;
        $reason  = sanitize($it['reason'] ?? '');
        $ins->bind_param('iiss', $locationId, $typeVal, $date, $reason);
        $ins->execute();
        $count++;
    }
    $ins->close();
    $conn->commit();
    jsonResponse(true, 'Saved ' . $count . ' holiday(s) for ' . $start, ['saved' => $count]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed: ' . $e->getMessage());
}
