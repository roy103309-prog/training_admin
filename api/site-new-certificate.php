<?php
// Public website endpoint (no login)
require __DIR__ . '/../includes/site-bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['error' => 'Method not allowed'], 405); }
$in = json_input();
$sid = trim((string)($in['studentId'] ?? ''));
$dob = trim((string)($in['dob'] ?? ''));
if ($sid === '' || $dob === '') { json_response(['error' => 'Roll number and date of birth are required'], 422); }
$st = db_one('SELECT * FROM students WHERE student_id = ? AND dob = ? LIMIT 1', [$sid, $dob]);
if (!$st) { json_response(['error' => 'Certificate not found'], 404); }
$cert = db_one("SELECT * FROM certificates WHERE student_id = ? AND file_path <> '' ORDER BY issue_date DESC, id DESC LIMIT 1", [$st['id']]);
if (!$cert) { json_response(['error' => 'Certificate not found'], 404); }
json_response([
    'student_name' => $st['name'],
    'student_id' => $st['student_id'],
    'student_father_name' => $st['father_name'],
    'student_mother_name' => $st['mother_name'],
    'student_address' => $st['present_address'] ?: $st['permanent_address'],
    'course_name' => $st['course_name'],
    'issue_date' => $cert['issue_date'],
    'certificate_type' => $cert['title'],
    'student_image' => asset_url($st['photo']),
    'student_image_type' => 'path',
    'certificate_image' => asset_url($cert['file_path']),
    'certificate_image_type' => 'path',
]);
