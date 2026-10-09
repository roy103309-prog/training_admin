<?php
require_once __DIR__ . '/../_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$to = trim($_GET['to'] ?? '');
if ($to === '') $to = date('Y-m-d');

/** Asset closing balance up to $to = opening_balance + cumulative in - cumulative out */
function bs_asset_closing(mysqli $conn, $locationId, $to) {
    $sql = "
        SELECT a.id, a.name, a.type, a.opening_balance,
               COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'income'    AND location_id = ? AND voucher_date <= ?), 0)
             + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'liability' AND transaction_type = 'receive' AND location_id = ? AND voucher_date <= ?), 0)
             + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE to_asset_id = a.id        AND type = 'transfer' AND location_id = ? AND voucher_date <= ?), 0) AS in_amt,
               COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'expense'   AND location_id = ? AND voucher_date <= ?), 0)
             + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'liability' AND transaction_type = 'pay' AND location_id = ? AND voucher_date <= ?), 0)
             + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE from_asset_id = a.id     AND type = 'transfer' AND location_id = ? AND voucher_date <= ?), 0) AS out_amt
        FROM acc_asset_categories a
        WHERE a.location_id = ? AND a.isactive = 1
        ORDER BY a.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isisisisisisi',
        $locationId,$to,$locationId,$to,$locationId,$to,
        $locationId,$to,$locationId,$to,$locationId,$to,
        $locationId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            'id'      => (int)$r['id'],
            'name'    => $r['name'],
            'type'    => $r['type'],
            'closing' => (float)$r['opening_balance'] + (float)$r['in_amt'] - (float)$r['out_amt']
        ];
    }
    $stmt->close();
    return $rows;
}

function bs_liability_closing(mysqli $conn, $locationId, $to) {
    $sql = "
        SELECT l.id, l.name, l.opening_balance,
               COALESCE((SELECT SUM(CASE WHEN v.transaction_type = 'receive' THEN i.amount ELSE 0 END)
                         FROM acc_voucher_items i JOIN acc_vouchers v ON v.id = i.voucher_id
                         WHERE i.category_kind = 'liability' AND i.category_id = l.id
                           AND v.location_id = ? AND v.voucher_date <= ?), 0) AS in_amt,
               COALESCE((SELECT SUM(CASE WHEN v.transaction_type = 'pay' THEN i.amount ELSE 0 END)
                         FROM acc_voucher_items i JOIN acc_vouchers v ON v.id = i.voucher_id
                         WHERE i.category_kind = 'liability' AND i.category_id = l.id
                           AND v.location_id = ? AND v.voucher_date <= ?), 0) AS out_amt
        FROM acc_liability_categories l
        WHERE l.location_id = ? AND l.isactive = 1
        ORDER BY l.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isisi', $locationId, $to, $locationId, $to, $locationId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = ['id' => (int)$r['id'], 'name' => $r['name'],
                   'closing' => (float)$r['opening_balance'] + (float)$r['in_amt'] - (float)$r['out_amt']];
    }
    $stmt->close();
    return $rows;
}

function bs_income_total(mysqli $conn, $locationId, $to) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(opening_balance),0) AS opening FROM acc_income_categories WHERE location_id = ? AND isactive = 1");
    $stmt->bind_param('i', $locationId); $stmt->execute();
    $opening = (float)$stmt->get_result()->fetch_assoc()['opening']; $stmt->close();
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(i.amount),0) AS amt
        FROM acc_voucher_items i JOIN acc_vouchers v ON v.id = i.voucher_id
        WHERE v.location_id = ? AND v.type = 'income' AND i.category_kind = 'income' AND v.voucher_date <= ?");
    $stmt->bind_param('is', $locationId, $to); $stmt->execute();
    $period = (float)$stmt->get_result()->fetch_assoc()['amt']; $stmt->close();
    return $opening + $period;
}

function bs_expense_total(mysqli $conn, $locationId, $to) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(i.amount),0) AS amt
        FROM acc_voucher_items i JOIN acc_vouchers v ON v.id = i.voucher_id
        WHERE v.location_id = ? AND v.type = 'expense' AND i.category_kind = 'expense' AND v.voucher_date <= ?");
    $stmt->bind_param('is', $locationId, $to); $stmt->execute();
    $period = (float)$stmt->get_result()->fetch_assoc()['amt']; $stmt->close();
    return $period;
}

$assetRows     = bs_asset_closing($conn, $locationId, $to);
$liabilityRows = bs_liability_closing($conn, $locationId, $to);

// Split assets
$currentAssets = []; $fixedAssets = []; $payables = [];
foreach ($assetRows as $r) {
    if ($r['type'] === 'fixed_asset') $fixedAssets[] = $r;
    elseif ($r['type'] === 'payable') $payables[] = $r;
    else $currentAssets[] = $r;  // asset, receivable
}

$totalCurrent   = array_sum(array_column($currentAssets, 'closing'));
$totalFixed     = array_sum(array_column($fixedAssets,   'closing'));
$totalAssets    = $totalCurrent + $totalFixed;

$totalLiabCats  = array_sum(array_column($liabilityRows, 'closing'));
$totalPayables  = array_sum(array_column($payables,      'closing'));
$totalLiabilities = $totalLiabCats + $totalPayables;

$incomeTotal    = bs_income_total($conn, $locationId, $to);
$expenseTotal   = bs_expense_total($conn, $locationId, $to);
$retained       = $incomeTotal - $expenseTotal;

$totalEquity    = $retained;
$totalLiabEquity = $totalLiabilities + $totalEquity;

jsonResponse(true, 'OK', [
    'to' => $to,
    'sections' => [
        'current_assets' => ['rows' => $currentAssets, 'total' => $totalCurrent],
        'fixed_assets'   => ['rows' => $fixedAssets,   'total' => $totalFixed],
        'payables'       => ['rows' => $payables,     'total' => $totalPayables],
        'liabilities'    => ['rows' => $liabilityRows, 'total' => $totalLiabCats],
        'equity' => [
            'rows' => [
                ['name' => 'Income (cumulative)',  'closing' => $incomeTotal],
                ['name' => 'Expense (cumulative)', 'closing' => -$expenseTotal],
            ],
            'total' => $retained
        ]
    ],
    'totals' => [
        'assets'      => $totalAssets,
        'liabilities' => $totalLiabilities,
        'equity'      => $totalEquity,
        'liab_equity' => $totalLiabEquity,
        'difference'  => $totalAssets - $totalLiabEquity
    ]
]);
