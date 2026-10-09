<?php
require_once __DIR__ . '/practical-common.php';
// Return / retrain entry of a job: only on the job's own date, one entry per job (saving again updates it)
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$jobId = (int)($in['job_id'] ?? 0);
$type = (string)($in['classification'] ?? '');
$remarks = nlClean($in['remarks'] ?? '', 500);
if (!isset(PRACTICAL_RETURN_TYPES[$type])) jsonResponse(false, 'Choose Retrain, Plate Returned or Failed');
if ($remarks === '') jsonResponse(false, 'Write the inspection remarks / reason');

$stmt = $conn->prepare("SELECT id, student_id, job_date, job_no FROM jobs WHERE id = ?");
$stmt->bind_param('i', $jobId); $stmt->execute();
$job = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$job) jsonResponse(false, 'Job not found');
$today = date('Y-m-d');
if ($job['job_date'] !== $today) {
    jsonResponse(false, 'Return / retrain can be entered only on the job date (' . date('d-m-Y', strtotime($job['job_date'])) . ')');
}

$uid = (int)$user['user_id'];
$stmt = $conn->prepare("INSERT INTO job_returns (job_id, student_id, return_date, classification, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE classification = VALUES(classification), remarks = VALUES(remarks)");
$stmt->bind_param('iisssi', $jobId, $job['student_id'], $today, $type, $remarks, $uid);
$stmt->execute(); $stmt->close();

jsonResponse(true, PRACTICAL_RETURN_TYPES[$type] . ' saved for job ' . $job['job_no']);
