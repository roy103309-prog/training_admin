<?php
require_once __DIR__ . '/practical-common.php';
// Deleting a ledger entry reverses its stock change (an IN cannot be removed once that stock is issued)
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT * FROM store_ledger WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row) throw new NlUserError('Entry not found');

    $product = practicalProductLock($conn, (int)$row['product_id']);
    if ($row['movement'] === 'in' && (float)$product['current_stock'] + 0.001 < (float)$row['quantity']) {
        throw new NlUserError('This IN cannot be deleted: only ' . (float)$product['current_stock'] . ' ' . $product['unit']
            . ' is left in stock, the rest was already issued. Delete the later OUT entries first.');
    }
    practicalStockChange($conn, $product, $row['movement'] === 'in' ? -(float)$row['quantity'] : (float)$row['quantity']);

    $stmt = $conn->prepare("DELETE FROM store_ledger WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
jsonResponse(true, 'Entry deleted and stock corrected');
