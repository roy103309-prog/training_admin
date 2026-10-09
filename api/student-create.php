<?php
require_once __DIR__ . '/student-common.php';
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

// multipart/form-data (photo + documents)
$in = $_POST;
$name = nlClean($in['name'] ?? '', 150);
$courseId = (int)($in['course_id'] ?? 0);
if ($name === '') jsonResponse(false, 'Student name is required');
if ($courseId <= 0) jsonResponse(false, 'Please select a course');

$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param('i', $courseId); $stmt->execute();
$course = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$course) jsonResponse(false, 'Course not found');

$studentId = nlClean($in['student_id'] ?? '', 40);
if ($studentId === '') $studentId = studentNextId($conn);
$stmt = $conn->prepare("SELECT 1 FROM students WHERE student_id = ?");
$stmt->bind_param('s', $studentId); $stmt->execute();
$exists = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
if ($exists) jsonResponse(false, 'Student ID "' . $studentId . '" already exists');

$fee = isset($in['course_fee']) && $in['course_fee'] !== '' ? max(0, (float)$in['course_fee']) : (float)$course['fee'];
$paid = max(0, round((float)($in['paid_amount'] ?? 0), 2));
if ($paid > $fee) jsonResponse(false, 'Initial payment cannot be more than the course fee');
$pmId = (int)($in['payment_method_id'] ?? 0);

$photo = nlUploadImage('photo', 'students') ?? '';
$f = studentProfileFields($in);
$admissionDate = nlDate($in['admission_date'] ?? '') ?? date('Y-m-d');
$duration = nlClean($in['duration'] ?? '', 60) ?: $course['duration'];
$uid = (int)$user['user_id'];

$docs = [];
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO students
        (location_id, student_id, admission_date, name, dob, blood_group, nid, passport_number, phone_number, present_address, permanent_address,
         course_id, course_category, course_name, course_fee, duration, father_name, mother_name, guardian_relation, guardian_phone,
         reference_name, reference_remarks, photo, status, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param('issssssssssissdssssssssi', $locationId, $studentId, $admissionDate, $name, $f['dob'], $f['blood_group'],
        $f['nid'], $f['passport_number'], $f['phone_number'], $f['present_address'], $f['permanent_address'],
        $courseId, $course['category'], $course['name'], $fee, $duration, $f['father_name'], $f['mother_name'],
        $f['guardian_relation'], $f['guardian_phone'], $f['reference_name'], $f['reference_remarks'], $photo, $uid);
    $stmt->execute();
    $id = (int)$conn->insert_id;
    $stmt->close();

    if ($paid > 0) {
        studentRecordPayment($conn, $user, $locationId, ['id' => $id, 'name' => $name, 'student_id' => $studentId],
            $paid, date('Y-m-d H:i:s'), $pmId, 'Admission payment');
    }
    studentSaveDocuments($conn, $id, $docs);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    if ($photo) nlDeleteFile($photo);
    foreach ($docs as $d) nlDeleteFile($d);
    throw $e;
}

jsonResponse(true, 'Admission saved. Student ID: ' . $studentId, ['id' => $id, 'student_id' => $studentId]);
