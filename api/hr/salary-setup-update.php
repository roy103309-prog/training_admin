<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id       = (int)($in['id'] ?? 0);
$roleId   = (int)($in['role_id']  ?? 0);
$gradeId  = (int)($in['grade_id'] ?? 0);
$type     = in_array($in['salary_type'] ?? '', ['daily','weekly','monthly'], true) ? $in['salary_type'] : 'monthly';
$amount   = (float)($in['amount'] ?? 0);
$isactive = (int)($in['isactive'] ?? 1);
if ($id <= 0) jsonResponse(false, 'ID required');

$stmt = $conn->prepare("UPDATE salary_setup SET role_id = ?, grade_id = ?, salary_type = ?, amount = ?, isactive = ?
                        WHERE id = ? AND location_id = ?");
$stmt->bind_param('iisdiii', $roleId, $gradeId, $type, $amount, $isactive, $id, $locationId);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Salary setup updated');
