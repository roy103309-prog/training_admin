<?php
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$stmt = $conn->prepare("
    SELECT l.*, u.name AS emp_name, u.employee_code, ro.name AS role_name, lt.name AS leave_type_name
    FROM att_leaves l
    JOIN users u ON u.id = l.user_id
    LEFT JOIN roles ro ON ro.id = u.role_id
    LEFT JOIN att_leave_types lt ON lt.id = l.leave_type_id
    WHERE l.location_id = ?
    ORDER BY l.id DESC LIMIT 1000");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();
jsonResponse(true, 'OK', ['items' => $items]);
