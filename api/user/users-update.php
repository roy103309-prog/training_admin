<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$name = sanitize($input['name'] ?? '');
$mobile = sanitize($input['mobile'] ?? '');
$password = $input['password'] ?? '';
$roleId = (int)($input['role_id'] ?? 0);
$locationId = (int)($input['location_id'] ?? 0);
$locationIds = is_array($input['location_ids'] ?? null) ? array_map('intval', $input['location_ids']) : null;
$issuperadmin = (int)($input['issuperadmin'] ?? 0);
$isactive = (int)($input['isactive'] ?? 1);

if ($id <= 0 || empty($name) || empty($mobile)) jsonResponse(false, 'Invalid data');

$conn = getTenantConnection();

// Check if mobile exists for other users
$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ? AND id != ?");
$stmt->bind_param('si', $mobile, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) jsonResponse(false, 'Mobile number already exists');
$stmt->close();

// Default primary location = first selected branch if not explicitly provided
if ($locationId <= 0 && !empty($locationIds)) $locationId = $locationIds[0];

$roleIdVal = $roleId > 0 ? $roleId : null;
$locationIdVal = $locationId > 0 ? $locationId : null;

$conn->begin_transaction();
try {
    if (!empty($password)) {
        $passwordHash = $password; // plain text
        $stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ?, password_hash = ?, role_id = ?, location_id = ?, issuperadmin = ?, isactive = ? WHERE id = ?");
        $stmt->bind_param('sssiiiii', $name, $mobile, $passwordHash, $roleIdVal, $locationIdVal, $issuperadmin, $isactive, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ?, role_id = ?, location_id = ?, issuperadmin = ?, isactive = ? WHERE id = ?");
        $stmt->bind_param('ssiiiii', $name, $mobile, $roleIdVal, $locationIdVal, $issuperadmin, $isactive, $id);
    }
    $stmt->execute();
    $stmt->close();

    // Sync branch assignments if the client explicitly sent location_ids
    if ($locationIds !== null) {
        $del = $conn->prepare("DELETE FROM user_locations WHERE user_id = ?");
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
        if (!empty($locationIds)) {
            $ins = $conn->prepare("INSERT IGNORE INTO user_locations (user_id, location_id) VALUES (?, ?)");
            foreach ($locationIds as $lid) {
                if ($lid <= 0) continue;
                $ins->bind_param('ii', $id, $lid);
                $ins->execute();
            }
            $ins->close();
        }
    }

    $conn->commit();
    jsonResponse(true, 'User updated successfully');
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed to update user: ' . $e->getMessage());
}
