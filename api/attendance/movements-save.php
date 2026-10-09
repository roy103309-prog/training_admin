<?php
// payload: { id?, user_id, from_date, to_date, in_time, out_time, auto_present, place, purpose }
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getTenantConnection();

$in      = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id      = (int)($in['id'] ?? 0);
$userId  = (int)($in['user_id'] ?? 0);
$from    = sanitize($in['from_date'] ?? '');
$to      = sanitize($in['to_date'] ?? '');
$inTime  = trim($in['in_time'] ?? '');
$outTime = trim($in['out_time'] ?? '');
$auto    = (int)($in['auto_present'] ?? 1);
$place   = sanitize($in['place'] ?? '');
$purpose = sanitize($in['purpose'] ?? '');
if ($userId <= 0 || $from === '' || $to === '') jsonResponse(false, 'Employee and dates required');
if ($to < $from) jsonResponse(false, 'To date must be ≥ From date');

$inVal  = $inTime  !== '' ? $inTime  : null;
$outVal = $outTime !== '' ? $outTime : null;

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE att_movements SET user_id = ?, from_date = ?, to_date = ?, in_time = ?, out_time = ?, auto_present = ?, place = ?, purpose = ? WHERE id = ? AND location_id = ?");
    $stmt->bind_param('issssissii', $userId, $from, $to, $inVal, $outVal, $auto, $place, $purpose, $id, $locationId);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Updated');
}
$stmt = $conn->prepare("INSERT INTO att_movements (location_id, user_id, from_date, to_date, in_time, out_time, auto_present, place, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iissssiss', $locationId, $userId, $from, $to, $inVal, $outVal, $auto, $place, $purpose);
if (!$stmt->execute()) { $err = $stmt->error; $stmt->close(); jsonResponse(false, 'Failed: ' . $err); }
$nid = $conn->insert_id; $stmt->close();
jsonResponse(true, 'Created', ['id' => $nid]);
