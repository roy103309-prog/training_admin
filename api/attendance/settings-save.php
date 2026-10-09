<?php
// Apply attendance rules. Two modes:
//  A) rows[]   : per-employee values  → [{user_id,in_time,out_time,late_min,absent_min,is_off}, ...] + days[]
//  B) user_ids[]: one shared rule for many employees + days[] + in_time/out_time/late_min/absent_min/is_off
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$days = is_array($in['days'] ?? null) ? array_map('intval', $in['days']) : [];
$days = array_values(array_filter($days, function ($x) { return $x >= 0 && $x <= 6; }));
if (!$days) jsonResponse(false, 'Select at least one day');

$rows = is_array($in['rows'] ?? null) ? $in['rows'] : null;

// Normalized work list: [user_id, in, out, late, absent, is_off]
$work = [];
if ($rows !== null) {
    foreach ($rows as $r) {
        $uid = (int)($r['user_id'] ?? 0);
        if ($uid <= 0) continue;
        $work[] = [
            $uid,
            trim($r['in_time'] ?? '')  !== '' ? trim($r['in_time'])  : null,
            trim($r['out_time'] ?? '') !== '' ? trim($r['out_time']) : null,
            max(0, (int)($r['late_min'] ?? 0)),
            max(0, (int)($r['absent_min'] ?? 0)),
            (int)($r['is_off'] ?? 0)
        ];
    }
} else {
    $userIds = is_array($in['user_ids'] ?? null) ? array_map('intval', $in['user_ids']) : [];
    $userIds = array_values(array_filter($userIds, function ($x) { return $x > 0; }));
    $inTime  = trim($in['in_time'] ?? '');
    $outTime = trim($in['out_time'] ?? '');
    $lateMin = max(0, (int)($in['late_min'] ?? 0));
    $absMin  = max(0, (int)($in['absent_min'] ?? 0));
    $isOff   = (int)($in['is_off'] ?? 0);
    foreach ($userIds as $uid) {
        $work[] = [$uid, $inTime !== '' ? $inTime : null, $outTime !== '' ? $outTime : null, $lateMin, $absMin, $isOff];
    }
}

if (!$work) jsonResponse(false, 'Select at least one employee');

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO att_settings
        (location_id, user_id, day_of_week, in_time, out_time, late_min, absent_min, is_off)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            in_time = VALUES(in_time), out_time = VALUES(out_time),
            late_min = VALUES(late_min), absent_min = VALUES(absent_min), is_off = VALUES(is_off)");
    $count = 0;
    foreach ($work as $w) {
        foreach ($days as $d) {
            $stmt->bind_param('iiissiii', $locationId, $w[0], $d, $w[1], $w[2], $w[3], $w[4], $w[5]);
            $stmt->execute();
            $count++;
        }
    }
    $stmt->close();
    $conn->commit();
    jsonResponse(true, 'Saved ' . $count . ' rule(s)', ['applied' => $count]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed: ' . $e->getMessage());
}
