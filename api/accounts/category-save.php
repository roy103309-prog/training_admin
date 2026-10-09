<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$kind = $in['kind'] ?? '';
$tables = [
    'liability' => 'acc_liability_categories',
    'income'    => 'acc_income_categories',
    'expense'   => 'acc_expense_categories'
];
if (!isset($tables[$kind])) jsonResponse(false, 'Invalid kind');
$table = $tables[$kind];
$hasOpening = $kind !== 'expense';

$id       = (int)($in['id'] ?? 0);
$name     = sanitize($in['name'] ?? '');
$code     = sanitize($in['code'] ?? '');
$opening  = (float)($in['opening_balance'] ?? 0);
$isactive = (int)($in['isactive'] ?? 1);
if ($name === '') jsonResponse(false, 'Name required');

if ($id > 0) {
    if ($hasOpening) {
        $stmt = $conn->prepare("UPDATE $table SET name = ?, code = ?, opening_balance = ?, isactive = ? WHERE id = ? AND location_id = ?");
        $stmt->bind_param('ssdiii', $name, $code, $opening, $isactive, $id, $locationId);
    } else {
        $stmt = $conn->prepare("UPDATE $table SET name = ?, code = ?, isactive = ? WHERE id = ? AND location_id = ?");
        $stmt->bind_param('ssiii', $name, $code, $isactive, $id, $locationId);
    }
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Updated');
}

if ($hasOpening) {
    $stmt = $conn->prepare("INSERT INTO $table (location_id, name, code, opening_balance, isactive) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issdi', $locationId, $name, $code, $opening, $isactive);
} else {
    $stmt = $conn->prepare("INSERT INTO $table (location_id, name, code, isactive) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('issi', $locationId, $name, $code, $isactive);
}
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$nid = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Created', ['id' => $nid]);
