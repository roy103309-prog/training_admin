<?php
// Single employee → all 7 day rules. ?user_id=
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) jsonResponse(false, 'Employee required');

// Employee info
$stmt = $conn->prepare("SELECT u.id, u.employee_code, u.name, r.name AS role_name
                        FROM users u LEFT JOIN roles r ON r.id = u.role_id
                        WHERE u.id = ? LIMIT 1");
$stmt->bind_param('i', $userId); $stmt->execute();
$emp = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$emp) jsonResponse(false, 'Employee not found');

// Existing settings keyed by day_of_week
$stmt = $conn->prepare("SELECT day_of_week, in_time, out_time, late_min, absent_min, is_off
                        FROM att_settings WHERE location_id = ? AND user_id = ?");
$stmt->bind_param('ii', $locationId, $userId); $stmt->execute();
$res = $stmt->get_result();
$byDay = [];
while ($r = $res->fetch_assoc()) $byDay[(int)$r['day_of_week']] = $r;
$stmt->close();

$names = attDayNames();
$days = [];
// Only configured days are work days. Days without a saved rule are NOT work days.
for ($d = 0; $d < 7; $d++) {
    if (!isset($byDay[$d])) continue;
    $row = $byDay[$d];
    $days[] = [
        'day_of_week' => $d,
        'day_name'    => $names[$d],
        'in_time'     => substr($row['in_time'] ?? '', 0, 5),
        'out_time'    => substr($row['out_time'] ?? '', 0, 5),
        'late_min'    => (int)$row['late_min'],
        'absent_min'  => (int)$row['absent_min'],
        'is_off'      => (int)$row['is_off']
    ];
}

jsonResponse(true, 'OK', ['employee' => $emp, 'days' => $days]);
