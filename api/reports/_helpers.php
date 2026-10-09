<?php
/**
 * Reports module — shared helpers
 */

/**
 * Resolve and validate the requested date range. Returns [from, to] strings.
 * If $required is false and either date is missing, defaults to 1900..2999.
 */
function rep_dateRange($required = true) {
    $from = trim($_GET['date_from'] ?? '');
    $to   = trim($_GET['date_to']   ?? '');
    if ($required && ($from === '' || $to === '')) {
        jsonResponse(false, 'Date range is required');
    }
    if ($from === '') $from = '1900-01-01';
    if ($to   === '') $to   = '2999-12-31';
    return [$from, $to];
}

/**
 * Wrap a date in BETWEEN bounds (00:00:00 and 23:59:59).
 */
function rep_dayStart($d) { return $d . ' 00:00:00'; }
function rep_dayEnd($d)   { return $d . ' 23:59:59'; }

/**
 * Detect whether the current tenant DB has the v2 polymorphic ledger
 * (i.e. inv_stock_ledger.restaurant_item_id exists).
 */
function rep_ledgerHasItemColumn(mysqli $conn) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $r = $conn->query("SELECT COUNT(*) AS c FROM information_schema.columns
                       WHERE table_schema = DATABASE()
                         AND table_name = 'inv_stock_ledger'
                         AND column_name = 'restaurant_item_id'");
    $row = $r ? $r->fetch_assoc() : null;
    return $cache = ($row && (int)$row['c'] > 0);
}

function rep_purchaseHasItemColumn(mysqli $conn) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $r = $conn->query("SELECT COUNT(*) AS c FROM information_schema.columns
                       WHERE table_schema = DATABASE()
                         AND table_name = 'inv_purchase_items'
                         AND column_name = 'restaurant_item_id'");
    $row = $r ? $r->fetch_assoc() : null;
    return $cache = ($row && (int)$row['c'] > 0);
}

function rep_materialHasUnitId(mysqli $conn) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $r = $conn->query("SELECT COUNT(*) AS c FROM information_schema.columns
                       WHERE table_schema = DATABASE()
                         AND table_name = 'inv_materials'
                         AND column_name = 'unit_id'");
    $row = $r ? $r->fetch_assoc() : null;
    return $cache = ($row && (int)$row['c'] > 0);
}
