<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$roleId   = (int)($in['role_id']  ?? 0);
$gradeId  = (int)($in['grade_id'] ?? 0);
$type     = in_array($in['salary_type'] ?? '', ['daily','weekly','monthly'], true) ? $in['salary_type'] : 'monthly';
$amount   = (float)($in['amount']   ?? 0);
$isactive = (int)($in['isactive'] ?? 1);

if ($roleId <= 0 || $gradeId <= 0 || $amount < 0) jsonResponse(false, 'Role, grade and amount required');

$stmt = $conn->prepare("INSERT INTO salary_setup (location_id, role_id, grade_id, salary_type, amount, isactive)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE amount = VALUES(amount), isactive = VALUES(isactive)");
$stmt->bind_param('iiisdi', $locationId, $roleId, $gradeId, $type, $amount, $isactive);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$id = $conn->insert_id ?: 0;
$stmt->close();
jsonResponse(true, 'Salary setup saved', ['id' => $id]);
