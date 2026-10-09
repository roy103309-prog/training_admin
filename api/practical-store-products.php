<?php
require_once __DIR__ . '/practical-common.php';
// Store inventory with the totals shown on the Store page
$user = requireAuth();
$conn = getNewLifeConnection();

$search = nlClean($_GET['search'] ?? '', 100);
$sql = "SELECT p.*, COUNT(l.id) AS txn_count
        FROM store_products p LEFT JOIN store_ledger l ON l.product_id = p.id";
$params = []; $types = '';
if ($search !== '') {
    $sql .= " WHERE (p.name LIKE ? OR p.code LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like); $types .= 'ss';
}
$stmt = $conn->prepare($sql . " GROUP BY p.id ORDER BY p.name");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$t = $conn->query("SELECT COALESCE(SUM(CASE WHEN movement = 'in' THEN quantity END), 0) AS qty_in,
                          COALESCE(SUM(CASE WHEN movement = 'out' THEN quantity END), 0) AS qty_out FROM store_ledger")->fetch_assoc();
$p = $conn->query("SELECT COUNT(*) AS c, COALESCE(SUM(current_stock), 0) AS stock FROM store_products")->fetch_assoc();

jsonResponse(true, 'OK', [
    'items' => $items,
    'summary' => ['products' => (int)$p['c'], 'qty_in' => (float)$t['qty_in'], 'qty_out' => (float)$t['qty_out'], 'stock' => (float)$p['stock']],
]);
