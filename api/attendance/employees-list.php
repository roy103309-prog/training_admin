<?php
// Active employees for attendance setting grid. Optional ?search= by code/name.
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$search = sanitize($_GET['search'] ?? '');
$where  = "u.isemployee = 1 AND u.isactive = 1 AND (u.location_id = ? OR u.location_id IS NULL)";
$params = [$locationId]; $types = 'i';
if ($search !== '') {
    $where .= " AND (u.employee_code LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss';
}

$sql = "SELECT u.id, u.employee_code, u.name, r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.role_id
        WHERE $where
        ORDER BY u.employee_code, u.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$items = []; while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();
jsonResponse(true, 'OK', ['items' => $items]);
