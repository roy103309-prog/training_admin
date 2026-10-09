<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id        = (int)($in['id'] ?? 0);
$type      = in_array($in['type'] ?? '', ['asset','fixed_asset','receivable','payable'], true) ? $in['type'] : 'asset';
$name      = sanitize($in['name'] ?? '');
$opening   = (float)($in['opening_balance'] ?? 0);
$openDate  = sanitize($in['opening_date'] ?? '');
$remark    = sanitize($in['remark'] ?? '');
$isactive  = (int)($in['isactive'] ?? 1);
if ($name === '') jsonResponse(false, 'Name required');
$od = $openDate !== '' ? $openDate : null;

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE acc_asset_categories SET type = ?, name = ?, opening_balance = ?, opening_date = ?, remark = ?, isactive = ? WHERE id = ? AND location_id = ?");
    $stmt->bind_param('ssdssiii', $type, $name, $opening, $od, $remark, $isactive, $id, $locationId);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Updated');
}
$stmt = $conn->prepare("INSERT INTO acc_asset_categories (location_id, type, name, opening_balance, opening_date, remark, isactive) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('issdssi', $locationId, $type, $name, $opening, $od, $remark, $isactive);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$nid = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Created', ['id' => $nid]);
