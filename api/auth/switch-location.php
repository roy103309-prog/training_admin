<?php
require_once '../tenant-config.php';

header('Content-Type: application/json');

$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$locationId = (int)($input['location_id'] ?? 0);

if ($locationId <= 0) {
    jsonResponse(false, 'Invalid location ID');
}

$conn = getTenantConnection();

// Check if location exists and is active
$stmt = $conn->prepare("SELECT id, name FROM locations WHERE id = ? AND isactive = 1");
$stmt->bind_param('i', $locationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $result->free();
    $stmt->close();
    jsonResponse(false, 'Location not found or inactive');
}

$location = $result->fetch_assoc();
$result->free();
$stmt->close();

// If not superadmin, verify access via user_locations pivot (fallback to legacy single location_id)
if (!$user['issuperadmin']) {
    $accessStmt = $conn->prepare("SELECT 1 FROM user_locations WHERE user_id = ? AND location_id = ? LIMIT 1");
    $accessStmt->bind_param('ii', $user['user_id'], $locationId);
    $accessStmt->execute();
    $hasAccess = $accessStmt->get_result()->fetch_assoc() ? true : false;
    $accessStmt->close();
    if (!$hasAccess && $user['location_id'] != $locationId) {
        jsonResponse(false, 'You do not have access to this branch');
    }

    // Role-level branch restriction
    if (!empty($user['role_id'])) {
        $roleLocIds = getRoleLocationIds($conn, (int)$user['role_id']);
        if (is_array($roleLocIds) && !in_array($locationId, $roleLocIds, true)) {
            jsonResponse(false, 'Your role does not have access to this branch');
        }
    }
}

// Set current location
setCurrentLocation($locationId);

jsonResponse(true, 'Location switched successfully', [
    'current_location' => $location
]);
