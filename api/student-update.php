<?php
require_once __DIR__ . '/student-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

// multipart/form-data (optional new photo + extra documents)
$in = $_POST;
$id = (int)($in['id'] ?? 0);
$row = studentFind($conn, $id);

$name = nlClean($in['name'] ?? '', 150);
if ($name === '') jsonResponse(false, 'Student name is required');
$status = in_array($in['status'] ?? '', STUDENT_STATUSES, true) ? $in['status'] : $row['status'];

// Course change: take fee / duration from the course unless given
$courseId = (int)($in['course_id'] ?? $row['course_id']);
$courseName = $row['course_name']; $courseCategory = $row['course_category'];
$fee = isset($in['course_fee']) && $in['course_fee'] !== '' ? max(0, (float)$in['course_fee']) : (float)$row['course_fee'];
$duration = nlClean($in['duration'] ?? $row['duration'], 60);
if ($courseId && $courseId !== (int)$row['course_id']) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param('i', $courseId); $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$course) jsonResponse(false, 'Course not found');
    $courseName = $course['name']; $courseCategory = $course['category'];
    if (!isset($in['course_fee']) || $in['course_fee'] === '') $fee = (float)$course['fee'];
    if (($in['duration'] ?? '') === '') $duration = $course['duration'];
}
if ($fee + 0.001 < (float)$row['paid_amount']) jsonResponse(false, 'Course fee cannot be less than the amount already paid (' . $row['paid_amount'] . ')');

$newPhoto = nlUploadImage('photo', 'students');
$photo = $newPhoto ?? $row['photo'];
$v = studentProfileFields($in);
$admissionDate = nlDate($in['admission_date'] ?? '') ?? $row['admission_date'];
$cid = $courseId ?: null;

$docs = [];
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE students SET admission_date = ?, name = ?, dob = ?, blood_group = ?, nid = ?, passport_number = ?, phone_number = ?,
            present_address = ?, permanent_address = ?, course_id = ?, course_category = ?, course_name = ?, course_fee = ?, duration = ?,
            father_name = ?, mother_name = ?, guardian_relation = ?, guardian_phone = ?, reference_name = ?, reference_remarks = ?,
            photo = ?, status = ? WHERE id = ?");
    $stmt->bind_param('sssssssssissdsssssssssi', $admissionDate, $name, $v['dob'], $v['blood_group'], $v['nid'], $v['passport_number'],
        $v['phone_number'], $v['present_address'], $v['permanent_address'], $cid, $courseCategory, $courseName, $fee, $duration,
        $v['father_name'], $v['mother_name'], $v['guardian_relation'], $v['guardian_phone'], $v['reference_name'], $v['reference_remarks'],
        $photo, $status, $id);
    $stmt->execute();
    $stmt->close();
    studentSaveDocuments($conn, $id, $docs);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    if ($newPhoto) nlDeleteFile($newPhoto);
    foreach ($docs as $d) nlDeleteFile($d);
    throw $e;
}
if ($newPhoto && $row['photo']) nlDeleteFile($row['photo']);

jsonResponse(true, 'Student updated');
