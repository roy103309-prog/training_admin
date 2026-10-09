<?php
require_once __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();
$locationId = getCurrentLocation();
if (!$locationId) jsonResponse(false, 'No location selected');

$conn = getTenantConnection();

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id        = (int)($in['id'] ?? 0);
$name      = sanitize($in['name']       ?? '');
$shortName = sanitize($in['short_name'] ?? '');
$isactive  = (int)($in['isactive'] ?? 1);

if ($id <= 0 || $name === '') jsonResponse(false, 'ID and name required');

$stmt = $conn->prepare("UPDATE grades SET name = ?, short_name = ?, isactive = ? WHERE id = ? AND location_id = ?");
$stmt->bind_param('ssiii', $name, $shortName, $isactive, $id, $locationId);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Grade updated');
