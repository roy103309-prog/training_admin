<?php
require_once __DIR__ . '/practical-common.php';
// Out of Student: practical job of an outside candidate, paid on the spot.
// The fee is posted to Accounts (head "Practical Test Fee") when General Settings -> Accounts allows it.
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

$in = nlInput();
$name = nlClean($in['candidate_name'] ?? '', 150);
$mobile = nlClean($in['mobile'] ?? '', 30);
$passport = nlClean($in['passport_number'] ?? '', 40);
$agent = nlClean($in['agent_reference'] ?? '', 150);
$categoryId = (int)($in['category_id'] ?? 0);
$date = nlDate($in['job_date'] ?? '') ?? date('Y-m-d');
$paidTo = nlClean($in['paid_to'] ?? '', 150);
$pmId = (int)($in['payment_method_id'] ?? 0) ?: null;
$paymentRef = nlClean($in['payment_ref'] ?? '', 100);
$remarks = nlClean($in['remarks'] ?? '', 255);
if ($name === '') jsonResponse(false, 'Candidate name is required');

$cat = practicalCategoryFind($conn, $categoryId);
if ((int)$cat['is_active'] !== 1) jsonResponse(false, 'Job category "' . $cat['name'] . '" is inactive');
$price = (float)$cat['fee'];
$paid = isset($in['paid']) && $in['paid'] !== '' ? round((float)$in['paid'], 2) : $price;
if ($paid < 0) jsonResponse(false, 'Paid amount cannot be negative');
if ($paid > $price + 0.001) jsonResponse(false, 'Paid amount cannot be more than the price (' . number_format($price, 2) . ')');
if ($paidTo === '') $paidTo = (string)$user['name'];

$conn->begin_transaction();
try {
    [$serial, $tokenNo] = practicalNextOutToken($conn, $date);

    $voucherId = null;
    // Only when the branch has an asset account, so the income head is not created for nothing
    if ($paid > 0 && nlSetting('auto_post_out_income') === '1' && nlAccAssetForPaymentMethod($conn, $locationId, $pmId)) {
        $voucherId = nlAccPostIncome($conn, $locationId, $date, $paid,
            'Practical test fee - ' . $name . ' (' . $tokenNo . ', ' . $cat['name'] . ')', $pmId, $paymentRef,
            (int)$user['user_id'], $paidTo, nlAccIncomeCategory($conn, $locationId, 'PTF', 'Practical Test Fee'));
    }

    $uid = (int)$user['user_id'];
    $catName = $cat['name'];
    $stmt = $conn->prepare("INSERT INTO out_jobs (location_id, token_no, job_date, daily_serial, candidate_name, mobile, passport_number, agent_reference,
                                                  category_id, category_name, price, paid, paid_to, payment_method_id, payment_ref, voucher_id, remarks, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ississssisddsisisi', $locationId, $tokenNo, $date, $serial, $name, $mobile, $passport, $agent,
        $categoryId, $catName, $price, $paid, $paidTo, $pmId, $paymentRef, $voucherId, $remarks, $uid);
    $stmt->execute();
    $id = (int)$conn->insert_id;
    $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}

jsonResponse(true, 'Out-student job ' . $tokenNo . ' saved', ['job' => practicalOutFind($conn, $id)]);
