<?php
/**
 * Posting New Life money (student fees) into the TrainingAdmin Basic Accounts.
 * Vouchers are written to acc_vouchers / acc_voucher_items on the same
 * connection as the New Life tables, so the payment and its voucher are
 * saved in one transaction.
 */
require_once __DIR__ . '/../newlife-common.php';

// Voucher number that stays unique even after deletes (same format as Basic Accounts: INC-YYYYMMDD-00001)
function nlAccNextVoucherNo(mysqli $conn, $locationId, $prefix, $date) {
    $vouchers = nlCoreTable('acc_vouchers');
    $day = date('Ymd', strtotime($date) ?: time());
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM $vouchers WHERE location_id = ?");
    $stmt->bind_param('i', $locationId); $stmt->execute();
    $n = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
    $chk = $conn->prepare("SELECT 1 FROM $vouchers WHERE location_id = ? AND voucher_no = ?");
    do {
        $n++;
        $no = $prefix . '-' . $day . '-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
        $chk->bind_param('is', $locationId, $no); $chk->execute();
        $taken = (bool)$chk->get_result()->fetch_row();
    } while ($taken);
    $chk->close();
    return $no;
}

// Cash payments go to the "cash" asset, others to the first non-cash asset
function nlAccAssetForPaymentMethod(mysqli $conn, $locationId, $paymentMethodId) {
    $assets = nlCoreTable('acc_asset_categories');
    $isCash = true;
    if ($paymentMethodId) {
        $stmt = $conn->prepare("SELECT name FROM " . nlCoreTable('payment_methods') . " WHERE id = ?");
        $stmt->bind_param('i', $paymentMethodId); $stmt->execute();
        $pm = $stmt->get_result()->fetch_assoc(); $stmt->close();
        $isCash = !$pm || stripos($pm['name'], 'cash') !== false;
    }
    $stmt = $conn->prepare("SELECT id FROM $assets WHERE location_id = ? AND isactive = 1 AND type = 'asset' AND name "
                         . ($isCash ? "LIKE '%cash%'" : "NOT LIKE '%cash%'") . " ORDER BY id LIMIT 1");
    $stmt->bind_param('i', $locationId); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row) {
        $stmt = $conn->prepare("SELECT id FROM $assets WHERE location_id = ? AND isactive = 1 AND type = 'asset' ORDER BY id LIMIT 1");
        $stmt->bind_param('i', $locationId); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    }
    return $row ? (int)$row['id'] : null;
}

// Income head of the branch by code / name; created the first time it is needed
function nlAccIncomeCategory(mysqli $conn, $locationId, $code, $name) {
    $cats = nlCoreTable('acc_income_categories');
    $stmt = $conn->prepare("SELECT id FROM $cats WHERE location_id = ? AND (code = ? OR name = ?) ORDER BY isactive DESC, id LIMIT 1");
    $stmt->bind_param('iss', $locationId, $code, $name); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($row) return (int)$row['id'];
    $stmt = $conn->prepare("INSERT INTO $cats (location_id, name, code, isactive) VALUES (?, ?, ?, 1)");
    $stmt->bind_param('iss', $locationId, $name, $code); $stmt->execute();
    $id = (int)$conn->insert_id; $stmt->close();
    return $id;
}

// Income head "Course Fee" (code FEE)
function nlAccCourseFeeCategory(mysqli $conn, $locationId) {
    return nlAccIncomeCategory($conn, $locationId, 'FEE', 'Course Fee');
}

/**
 * One-line income voucher. Returns the voucher id, or null when the branch
 * has no asset account yet (the payment itself is still saved).
 * $categoryId = income head (default "Course Fee").
 */
function nlAccPostIncome(mysqli $conn, $locationId, $date, $amount, $description, $paymentMethodId, $note, $createdBy, $collectedBy, $categoryId = null) {
    if ($amount <= 0) return null;
    $assetId = nlAccAssetForPaymentMethod($conn, $locationId, $paymentMethodId);
    if (!$assetId) return null;
    $categoryId = $categoryId ?: nlAccCourseFeeCategory($conn, $locationId);
    $voucherNo = nlAccNextVoucherNo($conn, $locationId, 'INC', $date);
    $vDate = substr((string)$date, 0, 10);
    $pm = $paymentMethodId ?: null;

    $stmt = $conn->prepare("INSERT INTO " . nlCoreTable('acc_vouchers') . "
        (location_id, voucher_no, type, voucher_date, asset_category_id, collected_by, total, note, created_by)
        VALUES (?, ?, 'income', ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('issisdsi', $locationId, $voucherNo, $vDate, $assetId, $collectedBy, $amount, $note, $createdBy);
    $stmt->execute();
    $vid = (int)$conn->insert_id; $stmt->close();

    $stmt = $conn->prepare("INSERT INTO " . nlCoreTable('acc_voucher_items') . " (voucher_id, category_kind, category_id, description, payment_method_id, amount)
                            VALUES (?, 'income', ?, ?, ?, ?)");
    $stmt->bind_param('iisid', $vid, $categoryId, $description, $pm, $amount);
    $stmt->execute(); $stmt->close();
    return $vid;
}

function nlAccDeleteVoucher(mysqli $conn, $voucherId) {
    $voucherId = (int)$voucherId;
    if ($voucherId <= 0) return;
    $stmt = $conn->prepare("DELETE FROM " . nlCoreTable('acc_voucher_items') . " WHERE voucher_id = ?");
    $stmt->bind_param('i', $voucherId); $stmt->execute(); $stmt->close();
    $stmt = $conn->prepare("DELETE FROM " . nlCoreTable('acc_vouchers') . " WHERE id = ?");
    $stmt->bind_param('i', $voucherId); $stmt->execute(); $stmt->close();
}
