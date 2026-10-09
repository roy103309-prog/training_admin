<?php
require_once __DIR__ . '/../_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to']   ?? '');
if ($to === '')   $to   = date('Y-m-d');
if ($from === '') $from = date('Y-m-01');
$openCutoff = date('Y-m-d', strtotime($from . ' -1 day'));

/**
 * Aggregate movements for $kind.
 * If $startDate null → cumulative up to $endDate (used for opening).
 * Else → between $startDate and $endDate inclusive.
 */
function tb_movements(mysqli $conn, $locationId, $startDate, $endDate, $kind) {
    $out = [];
    $hasStart = $startDate !== null;

    if ($kind === 'income') {
        $sql = "SELECT i.category_id, SUM(i.amount) AS amt
                FROM acc_voucher_items i
                JOIN acc_vouchers v ON v.id = i.voucher_id
                WHERE v.location_id = ? AND v.type = 'income' AND i.category_kind = 'income'
                  " . ($hasStart ? "AND v.voucher_date >= ? " : "") . "
                  AND v.voucher_date <= ?
                GROUP BY i.category_id";
        $stmt = $conn->prepare($sql);
        if ($hasStart) $stmt->bind_param('iss', $locationId, $startDate, $endDate);
        else           $stmt->bind_param('is',  $locationId, $endDate);
        $stmt->execute(); $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['category_id']] = ['in' => (float)$x['amt'], 'out' => 0];
        $stmt->close();
    } elseif ($kind === 'expense') {
        $sql = "SELECT i.category_id, SUM(i.amount) AS amt
                FROM acc_voucher_items i
                JOIN acc_vouchers v ON v.id = i.voucher_id
                WHERE v.location_id = ? AND v.type = 'expense' AND i.category_kind = 'expense'
                  " . ($hasStart ? "AND v.voucher_date >= ? " : "") . "
                  AND v.voucher_date <= ?
                GROUP BY i.category_id";
        $stmt = $conn->prepare($sql);
        if ($hasStart) $stmt->bind_param('iss', $locationId, $startDate, $endDate);
        else           $stmt->bind_param('is',  $locationId, $endDate);
        $stmt->execute(); $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['category_id']] = ['in' => (float)$x['amt'], 'out' => 0];
        $stmt->close();
    } elseif ($kind === 'liability') {
        $sql = "SELECT i.category_id,
                       SUM(CASE WHEN v.transaction_type = 'receive' THEN i.amount ELSE 0 END) AS in_amt,
                       SUM(CASE WHEN v.transaction_type = 'pay'     THEN i.amount ELSE 0 END) AS out_amt
                FROM acc_voucher_items i
                JOIN acc_vouchers v ON v.id = i.voucher_id
                WHERE v.location_id = ? AND v.type = 'liability' AND i.category_kind = 'liability'
                  " . ($hasStart ? "AND v.voucher_date >= ? " : "") . "
                  AND v.voucher_date <= ?
                GROUP BY i.category_id";
        $stmt = $conn->prepare($sql);
        if ($hasStart) $stmt->bind_param('iss', $locationId, $startDate, $endDate);
        else           $stmt->bind_param('is',  $locationId, $endDate);
        $stmt->execute(); $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['category_id']] = ['in' => (float)$x['in_amt'], 'out' => (float)$x['out_amt']];
        $stmt->close();
    } elseif ($kind === 'asset') {
        $dateClause = $hasStart ? "AND voucher_date BETWEEN ? AND ?" : "AND voucher_date <= ?";
        $bindMov    = $hasStart ? 'ss' : 's';

        $sql = "
            SELECT a.id AS cat_id,
                   COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'income'   AND location_id = ? $dateClause), 0)
                 + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'liability' AND transaction_type = 'receive' AND location_id = ? $dateClause), 0)
                 + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE to_asset_id = a.id AND type = 'transfer' AND location_id = ? $dateClause), 0) AS in_amt,
                   COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'expense' AND location_id = ? $dateClause), 0)
                 + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE asset_category_id = a.id AND type = 'liability' AND transaction_type = 'pay' AND location_id = ? $dateClause), 0)
                 + COALESCE((SELECT SUM(total) FROM acc_vouchers WHERE from_asset_id = a.id AND type = 'transfer' AND location_id = ? $dateClause), 0) AS out_amt
            FROM acc_asset_categories a
            WHERE a.location_id = ?";

        $params = [];
        for ($i = 0; $i < 6; $i++) {
            $params[] = $locationId;
            if ($hasStart) { $params[] = $startDate; $params[] = $endDate; }
            else           { $params[] = $endDate; }
        }
        $params[] = $locationId;
        $types = str_repeat('i' . $bindMov, 6) . 'i';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute(); $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['cat_id']] = ['in' => (float)$x['in_amt'], 'out' => (float)$x['out_amt']];
        $stmt->close();
    }
    return $out;
}

function tb_section(mysqli $conn, $locationId, $from, $to, $openCutoff, $kind) {
    $tableMap = [
        'asset'     => ['table' => 'acc_asset_categories',     'has_opening' => true],
        'income'    => ['table' => 'acc_income_categories',    'has_opening' => true],
        'expense'   => ['table' => 'acc_expense_categories',   'has_opening' => false],
        'liability' => ['table' => 'acc_liability_categories', 'has_opening' => true]
    ];
    $cfg = $tableMap[$kind];
    $cols = $cfg['has_opening'] ? 'id, name, opening_balance' : 'id, name, 0 AS opening_balance';
    $stmt = $conn->prepare("SELECT $cols FROM " . $cfg['table'] . " WHERE location_id = ? AND isactive = 1 ORDER BY id");
    $stmt->bind_param('i', $locationId); $stmt->execute();
    $r = $stmt->get_result();
    $cats = []; while ($x = $r->fetch_assoc()) $cats[] = $x;
    $stmt->close();

    $openMov   = tb_movements($conn, $locationId, null, $openCutoff, $kind);
    $periodMov = tb_movements($conn, $locationId, $from, $to, $kind);

    $rows = []; $sumOpen = 0; $sumIn = 0; $sumOut = 0; $sumClose = 0;
    foreach ($cats as $c) {
        $id     = (int)$c['id'];
        $name   = $c['name'];
        $seed   = (float)$c['opening_balance'];

        $priorIn  = isset($openMov[$id]) ? $openMov[$id]['in']  : 0;
        $priorOut = isset($openMov[$id]) ? $openMov[$id]['out'] : 0;
        $opening  = $cfg['has_opening'] ? ($seed + $priorIn - $priorOut) : null;

        $in  = isset($periodMov[$id]) ? $periodMov[$id]['in']  : 0;
        $out = isset($periodMov[$id]) ? $periodMov[$id]['out'] : 0;

        $closing = $cfg['has_opening'] ? ($opening + $in - $out) : null;
        $rows[] = ['name' => $name, 'opening' => $opening, 'in' => $in, 'out' => $out, 'closing' => $closing];
        if ($cfg['has_opening']) { $sumOpen += $opening; $sumClose += $closing; }
        $sumIn += $in; $sumOut += $out;
    }
    return [
        'rows'  => $rows,
        'total' => [
            'opening' => $cfg['has_opening'] ? $sumOpen : null,
            'in'      => $sumIn,
            'out'     => $sumOut,
            'closing' => $cfg['has_opening'] ? $sumClose : null
        ],
        'has_opening' => $cfg['has_opening']
    ];
}

$sections = [
    'assets'      => tb_section($conn, $locationId, $from, $to, $openCutoff, 'asset'),
    'income'      => tb_section($conn, $locationId, $from, $to, $openCutoff, 'income'),
    'expense'     => tb_section($conn, $locationId, $from, $to, $openCutoff, 'expense'),
    'liabilities' => tb_section($conn, $locationId, $from, $to, $openCutoff, 'liability')
];

jsonResponse(true, 'OK', ['from' => $from, 'to' => $to, 'sections' => $sections]);
