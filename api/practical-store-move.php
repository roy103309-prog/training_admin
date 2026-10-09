<?php
require_once __DIR__ . '/practical-common.php';
/**
 * Product IN / OUT.
 *   { movement: 'in'|'out', product_id?, code?, name?, unit?, quantity, txn_date, reference, remarks }
 * IN without product_id creates the product (or uses the one with the same code / name).
 * OUT needs an existing product and enough stock.
 */
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

$in = nlInput();
$movement = (string)($in['movement'] ?? '');
if (!isset(PRACTICAL_STORE_MOVEMENTS[$movement])) jsonResponse(false, 'Choose Product IN or Product OUT');
$productId = (int)($in['product_id'] ?? 0);
$code = nlClean($in['code'] ?? '', 40);
$name = nlClean($in['name'] ?? '', 190);
$unit = nlClean($in['unit'] ?? '', 20) ?: 'pcs';
$qty = round((float)($in['quantity'] ?? 0), 2);
$date = nlDate($in['txn_date'] ?? '') ?? date('Y-m-d');
$reference = nlClean($in['reference'] ?? '', 100);
$remarks = nlClean($in['remarks'] ?? '', 500);
if ($qty <= 0) jsonResponse(false, 'Enter a quantity greater than zero');

$conn->begin_transaction();
try {
    if ($productId <= 0) {
        // Typed product: find it by code, else by name
        if ($code !== '') {
            $stmt = $conn->prepare("SELECT id FROM store_products WHERE code = ?");
            $stmt->bind_param('s', $code);
        } else {
            if ($name === '') throw new NlUserError('Choose a product or type its description');
            $stmt = $conn->prepare("SELECT id FROM store_products WHERE name = ? AND code IS NULL");
            $stmt->bind_param('s', $name);
        }
        $stmt->execute();
        $productId = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0);
        $stmt->close();

        if ($productId <= 0) {
            if ($movement === 'out') throw new NlUserError('This product is not in the store yet. Use Product IN first.');
            if ($name === '') throw new NlUserError('Product description is required for a new product');
            $codeOrNull = $code !== '' ? $code : null;
            $stmt = $conn->prepare("INSERT INTO store_products (code, name, unit) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $codeOrNull, $name, $unit);
            $stmt->execute();
            $productId = (int)$conn->insert_id;
            $stmt->close();
        }
    }

    $product = practicalProductLock($conn, $productId);
    $stock = practicalStockChange($conn, $product, $movement === 'in' ? $qty : -$qty);

    $uid = (int)$user['user_id'];
    $stmt = $conn->prepare("INSERT INTO store_ledger (location_id, product_id, txn_date, movement, quantity, reference, remarks, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iissdssi', $locationId, $productId, $date, $movement, $qty, $reference, $remarks, $uid);
    $stmt->execute();
    $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}

jsonResponse(true, 'Product ' . PRACTICAL_STORE_MOVEMENTS[$movement] . ' saved: ' . $product['name'] . ' — stock now ' . (float)$stock . ' ' . $product['unit']);
