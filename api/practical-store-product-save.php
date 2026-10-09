<?php
require_once __DIR__ . '/practical-common.php';
// Edit a product's code, name and unit (stock changes only through Product IN / OUT)
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$code = nlClean($in['code'] ?? '', 40);
$name = nlClean($in['name'] ?? '', 190);
$unit = nlClean($in['unit'] ?? '', 20) ?: 'pcs';
if ($name === '') jsonResponse(false, 'Product description is required');

$codeOrNull = $code !== '' ? $code : null;
$stmt = $conn->prepare("UPDATE store_products SET code = ?, name = ?, unit = ? WHERE id = ?");
$stmt->bind_param('sssi', $codeOrNull, $name, $unit, $id);
$stmt->execute();
$stmt->close();
jsonResponse(true, 'Product updated');
