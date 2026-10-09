<?php
require_once __DIR__ . '/student-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("SELECT student_id, amount, voucher_id FROM student_payments WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$p = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$p) jsonResponse(false, 'Payment not found');

$conn->begin_transaction();
try {
    // Practical jobs are paid from the deposit: it cannot drop below what the jobs used
    $sid = (int)$p['student_id'];
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $sid); $stmt->execute(); $stmt->get_result()->free(); $stmt->close();
    $balance = (float)studentFind($conn, $sid)['job_balance'];
    if ($balance - (float)$p['amount'] < -0.001) {
        throw new NlUserError('Practical jobs already used this deposit (job balance ' . number_format($balance, 2) . '). Delete those jobs first.');
    }

    nlAccDeleteVoucher($conn, (int)$p['voucher_id']);
    $stmt = $conn->prepare("DELETE FROM student_payments WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
jsonResponse(true, $p['voucher_id'] ? 'Payment deleted (its Accounts voucher was removed too)' : 'Payment deleted');
