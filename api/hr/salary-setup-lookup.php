<?php
// Resolve basic salary by role + grade + salary_type
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

$roleId  = (int)($_GET['role_id']  ?? 0);
$gradeId = (int)($_GET['grade_id'] ?? 0);
$type    = in_array($_GET['salary_type'] ?? '', ['daily','weekly','monthly'], true) ? $_GET['salary_type'] : 'monthly';

$conn = getTenantConnection();

$stmt = $conn->prepare("SELECT amount FROM salary_setup WHERE location_id = ? AND role_id = ? AND grade_id = ? AND salary_type = ? AND isactive = 1 LIMIT 1");
$stmt->bind_param('iiis', $locationId, $roleId, $gradeId, $type);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
jsonResponse(true, 'OK', ['amount' => $row ? (float)$row['amount'] : 0]);
