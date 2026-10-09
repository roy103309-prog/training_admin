<?php
require_once __DIR__ . '/../tenant-config.php';

/**
 * Basic Accounts tables (acc_asset_categories, acc_liability_categories,
 * acc_income_categories, acc_expense_categories, acc_vouchers,
 * acc_voucher_items) are provisioned at registration via
 * database/tenant_schema.sql. No runtime migration here.
 */

function accGenVoucherNo(mysqli $conn, $locationId, $prefix) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM acc_vouchers WHERE location_id = ?");
    $stmt->bind_param('i', $locationId); $stmt->execute();
    $c = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
    return $prefix . '-' . date('Ymd') . '-' . str_pad((string)($c + 1), 5, '0', STR_PAD_LEFT);
}
