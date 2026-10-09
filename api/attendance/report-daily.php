<?php
// Attendance report for a single date. ?date=YYYY-MM-DD
// Per employee: shift (from att_settings for weekday) vs actual (att_records),
// late minutes, early-out minutes, status (present/absent/leave/movement/holiday/off/not-marked).
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$date = sanitize($_GET['date'] ?? '');
if ($date === '') jsonResponse(false, 'Date required');
$dow = (int)date('w', strtotime($date));

// Is the date a company holiday?
$stmt = $conn->prepare("SELECT t.name FROM att_holidays h LEFT JOIN att_holiday_types t ON t.id = h.holiday_type_id
                        WHERE h.location_id = ? AND h.holiday_date = ? LIMIT 1");
$stmt->bind_param('is', $locationId, $date); $stmt->execute();
$hr = $stmt->get_result()->fetch_assoc();
$holidayName = $hr ? ($hr['name'] ?: 'Holiday') : '';
$stmt->close();

// Active employees
$stmt = $conn->prepare("SELECT u.id, u.employee_code, u.name, r.name AS role_name
                        FROM users u LEFT JOIN roles r ON r.id = u.role_id
                        WHERE u.isemployee = 1 AND u.isactive = 1 AND (u.location_id = ? OR u.location_id IS NULL)
                        ORDER BY u.employee_code, u.id");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $emps = [];
while ($r = $res->fetch_assoc()) $emps[] = $r;
$stmt->close();

// Shift rules for weekday
$stmt = $conn->prepare("SELECT user_id, in_time, out_time, late_min, absent_min FROM att_settings WHERE location_id = ? AND day_of_week = ?");
$stmt->bind_param('ii', $locationId, $dow); $stmt->execute();
$res = $stmt->get_result(); $shift = [];
while ($r = $res->fetch_assoc()) $shift[(int)$r['user_id']] = $r;
$stmt->close();

// Actual records
$stmt = $conn->prepare("SELECT user_id, in_time, out_time, is_absent FROM att_records WHERE location_id = ? AND att_date = ?");
$stmt->bind_param('is', $locationId, $date); $stmt->execute();
$res = $stmt->get_result(); $rec = [];
while ($r = $res->fetch_assoc()) $rec[(int)$r['user_id']] = $r;
$stmt->close();

// Approved leave
$stmt = $conn->prepare("SELECT l.user_id, lt.name AS leave_type FROM att_leaves l
                        LEFT JOIN att_leave_types lt ON lt.id = l.leave_type_id
                        WHERE l.location_id = ? AND l.status = 'approved' AND ? BETWEEN l.from_date AND l.to_date");
$stmt->bind_param('is', $locationId, $date); $stmt->execute();
$res = $stmt->get_result(); $leave = [];
while ($r = $res->fetch_assoc()) $leave[(int)$r['user_id']] = $r['leave_type'] ?: 'Leave';
$stmt->close();

// Movement
$stmt = $conn->prepare("SELECT user_id, place, auto_present FROM att_movements WHERE location_id = ? AND ? BETWEEN from_date AND to_date");
$stmt->bind_param('is', $locationId, $date); $stmt->execute();
$res = $stmt->get_result(); $move = [];
while ($r = $res->fetch_assoc()) $move[(int)$r['user_id']] = $r;
$stmt->close();

function t2m($t) { // 'HH:MM[:SS]' → minutes
    if (!$t) return null;
    $p = explode(':', $t);
    return ((int)$p[0]) * 60 + ((int)($p[1] ?? 0));
}
function m2h($m) {
    if ($m === null) return '-';
    $h = intdiv($m, 60); $mm = $m % 60;
    return sprintf('%02d:%02d', $h, $mm);
}

$rows = [];
$sum = ['present'=>0,'absent'=>0,'leave'=>0,'movement'=>0,'off'=>0,'holiday'=>0,'not_marked'=>0,'late'=>0,'early'=>0];

foreach ($emps as $e) {
    $uid = (int)$e['id'];
    $sh  = $shift[$uid] ?? null;
    $rc  = $rec[$uid] ?? null;
    $isWorkDay = $sh ? true : false;

    $shIn  = $sh ? t2m($sh['in_time'])  : null;
    $shOut = $sh ? t2m($sh['out_time']) : null;
    $lateAllow   = $sh ? (int)$sh['late_min']   : 0;
    $absentAllow = $sh ? (int)$sh['absent_min'] : 0;

    $actIn  = $rc ? t2m($rc['in_time'])  : null;
    $actOut = $rc ? t2m($rc['out_time']) : null;

    $status = ''; $statusCls = '';
    $lateMin = 0; $earlyMin = 0;

    if ($holidayName !== '') {
        $status = 'Holiday'; $statusCls = 'secondary'; $sum['holiday']++;
    } elseif (isset($leave[$uid])) {
        $status = 'Leave: ' . $leave[$uid]; $statusCls = 'warning'; $sum['leave']++;
    } elseif (!$isWorkDay) {
        $status = 'Weekly Off'; $statusCls = 'secondary'; $sum['off']++;
    } elseif (isset($move[$uid]) && (int)$move[$uid]['auto_present'] === 1) {
        $status = 'Movement'; $statusCls = 'info'; $sum['movement']++;
    } elseif ($rc && (int)$rc['is_absent'] === 1) {
        $status = 'Absent'; $statusCls = 'danger'; $sum['absent']++;
    } elseif ($actIn !== null) {
        // Present — compute late + early-out beyond allowances
        if ($shIn !== null && $actIn > $shIn) {
            $rawLate = $actIn - $shIn;
            $lateMin = max(0, $rawLate - $lateAllow);
        }
        if ($shOut !== null && $actOut !== null && $actOut < $shOut) {
            $rawEarly = $shOut - $actOut;
            $earlyMin = max(0, $rawEarly - $absentAllow);
        }
        $status = $lateMin > 0 ? 'Present (Late)' : 'Present';
        $statusCls = $lateMin > 0 ? 'warning' : 'success';
        $sum['present']++;
        $sum['late']  += $lateMin;
        $sum['early'] += $earlyMin;
    } else {
        $status = 'Not Marked'; $statusCls = 'light'; $sum['not_marked']++;
    }

    $rows[] = [
        'employee_code' => $e['employee_code'],
        'name'          => $e['name'],
        'role_name'     => $e['role_name'],
        'shift_in'      => $sh ? substr($sh['in_time'] ?? '', 0, 5)  : '-',
        'shift_out'     => $sh ? substr($sh['out_time'] ?? '', 0, 5) : '-',
        'actual_in'     => $rc && $rc['in_time']  ? substr($rc['in_time'], 0, 5)  : '-',
        'actual_out'    => $rc && $rc['out_time'] ? substr($rc['out_time'], 0, 5) : '-',
        'late_min'      => $lateMin,
        'early_min'     => $earlyMin,
        'status'        => $status,
        'status_cls'    => $statusCls
    ];
}

jsonResponse(true, 'OK', [
    'date'        => $date,
    'holiday'     => $holidayName,
    'rows'        => $rows,
    'summary'     => $sum
]);
