<?php
require_once __DIR__ . '/student-common.php';
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

$in = nlInput();
$student = studentFind($conn, (int)($in['student_id'] ?? 0));
$amount = round((float)($in['amount'] ?? 0), 2);
if ($amount <= 0) jsonResponse(false, 'Enter a valid amount greater than zero');
if ($amount > (float)$student['due_amount'] + 0.001) jsonResponse(false, 'Amount is more than the due amount (' . number_format((float)$student['due_amount'], 2) . ')');

$date = nlDate($in['payment_date'] ?? '') ?? date('Y-m-d');
$dateTime = $date === date('Y-m-d') ? date('Y-m-d H:i:s') : $date . ' 12:00:00';
$pmId = (int)($in['payment_method_id'] ?? 0);
$notes = nlClean($in['notes'] ?? '', 255);

$conn->begin_transaction();
try {
    $id = studentRecordPayment($conn, $user, $locationId, $student, $amount, $dateTime, $pmId, $notes);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
jsonResponse(true, 'Payment recorded', ['id' => $id]);
