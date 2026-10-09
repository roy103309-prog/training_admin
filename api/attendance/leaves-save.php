<?php
// payload: { id?, user_id, leave_type_id, from_date, to_date, reason, status }
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in     = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id     = (int)($in['id'] ?? 0);
$userId = (int)($in['user_id'] ?? 0);
$typeId = (int)($in['leave_type_id'] ?? 0);
$from   = sanitize($in['from_date'] ?? '');
$to     = sanitize($in['to_date'] ?? '');
$reason = sanitize($in['reason'] ?? '');
$status = in_array($in['status'] ?? '', ['pending','approved','rejected'], true) ? $in['status'] : 'pending';
if ($userId <= 0 || $from === '' || $to === '') jsonResponse(false, 'Employee and dates required');
if ($to < $from) jsonResponse(false, 'To date must be ≥ From date');

$days = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
$typeVal = $typeId > 0 ? $typeId : null;

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE att_leaves SET user_id = ?, leave_type_id = ?, from_date = ?, to_date = ?, days = ?, reason = ?, status = ? WHERE id = ? AND location_id = ?");
    $stmt->bind_param('iississii', $userId, $typeVal, $from, $to, $days, $reason, $status, $id, $locationId);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Updated');
}
$stmt = $conn->prepare("INSERT INTO att_leaves (location_id, user_id, leave_type_id, from_date, to_date, days, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iiississ', $locationId, $userId, $typeVal, $from, $to, $days, $reason, $status);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$nid = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Created', ['id' => $nid]);
