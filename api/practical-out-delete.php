<?php
require_once __DIR__ . '/practical-common.php';
// Deleting an out-student job removes its income voucher from Accounts too
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$job = practicalOutFind($conn, (int)($in['id'] ?? 0));

$conn->begin_transaction();
try {
    if ($job['voucher_id']) nlAccDeleteVoucher($conn, (int)$job['voucher_id']);
    $stmt = $conn->prepare("DELETE FROM out_jobs WHERE id = ?");
    $stmt->bind_param('i', $job['id']); $stmt->execute(); $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
jsonResponse(true, 'Out-student job ' . $job['token_no'] . ' deleted');
