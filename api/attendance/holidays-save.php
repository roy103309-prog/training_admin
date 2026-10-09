<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id       = (int)($in['id'] ?? 0);
$typeId   = (int)($in['holiday_type_id'] ?? 0);
$date     = sanitize($in['holiday_date'] ?? '');
$reason   = sanitize($in['reason'] ?? '');
$isactive = (int)($in['isactive'] ?? 1);
if ($date === '') jsonResponse(false, 'Date required');
$typeVal = $typeId > 0 ? $typeId : null;

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE att_holidays SET holiday_type_id = ?, holiday_date = ?, reason = ?, isactive = ? WHERE id = ? AND location_id = ?");
    $stmt->bind_param('issiii', $typeVal, $date, $reason, $isactive, $id, $locationId);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Updated');
}
$stmt = $conn->prepare("INSERT INTO att_holidays (location_id, holiday_type_id, holiday_date, reason, isactive) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('iissi', $locationId, $typeVal, $date, $reason, $isactive);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$nid = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Created', ['id' => $nid]);
