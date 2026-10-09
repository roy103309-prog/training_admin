<?php
// Monthly attendance matrix. ?year=&month=
// Per employee × day: status code (P present, L late, A absent, V leave, M movement, O weekly-off, H holiday, - not marked).
// Plus per-employee totals + total late/early minutes.
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$year  = (int)($_GET['year']  ?? 0);
$month = (int)($_GET['month'] ?? 0);
if ($year < 2000 || $month < 1 || $month > 12) jsonResponse(false, 'Invalid year/month');

$start = sprintf('%04d-%02d-01', $year, $month);
$end   = date('Y-m-t', strtotime($start));
$daysInMonth = (int)date('t', strtotime($start));

function t2m2($t) { if (!$t) return null; $p = explode(':', $t); return ((int)$p[0]) * 60 + ((int)($p[1] ?? 0)); }

// Active employees
$stmt = $conn->prepare("SELECT u.id, u.employee_code, u.name, r.name AS role_name
                        FROM users u LEFT JOIN roles r ON r.id = u.role_id
                        WHERE u.isemployee = 1 AND u.isactive = 1 AND (u.location_id = ? OR u.location_id IS NULL)
                        ORDER BY u.employee_code, u.id");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $emps = [];
while ($r = $res->fetch_assoc()) $emps[] = $r;
$stmt->close();

// Shift rules: user_id => [dow => row]
$stmt = $conn->prepare("SELECT user_id, day_of_week, in_time, out_time, late_min, absent_min FROM att_settings WHERE location_id = ?");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $shift = [];
while ($r = $res->fetch_assoc()) $shift[(int)$r['user_id']][(int)$r['day_of_week']] = $r;
$stmt->close();

// Records: user_id => [date => row]
$stmt = $conn->prepare("SELECT user_id, att_date, in_time, out_time, is_absent FROM att_records WHERE location_id = ? AND att_date BETWEEN ? AND ?");
$stmt->bind_param('iss', $locationId, $start, $end); $stmt->execute();
$res = $stmt->get_result(); $rec = [];
while ($r = $res->fetch_assoc()) $rec[(int)$r['user_id']][$r['att_date']] = $r;
$stmt->close();

// Approved leaves: list of [user_id, from, to]
$stmt = $conn->prepare("SELECT user_id, from_date, to_date FROM att_leaves WHERE location_id = ? AND status = 'approved' AND NOT (to_date < ? OR from_date > ?)");
$stmt->bind_param('iss', $locationId, $start, $end); $stmt->execute();
$res = $stmt->get_result(); $leaves = [];
while ($r = $res->fetch_assoc()) $leaves[] = $r;
$stmt->close();

// Movements
$stmt = $conn->prepare("SELECT user_id, from_date, to_date, auto_present FROM att_movements WHERE location_id = ? AND NOT (to_date < ? OR from_date > ?)");
$stmt->bind_param('iss', $locationId, $start, $end); $stmt->execute();
$res = $stmt->get_result(); $moves = [];
while ($r = $res->fetch_assoc()) $moves[] = $r;
$stmt->close();

// Holidays: set of dates
$stmt = $conn->prepare("SELECT holiday_date FROM att_holidays WHERE location_id = ? AND holiday_date BETWEEN ? AND ?");
$stmt->bind_param('iss', $locationId, $start, $end); $stmt->execute();
$res = $stmt->get_result(); $holiday = [];
while ($r = $res->fetch_assoc()) $holiday[$r['holiday_date']] = true;
$stmt->close();

// Build per-date helpers
function ive_covers($list, $uid, $date) {
    foreach ($list as $x) {
        if ((int)$x['user_id'] === $uid && $date >= $x['from_date'] && $date <= $x['to_date']) return $x;
    }
    return null;
}

$rows = [];
foreach ($emps as $e) {
    $uid = (int)$e['id'];
    $days = [];
    $tot = ['P'=>0,'L'=>0,'A'=>0,'V'=>0,'M'=>0,'O'=>0,'H'=>0,'-'=>0,'late'=>0,'early'=>0];

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dow = (int)date('w', strtotime($dateStr));
        $sh  = $shift[$uid][$dow] ?? null;
        $rc  = $rec[$uid][$dateStr] ?? null;

        $code = '-';
        if (isset($holiday[$dateStr])) {
            $code = 'H';
        } elseif (ive_covers($leaves, $uid, $dateStr)) {
            $code = 'V';
        } elseif (!$sh) {
            $code = 'O';
        } else {
            $mv = ive_covers($moves, $uid, $dateStr);
            if ($mv && (int)$mv['auto_present'] === 1) {
                $code = 'M';
            } elseif ($rc && (int)$rc['is_absent'] === 1) {
                $code = 'A';
            } elseif ($rc && $rc['in_time']) {
                $shIn  = t2m2($sh['in_time']);
                $shOut = t2m2($sh['out_time']);
                $actIn = t2m2($rc['in_time']);
                $actOut= t2m2($rc['out_time']);
                $lateAllow = (int)$sh['late_min'];
                $absAllow  = (int)$sh['absent_min'];
                $lateMin = 0; $earlyMin = 0;
                if ($shIn !== null && $actIn > $shIn) $lateMin = max(0, ($actIn - $shIn) - $lateAllow);
                if ($shOut !== null && $actOut !== null && $actOut < $shOut) $earlyMin = max(0, ($shOut - $actOut) - $absAllow);
                $code = $lateMin > 0 ? 'L' : 'P';
                $tot['late']  += $lateMin;
                $tot['early'] += $earlyMin;
            } else {
                $code = '-';
            }
        }
        $days[$d] = $code;
        $tot[$code]++;
    }

    $rows[] = [
        'employee_code' => $e['employee_code'],
        'name'          => $e['name'],
        'role_name'     => $e['role_name'],
        'days'          => $days,
        'totals'        => $tot
    ];
}

jsonResponse(true, 'OK', [
    'year'         => $year,
    'month'        => $month,
    'days_in_month'=> $daysInMonth,
    'rows'         => $rows
]);
