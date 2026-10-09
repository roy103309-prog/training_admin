<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($in['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'ID required');

$name        = sanitize($in['name']   ?? '');
$mobile      = sanitize($in['mobile'] ?? '');
$email       = sanitize($in['email']  ?? '');
$gender      = in_array($in['gender'] ?? '', ['male','female','other'], true) ? $in['gender'] : null;
$joining     = sanitize($in['joining_date'] ?? '');
$roleId      = (int)($in['role_id'] ?? 0);
$username    = sanitize($in['username'] ?? '');
$password    = $in['password'] ?? '';
$shift       = sanitize($in['shift']  ?? '');
$gradeId     = (int)($in['grade_id'] ?? 0);
$salaryType  = in_array($in['salary_type'] ?? '', ['daily','weekly','monthly'], true) ? $in['salary_type'] : 'monthly';
$basicSalary = (float)($in['basic_salary'] ?? 0);
$salaryStat  = (int)($in['salary_status'] ?? 1);
$isactive    = (int)($in['isactive'] ?? 1);

$jd = $joining !== '' ? $joining  : null;
$em = $email   !== '' ? $email    : null;
$un = $username!== '' ? $username : null;
$sh = $shift   !== '' ? $shift    : null;
$gd = $gradeId > 0    ? $gradeId  : null;

$sql = "UPDATE users SET name = ?, mobile = ?, email = ?, gender = ?, joining_date = ?, username = ?,
                          role_id = ?, shift = ?, grade_id = ?, salary_type = ?, basic_salary = ?,
                          salary_status = ?, isactive = ?";
$types = 'sssssiisisdii';
$params = [$name, $mobile, $em, $gender, $jd, $un, $roleId, $sh, $gd, $salaryType, $basicSalary, $salaryStat, $isactive];

if ($password !== '') { $sql .= ", password_hash = ?"; $types .= 's'; $params[] = hashPassword($password); }

$sql .= " WHERE id = ? AND isemployee = 1";
$types .= 'i'; $params[] = $id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Employee updated');
