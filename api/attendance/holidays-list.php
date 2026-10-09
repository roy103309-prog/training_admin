<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$stmt = $conn->prepare("
    SELECT h.*, t.name AS type_name
    FROM att_holidays h
    LEFT JOIN att_holiday_types t ON t.id = h.holiday_type_id
    WHERE h.location_id = ?
    ORDER BY h.holiday_date DESC, h.id DESC");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();
jsonResponse(true, 'OK', ['items' => $items]);
