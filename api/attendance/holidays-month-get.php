<?php
// Existing holidays for a year+month → map date => holiday_type_id. ?year=&month=
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

$stmt = $conn->prepare("SELECT holiday_date, holiday_type_id, reason FROM att_holidays
                        WHERE location_id = ? AND holiday_date BETWEEN ? AND ?");
$stmt->bind_param('iss', $locationId, $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$map = [];
while ($r = $res->fetch_assoc()) {
    $map[$r['holiday_date']] = [
        'holiday_type_id' => $r['holiday_type_id'] ? (int)$r['holiday_type_id'] : 0,
        'reason'          => $r['reason']
    ];
}
$stmt->close();

jsonResponse(true, 'OK', ['start' => $start, 'end' => $end, 'map' => $map]);
