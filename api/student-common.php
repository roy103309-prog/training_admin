<?php
require_once __DIR__ . '/newlife-common.php';
require_once __DIR__ . '/accounts/_auto-income.php';

/**
 * Student module (courses, students, student_documents, student_payments).
 */

const STUDENT_STATUSES = ['active', 'completed', 'inactive', 'dropped'];

// Students with paid / due totals; practical jobs are paid from the deposit (job_balance = paid - jobs)
function studentBaseSql() {
    return "SELECT s.*, COALESCE(p.paid, 0) AS paid_amount,
                   GREATEST(s.course_fee - COALESCE(p.paid, 0), 0) AS due_amount,
                   COALESCE(j.used, 0) AS job_used, COALESCE(j.cnt, 0) AS job_count,
                   COALESCE(p.paid, 0) - COALESCE(j.used, 0) AS job_balance,
                   COALESCE(o.overseas_status, 'not_applied') AS overseas_status, COALESCE(o.destination_country, '') AS destination_country,
                   o.company_id, oc.name AS company_name, COALESCE(o.passport_submitted, 0) AS passport_submitted, COALESCE(o.remarks, '') AS overseas_remarks
            FROM students s
            LEFT JOIN (SELECT student_id, SUM(amount) AS paid FROM student_payments GROUP BY student_id) p ON p.student_id = s.id
            LEFT JOIN (SELECT student_id, SUM(payable) AS used, COUNT(*) AS cnt FROM jobs GROUP BY student_id) j ON j.student_id = s.id
            " . studentOverseasJoin();
}

// Overseas placement (Practical module) of each student; o = student_overseas, oc = companies
function studentOverseasJoin() {
    return " LEFT JOIN student_overseas o ON o.student_id = s.id LEFT JOIN companies oc ON oc.id = o.company_id";
}

function studentFind(mysqli $conn, $id) {
    $stmt = $conn->prepare(studentBaseSql() . " WHERE s.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) jsonResponse(false, 'Student not found');
    return $row;
}

// Next ID like STD-260001 (prefix from General Settings + 2-digit year + serial)
function studentNextId(mysqli $conn) {
    $prefix = nlSetting('student_id_prefix', 'STD') . '-' . date('y');
    $like = $prefix . '%';
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id LIKE ?");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $n = 0;
    while ($r = $res->fetch_assoc()) {
        $rest = substr($r['student_id'], strlen($prefix));
        if (ctype_digit($rest)) $n = max($n, (int)$rest);
    }
    $stmt->close();
    return $prefix . str_pad((string)($n + 1), 4, '0', STR_PAD_LEFT);
}

/**
 * Save a fee payment; posts an income voucher (Course Fee) to Basic Accounts
 * when General Settings -> Accounts -> auto post is on. Call inside a transaction.
 */
function studentRecordPayment(mysqli $conn, array $user, $locationId, array $student, $amount, $date, $paymentMethodId, $notes) {
    $voucherId = null;
    if (nlSetting('auto_post_income') === '1') {
        $voucherId = nlAccPostIncome($conn, $locationId, $date, $amount,
            'Course fee - ' . $student['name'] . ' (' . $student['student_id'] . ')', $paymentMethodId ?: null, $notes,
            (int)$user['user_id'], $user['name']);
    }
    $pm = $paymentMethodId ?: null;
    $uid = (int)$user['user_id'];
    $stmt = $conn->prepare("INSERT INTO student_payments (location_id, student_id, amount, payment_date, payment_method_id, notes, voucher_id, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iidsisii', $locationId, $student['id'], $amount, $date, $pm, $notes, $voucherId, $uid);
    $stmt->execute();
    $id = (int)$conn->insert_id;
    $stmt->close();
    return $id;
}

/**
 * Saves the files of the "documents[]" input and adds them to $saved (stored paths),
 * so the caller can remove them when its transaction rolls back.
 */
function studentSaveDocuments(mysqli $conn, $studentId, array &$saved) {
    foreach (nlUploadedFiles('documents') as $f) {
        $up = nlUploadFile($f, 'student-docs', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx']);
        if (!$up['success']) throw new NlUserError($f['name'] . ': ' . $up['message']);
        $name = mb_substr((string)$f['name'], 0, 255);
        $stmt = $conn->prepare("INSERT INTO student_documents (student_id, file_path, original_name) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $studentId, $up['path'], $name);
        $stmt->execute();
        $stmt->close();
        $saved[] = $up['path'];
    }
}

// Personal fields shared by admission and edit
function studentProfileFields(array $in) {
    return [
        'dob' => nlDate($in['dob'] ?? ''),
        'blood_group' => nlClean($in['blood_group'] ?? '', 5),
        'nid' => nlClean($in['nid'] ?? '', 40),
        'passport_number' => nlClean($in['passport_number'] ?? '', 40),
        'phone_number' => nlClean($in['phone_number'] ?? '', 30),
        'present_address' => nlClean($in['present_address'] ?? '', 1000),
        'permanent_address' => nlClean($in['permanent_address'] ?? '', 1000),
        'father_name' => nlClean($in['father_name'] ?? '', 150),
        'mother_name' => nlClean($in['mother_name'] ?? '', 150),
        'guardian_relation' => nlClean($in['guardian_relation'] ?? '', 40),
        'guardian_phone' => nlClean($in['guardian_phone'] ?? '', 30),
        'reference_name' => nlClean($in['reference_name'] ?? '', 150),
        'reference_remarks' => nlClean($in['reference_remarks'] ?? '', 1000),
    ];
}
