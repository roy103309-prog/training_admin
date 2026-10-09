<?php
require_once __DIR__ . '/student-common.php';

/**
 * Practical jobs (job_categories, jobs).
 * Jobs belong to the New Life students; each job's payable is taken from the
 * student's deposit (job_balance in studentBaseSql), so nothing is posted to Accounts.
 */

const PRACTICAL_VERIFY_METHODS = ['supervisor', 'biometric'];

function practicalCategoryFind(mysqli $conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM job_categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) throw new NlUserError('Job category not found');
    return $row;
}

// Next job number of a day: 20260818-003. Call inside a transaction (locks the day's rows).
function practicalNextJobNo(mysqli $conn, $date) {
    $stmt = $conn->prepare("SELECT COALESCE(MAX(daily_serial), 0) AS n FROM jobs WHERE job_date = ? FOR UPDATE");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $serial = (int)$stmt->get_result()->fetch_assoc()['n'] + 1;
    $stmt->close();
    return [$serial, str_replace('-', '', $date) . '-' . str_pad((string)$serial, 3, '0', STR_PAD_LEFT)];
}

const PRACTICAL_RETURN_TYPES = ['retrain' => 'Retrain', 'plate_returned' => 'Plate Returned', 'failed' => 'Failed / Defect'];

// Jobs with student, supervisor and issuer names, and their return / retrain entry
function practicalJobSql() {
    return "SELECT j.*, s.student_id AS student_code, s.name AS student_name, s.phone_number, s.course_name, s.photo,
                   vu.name AS verified_by_name, cu.name AS created_by_name,
                   r.id AS return_id, r.classification AS return_type, r.remarks AS return_remarks
            FROM jobs j
            LEFT JOIN students s ON s.id = j.student_id
            LEFT JOIN job_returns r ON r.job_id = j.id
            LEFT JOIN " . nlCoreTable('users') . " vu ON vu.id = j.verified_by
            LEFT JOIN " . nlCoreTable('users') . " cu ON cu.id = j.created_by";
}

// One job with what its token shows (student's balance and the jobs taken in its category)
function practicalJobForToken(mysqli $conn, $id) {
    $stmt = $conn->prepare(practicalJobSql() . " WHERE j.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$job) jsonResponse(false, 'Job not found');

    $job['job_balance'] = studentFind($conn, (int)$job['student_id'])['job_balance'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS taken FROM jobs WHERE student_id = ? AND category_id = ? AND id <= ?");
    $stmt->bind_param('iii', $job['student_id'], $job['category_id'], $job['id']);
    $stmt->execute();
    $job['category_job_no'] = (int)$stmt->get_result()->fetch_assoc()['taken'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT max_jobs FROM job_categories WHERE id = ?");
    $stmt->bind_param('i', $job['category_id']);
    $stmt->execute();
    $job['category_max_jobs'] = (int)($stmt->get_result()->fetch_assoc()['max_jobs'] ?? 0);
    $stmt->close();
    return $job;
}

// Jobs a student has taken in each category: [category_id => count]
function practicalStudentCategoryCounts(mysqli $conn, $studentId) {
    $stmt = $conn->prepare("SELECT category_id, COUNT(*) AS c FROM jobs WHERE student_id = ? GROUP BY category_id");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $out = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $out[(int)$r['category_id']] = (int)$r['c'];
    $stmt->close();
    return $out;
}

/* ------------------------------------------------------------------
 *  Out of Student (out_jobs): outside candidates, paid in cash
 * ------------------------------------------------------------------ */

// Next token of a day: OUT-20260818-001. Call inside a transaction (locks the day's rows).
function practicalNextOutToken(mysqli $conn, $date) {
    $stmt = $conn->prepare("SELECT COALESCE(MAX(daily_serial), 0) AS n FROM out_jobs WHERE job_date = ? FOR UPDATE");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $serial = (int)$stmt->get_result()->fetch_assoc()['n'] + 1;
    $stmt->close();
    return [$serial, 'OUT-' . str_replace('-', '', $date) . '-' . str_pad((string)$serial, 3, '0', STR_PAD_LEFT)];
}

// Out jobs with payment method, voucher number and issuer name
function practicalOutSql() {
    return "SELECT o.*, pm.name AS payment_method, v.voucher_no, cu.name AS created_by_name
            FROM out_jobs o
            LEFT JOIN " . nlCoreTable('payment_methods') . " pm ON pm.id = o.payment_method_id
            LEFT JOIN " . nlCoreTable('acc_vouchers') . " v ON v.id = o.voucher_id
            LEFT JOIN " . nlCoreTable('users') . " cu ON cu.id = o.created_by";
}

function practicalOutFind(mysqli $conn, $id) {
    $stmt = $conn->prepare(practicalOutSql() . " WHERE o.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) jsonResponse(false, 'Out-student job not found');
    return $row;
}

/* ------------------------------------------------------------------
 *  Overseas placement and companies
 * ------------------------------------------------------------------ */

const PRACTICAL_OVERSEAS_STATUSES = [
    'not_applied' => 'Not Applied',
    'interview'   => 'Interview',
    'selected'    => 'Selected',
];

/* ------------------------------------------------------------------
 *  Store (store_products, store_ledger)
 * ------------------------------------------------------------------ */

const PRACTICAL_STORE_MOVEMENTS = ['in' => 'IN', 'out' => 'OUT'];

// Product row locked for a stock change. Call inside a transaction.
function practicalProductLock(mysqli $conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM store_products WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) throw new NlUserError('Product not found');
    return $row;
}

// Adds (or subtracts, for a negative $delta) stock; never lets it go below zero
function practicalStockChange(mysqli $conn, array $product, $delta) {
    $new = round((float)$product['current_stock'] + $delta, 2);
    if ($new < -0.001) {
        throw new NlUserError('Not enough stock of "' . $product['name'] . '": ' . (float)$product['current_stock'] . ' ' . $product['unit'] . ' available');
    }
    $stmt = $conn->prepare("UPDATE store_products SET current_stock = ? WHERE id = ?");
    $stmt->bind_param('di', $new, $product['id']);
    $stmt->execute();
    $stmt->close();
    return $new;
}
