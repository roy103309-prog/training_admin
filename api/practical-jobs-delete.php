<?php
require_once __DIR__ . '/practical-common.php';
// Deleting a job gives its payable back to the student's job balance
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("SELECT job_no FROM jobs WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$job = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$job) jsonResponse(false, 'Job not found');

$conn->begin_transaction();
try {
    foreach (['job_returns' => 'job_id', 'jobs' => 'id'] as $table => $col) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE $col = ?");
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
jsonResponse(true, 'Job ' . $job['job_no'] . ' deleted');
