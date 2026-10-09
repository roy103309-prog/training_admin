<?php
require_once __DIR__ . '/student-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$row = studentFind($conn, $id);

$files = [$row['photo']];
foreach (['student_documents', 'certificates'] as $t) {
    $stmt = $conn->prepare("SELECT file_path FROM $t WHERE student_id = ?");
    $stmt->bind_param('i', $id); $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $f) $files[] = $f['file_path'];
    $stmt->close();
}

$conn->begin_transaction();
try {
    // Fee vouchers posted to Accounts go too
    $stmt = $conn->prepare("SELECT voucher_id FROM student_payments WHERE student_id = ? AND voucher_id IS NOT NULL");
    $stmt->bind_param('i', $id); $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) nlAccDeleteVoucher($conn, (int)$p['voucher_id']);
    $stmt->close();
    foreach (['student_documents', 'student_payments', 'certificates', 'job_returns', 'jobs', 'student_overseas'] as $t) {
        $stmt = $conn->prepare("DELETE FROM $t WHERE student_id = ?");
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    }
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
foreach ($files as $f) if ($f) nlDeleteFile($f);

jsonResponse(true, 'Student deleted');
