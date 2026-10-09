<?php
require_once __DIR__ . '/practical-common.php';
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

$in = nlInput();
$studentId = (int)($in['student_id'] ?? 0);
$categoryId = (int)($in['category_id'] ?? 0);
$date = nlDate($in['job_date'] ?? '') ?? date('Y-m-d');
$discount = round(max(0, (float)($in['discount'] ?? 0)), 2);
$method = in_array($in['verify_method'] ?? '', PRACTICAL_VERIFY_METHODS, true) ? $in['verify_method'] : 'supervisor';
$verifiedBy = (int)($in['verified_by'] ?? 0) ?: null;
$remarks = nlClean($in['remarks'] ?? '', 255);
if ($method === 'biometric') jsonResponse(false, 'Fingerprint scanner is not set up yet. Use supervisor verification.');

$conn->begin_transaction();
try {
    // Lock the student so two entries at once cannot spend the same balance
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $studentId); $stmt->execute();
    $found = (bool)$stmt->get_result()->fetch_row(); $stmt->close();
    if (!$found) throw new NlUserError('Student not found');
    $student = studentFind($conn, $studentId);
    if (in_array($student['status'], ['inactive', 'dropped'], true)) {
        throw new NlUserError('Student is ' . $student['status'] . '. Jobs can be issued to active students only.');
    }

    $cat = practicalCategoryFind($conn, $categoryId);
    if ((int)$cat['is_active'] !== 1) throw new NlUserError('Job category "' . $cat['name'] . '" is inactive');

    if ((int)$cat['max_jobs'] > 0) {
        $taken = practicalStudentCategoryCounts($conn, $studentId)[$categoryId] ?? 0;
        if ($taken >= (int)$cat['max_jobs']) {
            throw new NlUserError("Limit reached: {$taken} of {$cat['max_jobs']} {$cat['name']} jobs already taken");
        }
    }

    $price = (float)$cat['fee'];
    if ($discount > $price) throw new NlUserError('Discount cannot be more than the price');
    $payable = round($price - $discount, 2);
    if ($payable > (float)$student['job_balance'] + 0.001) {
        throw new NlUserError('Not enough job balance (' . number_format((float)$student['job_balance'], 2) . '). Collect a deposit first.');
    }

    if ($verifiedBy) {
        $stmt = $conn->prepare("SELECT id FROM " . nlCoreTable('users') . " WHERE id = ?");
        $stmt->bind_param('i', $verifiedBy); $stmt->execute();
        if (!$stmt->get_result()->fetch_row()) $verifiedBy = null;
        $stmt->close();
    }

    [$serial, $jobNo] = practicalNextJobNo($conn, $date);
    $uid = (int)$user['user_id'];
    $catName = $cat['name'];
    $stmt = $conn->prepare("INSERT INTO jobs (location_id, job_no, job_date, daily_serial, student_id, category_id, category_name,
                                              price, discount, payable, verify_method, verified_by, remarks, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('issiiisdddsisi', $locationId, $jobNo, $date, $serial, $studentId, $categoryId, $catName,
        $price, $discount, $payable, $method, $verifiedBy, $remarks, $uid);
    $stmt->execute();
    $id = (int)$conn->insert_id;
    $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}

jsonResponse(true, 'Job ' . $jobNo . ' issued', ['job' => practicalJobForToken($conn, $id)]);
