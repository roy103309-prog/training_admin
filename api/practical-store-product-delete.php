<?php
require_once __DIR__ . '/practical-common.php';
// A product can be deleted only while it has no ledger entries
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM store_ledger WHERE product_id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$used = (int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
if ($used > 0) jsonResponse(false, "This product has $used ledger entries. Delete those entries first.");

$stmt = $conn->prepare("DELETE FROM store_products WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$deleted = $stmt->affected_rows; $stmt->close();
if (!$deleted) jsonResponse(false, 'Product not found');
jsonResponse(true, 'Product deleted');
