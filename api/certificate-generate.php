<?php
require_once __DIR__ . '/certificate-common.php';
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

$in = nlInput();
$student = certStudent($conn, (int)($in['student_id'] ?? 0));
$title = nlClean($in['title'] ?? '', 255) ?: 'Certificate of Completion';
$number = nlClean($in['number'] ?? '', 60);
if ($number === '') $number = certNextNumber($conn);
$issue = nlDate($in['issue_date'] ?? '') ?? date('Y-m-d');
$meta = json_encode(is_array($in['metadata'] ?? null) ? $in['metadata'] : new stdClass(), JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("INSERT INTO certificates (location_id, student_id, certificate_no, title, issue_date, description, source) VALUES (?, ?, ?, ?, ?, ?, 'generated')");
$stmt->bind_param('iissss', $locationId, $student['id'], $number, $title, $issue, $meta);
$stmt->execute();
$id = (int)$conn->insert_id;
$stmt->close();

jsonResponse(true, 'Certificate generated', ['id' => $id, 'certificate_no' => $number, 'issue_date' => $issue, 'title' => $title]);
