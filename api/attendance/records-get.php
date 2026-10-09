<?php
// Attendance grid for a date. ?date=YYYY-MM-DD
// Returns each active employee + existing record (in/out/absent) for that date.
// Pre-fills default in/out from att_settings for that weekday; flags leave/movement.
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$date = sanitize($_GET['date'] ?? '');
if ($date === '') jsonResponse(false, 'Date required');
$dow = (int)date('w', strtotime($date)); // 0=Sun

// Active employees
$stmt = $conn->prepare("SELECT u.id, u.employee_code, u.name, u.mobile, r.name AS role_name
                        FROM users u LEFT JOIN roles r ON r.id = u.role_id
                        WHERE u.isemployee = 1 AND u.isactive = 1 AND (u.location_id = ? OR u.location_id IS NULL)
                        ORDER BY u.employee_code, u.id");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $emps = [];
while ($r = $res->fetch_assoc()) $emps[] = $r;
$stmt->close();

// Existing records keyed by user
$stmt = $conn->prepare("SELECT user_id, in_time, out_time, is_absent, source FROM att_records WHERE location_id = ? AND att_date = ?");
$stmt->bind_param('is', $locationId, $date); $stmt->execute();
$res = $stmt->get_result(); $rec = [];
while ($r = $res->fetch_assoc()) $rec[(int)$r['user_id']] = $r;
$stmt->close();

// Default shift times per employee for this weekday
$stmt = $conn->prepare("SELECT user_id, in_time, out_time FROM att_settings WHERE location_id = ? AND day_of_week = ?");
$stmt->bind_param('ii', $locationId, $dow); $stmt->execute();
$res = $stmt->get_result(); $shift = [];
while ($r = $res->fetch_assoc()) $shift[(int)$r['user_id']] = $r;
$stmt->close();

// Approved leave covering this date
$stmt = $conn->prepare("SELECT user_id FROM att_leaves WHERE location_id = ? AND status = 'approved' AND ? BETWEEN from_date AND to_date");
$stmt->bind_param('is', $locationId, $date); $stmt->execute();
$res = $stmt->get_result(); $onLeave = [];
while ($r = $res->fetch_assoc()) $onLeave[(int)$r['user_id']] = true;
$stmt->close();

// Movement covering this date
$stmt = $conn->prepare("SELECT user_id, in_time, out_time, auto_present FROM att_movements WHERE location_id = ? AND ? BETWEEN from_date AND to_date");
$stmt->bind_param('is', $locationId, $date); $stmt->execute();
$res = $stmt->get_result(); $onMove = [];
while ($r = $res->fetch_assoc()) $onMove[(int)$r['user_id']] = $r;
$stmt->close();

$rows = [];
foreach ($emps as $e) {
    $uid = (int)$e['id'];
    $r   = $rec[$uid] ?? null;
    $sh  = $shift[$uid] ?? null;
    $mv  = $onMove[$uid] ?? null;
    $leave = isset($onLeave[$uid]);

    $inT  = $r ? substr($r['in_time'] ?? '', 0, 5) : ($mv ? substr($mv['in_time'] ?? '', 0, 5) : ($sh ? substr($sh['in_time'] ?? '', 0, 5) : ''));
    $outT = $r ? substr($r['out_time'] ?? '', 0, 5) : ($mv ? substr($mv['out_time'] ?? '', 0, 5) : ($sh ? substr($sh['out_time'] ?? '', 0, 5) : ''));
    $abs  = $r ? (int)$r['is_absent'] : 0;

    $tag = '';
    if ($leave) $tag = 'leave';
    elseif ($mv && (int)$mv['auto_present'] === 1) $tag = 'movement';

    $rows[] = [
        'user_id'       => $uid,
        'employee_code' => $e['employee_code'],
        'name'          => $e['name'],
        'mobile'        => $e['mobile'],
        'role_name'     => $e['role_name'],
        'in_time'       => $inT,
        'out_time'      => $outT,
        'is_absent'     => $abs,
        'tag'           => $tag,
        'has_shift'     => $sh ? 1 : 0
    ];
}

jsonResponse(true, 'OK', ['date' => $date, 'rows' => $rows]);
