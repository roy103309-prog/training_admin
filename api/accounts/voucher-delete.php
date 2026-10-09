<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($in['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'ID required');
$conn = getTenantConnection();
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("DELETE FROM acc_voucher_items WHERE voucher_id IN (SELECT id FROM acc_vouchers WHERE id = ? AND location_id = ?)");
    $stmt->bind_param('ii', $id, $locationId); $stmt->execute(); $stmt->close();
    $stmt = $conn->prepare("DELETE FROM acc_vouchers WHERE id = ? AND location_id = ?");
    $stmt->bind_param('ii', $id, $locationId); $stmt->execute(); $stmt->close();
    $conn->commit();
    jsonResponse(true, 'Deleted');
} catch (Throwable $e) { $conn->rollback(); jsonResponse(false, 'Failed: ' . $e->getMessage()); }
