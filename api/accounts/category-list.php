<?php
// Generic list endpoint for liability/income/expense categories. ?kind=liability|income|expense
require_once __DIR__ . '/_bootstrap.php';
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$kind = $_GET['kind'] ?? '';
$tables = [
    'liability' => 'acc_liability_categories',
    'income'    => 'acc_income_categories',
    'expense'   => 'acc_expense_categories'
];
if (!isset($tables[$kind])) jsonResponse(false, 'Invalid kind');
$table = $tables[$kind];

$stmt = $conn->prepare("SELECT * FROM $table WHERE location_id = ? ORDER BY id DESC");
$stmt->bind_param('i', $locationId); $stmt->execute();
$res = $stmt->get_result(); $items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();
jsonResponse(true, 'OK', ['items' => $items]);
