<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$stmt = $conn->prepare("
    SELECT m.*, u.name AS emp_name, u.employee_code, ro.name AS role_name
    FROM att_movements m
    JOIN users u ON u.id = m.user_id
    LEFT JOIN roles ro ON ro.id = u.role_id
    WHERE m.location_id = ?
    ORDER BY m.id DESC LIMIT 1000");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();
jsonResponse(true, 'OK', ['items' => $items]);
