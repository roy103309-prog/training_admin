<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name        = sanitize($in['name']         ?? '');
$mobile      = sanitize($in['mobile']       ?? '');
$email       = sanitize($in['email']        ?? '');
$gender      = in_array($in['gender'] ?? '', ['male','female','other'], true) ? $in['gender'] : null;
$joining     = sanitize($in['joining_date'] ?? '');
$roleId      = (int)($in['role_id'] ?? 0);
$username    = sanitize($in['username']     ?? '');
$password    = $in['password']       ?? '';
$shift       = sanitize($in['shift']        ?? '');
$gradeId     = (int)($in['grade_id'] ?? 0);
$salaryType  = in_array($in['salary_type'] ?? '', ['daily','weekly','monthly'], true) ? $in['salary_type'] : 'monthly';
$basicSalary = (float)($in['basic_salary'] ?? 0);
$salaryStat  = (int)($in['salary_status'] ?? 1);
$isactive    = (int)($in['isactive'] ?? 1);

if ($name === '' || $mobile === '' || $password === '' || $roleId <= 0)
    jsonResponse(false, 'Name, mobile, password and role are required');

// Uniqueness check
$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->bind_param('s', $mobile); $stmt->execute();
if ($stmt->get_result()->fetch_assoc()) { $stmt->close(); jsonResponse(false, 'Mobile already exists'); }
$stmt->close();

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO users
        (name, mobile, password_hash, employee_code, email, gender, joining_date, username,
         role_id, location_id, shift, grade_id, salary_type, basic_salary, salary_status,
         isemployee, isactive)
        VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
    $jd = $joining !== '' ? $joining : null;
    $em = $email   !== '' ? $email   : null;
    $un = $username!== '' ? $username : null;
    $sh = $shift   !== '' ? $shift   : null;
    $gd = $gradeId > 0    ? $gradeId : null;
    $stmt->bind_param('sssssssiisisdii',
        $name, $mobile, $password, $em, $gender, $jd, $un,
        $roleId, $locationId, $sh, $gd, $salaryType, $basicSalary, $salaryStat,
        $isactive);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();

    // Assign employee_code now we have the id
    $code = 'EMP' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
    $u = $conn->prepare("UPDATE users SET employee_code = ? WHERE id = ?");
    $u->bind_param('si', $code, $id);
    $u->execute(); $u->close();

    // Branch pivot
    $up = $conn->prepare("INSERT IGNORE INTO user_locations (user_id, location_id) VALUES (?, ?)");
    $up->bind_param('ii', $id, $locationId);
    $up->execute(); $up->close();

    $conn->commit();
    jsonResponse(true, 'Employee created', ['id' => $id, 'employee_code' => $code]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed: ' . $e->getMessage());
}
