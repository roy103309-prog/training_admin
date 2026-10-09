<?php
require_once __DIR__ . '/student-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();

$id = (int)($_GET['id'] ?? 0);
$code = nlClean($_GET['student_id'] ?? '', 40);
if ($id <= 0 && $code !== '') {
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->bind_param('s', $code); $stmt->execute();
    $id = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0); $stmt->close();
}
$student = studentFind($conn, $id);

// Payment method and voucher number come from the TrainingAdmin database
$stmt = $conn->prepare("SELECT p.id, p.amount, p.payment_date, p.notes, p.voucher_id, pm.name AS payment_method, v.voucher_no
                        FROM student_payments p
                        LEFT JOIN " . nlCoreTable('payment_methods') . " pm ON pm.id = p.payment_method_id
                        LEFT JOIN " . nlCoreTable('acc_vouchers') . " v ON v.id = p.voucher_id
                        WHERE p.student_id = ? ORDER BY p.payment_date DESC, p.id DESC");
$stmt->bind_param('i', $id); $stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$stmt = $conn->prepare("SELECT id, file_path, original_name, created_at FROM student_documents WHERE student_id = ? ORDER BY id");
$stmt->bind_param('i', $id); $stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$stmt = $conn->prepare("SELECT * FROM certificates WHERE student_id = ? ORDER BY id DESC");
$stmt->bind_param('i', $id); $stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// Practical jobs (paid from the deposit)
$stmt = $conn->prepare("SELECT j.id, j.job_no, j.job_date, j.category_name, j.price, j.discount, j.payable, r.classification AS return_type, r.remarks AS return_remarks
                        FROM jobs j LEFT JOIN job_returns r ON r.job_id = j.id WHERE j.student_id = ? ORDER BY j.job_date DESC, j.id DESC");
$stmt->bind_param('i', $id); $stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

jsonResponse(true, 'OK', [
    'student' => $student,
    'payments' => $payments,
    'documents' => $documents,
    'certificates' => $certificates,
    'jobs' => $jobs
]);
