<?php
require_once __DIR__ . '/practical-common.php';
// Overseas placement of one student (student_overseas, one row per student)
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$student = studentFind($conn, (int)($in['student_id'] ?? 0));
$status = (string)($in['overseas_status'] ?? 'not_applied');
if (!isset(PRACTICAL_OVERSEAS_STATUSES[$status])) jsonResponse(false, 'Invalid overseas status');
$country = nlClean($in['destination_country'] ?? '', 100);
$companyId = (int)($in['company_id'] ?? 0) ?: null;
$passport = nlFlag($in['passport_submitted'] ?? 0);
$remarks = nlClean($in['remarks'] ?? '', 500);

if ($companyId) {
    $stmt = $conn->prepare("SELECT country FROM companies WHERE id = ?");
    $stmt->bind_param('i', $companyId); $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$company) jsonResponse(false, 'Company not found');
    if ($country === '') $country = $company['country'];
}

$uid = (int)$user['user_id'];
$stmt = $conn->prepare("INSERT INTO student_overseas (student_id, overseas_status, destination_country, company_id, passport_submitted, remarks, updated_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE overseas_status = VALUES(overseas_status), destination_country = VALUES(destination_country),
                            company_id = VALUES(company_id), passport_submitted = VALUES(passport_submitted), remarks = VALUES(remarks), updated_by = VALUES(updated_by)");
$stmt->bind_param('issiisi', $student['id'], $status, $country, $companyId, $passport, $remarks, $uid);
$stmt->execute();
$stmt->close();

jsonResponse(true, 'Overseas status saved: ' . PRACTICAL_OVERSEAS_STATUSES[$status]);
