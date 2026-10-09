<?php
// Save daily attendance. payload: { date, rows:[{user_id,in_time,out_time,is_absent}] }
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$date = sanitize($in['date'] ?? '');
$rows = is_array($in['rows'] ?? null) ? $in['rows'] : [];
if ($date === '') jsonResponse(false, 'Date required');
if (!$rows)       jsonResponse(false, 'No rows');

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO att_records
        (location_id, user_id, att_date, in_time, out_time, is_absent, source)
        VALUES (?, ?, ?, ?, ?, ?, 'manual')
        ON DUPLICATE KEY UPDATE
            in_time = VALUES(in_time), out_time = VALUES(out_time), is_absent = VALUES(is_absent)");
    $count = 0;
    foreach ($rows as $row) {
        $uid = (int)($row['user_id'] ?? 0);
        if ($uid <= 0) continue;
        $abs = (int)($row['is_absent'] ?? 0);
        $inV  = $abs ? null : (trim($row['in_time'] ?? '')  !== '' ? trim($row['in_time'])  : null);
        $outV = $abs ? null : (trim($row['out_time'] ?? '') !== '' ? trim($row['out_time']) : null);
        $stmt->bind_param('iisssi', $locationId, $uid, $date, $inV, $outV, $abs);
        $stmt->execute();
        $count++;
    }
    $stmt->close();
    $conn->commit();
    jsonResponse(true, 'Saved ' . $count . ' record(s)', ['saved' => $count]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed: ' . $e->getMessage());
}
