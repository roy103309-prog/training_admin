<?php
require_once __DIR__ . '/practical-common.php';
// Stock transaction ledger (IN / OUT), optional From–To dates, product and movement
$user = requireAuth();
$conn = getNewLifeConnection();

$from = nlDate($_GET['from'] ?? '');
$to = nlDate($_GET['to'] ?? '');
$productId = (int)($_GET['product_id'] ?? 0);
$movement = (string)($_GET['movement'] ?? '');

$where = []; $params = []; $types = '';
if ($from) { $where[] = 'l.txn_date >= ?'; $params[] = $from; $types .= 's'; }
if ($to) { $where[] = 'l.txn_date <= ?'; $params[] = $to; $types .= 's'; }
if ($productId > 0) { $where[] = 'l.product_id = ?'; $params[] = $productId; $types .= 'i'; }
if (isset(PRACTICAL_STORE_MOVEMENTS[$movement])) { $where[] = 'l.movement = ?'; $params[] = $movement; $types .= 's'; }

$stmt = $conn->prepare("SELECT l.*, p.code, p.name AS product_name, p.unit, u.name AS created_by_name
                        FROM store_ledger l
                        JOIN store_products p ON p.id = l.product_id
                        LEFT JOIN " . nlCoreTable('users') . " u ON u.id = l.created_by"
                     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY l.txn_date DESC, l.id DESC LIMIT 2000");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

jsonResponse(true, 'OK', ['items' => $items]);
