<?php
require_once __DIR__ . '/practical-common.php';
// Student for the job entry form (by Student ID or id): balance and jobs taken per category
$user = requireAuth();
$conn = getNewLifeConnection();

$id = (int)($_GET['id'] ?? 0);
$code = nlClean($_GET['code'] ?? '', 40);
if ($id <= 0 && $code !== '') {
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->bind_param('s', $code); $stmt->execute();
    $id = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0); $stmt->close();
    if ($id <= 0) jsonResponse(false, 'No student with ID ' . $code);
}
$s = studentFind($conn, $id);

jsonResponse(true, 'OK', [
    'student' => [
        'id' => $s['id'], 'student_id' => $s['student_id'], 'name' => $s['name'], 'father_name' => $s['father_name'],
        'phone_number' => $s['phone_number'], 'course_name' => $s['course_name'], 'photo' => $s['photo'], 'status' => $s['status'],
        'paid_amount' => $s['paid_amount'], 'job_used' => $s['job_used'], 'job_balance' => $s['job_balance'], 'job_count' => $s['job_count'],
    ],
    'taken' => (object)practicalStudentCategoryCounts($conn, $id),
]);
