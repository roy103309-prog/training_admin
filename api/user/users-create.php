<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$name = sanitize($input['name'] ?? '');
$mobile = sanitize($input['mobile'] ?? '');
$password = $input['password'] ?? '';
$roleId = (int)($input['role_id'] ?? 0);
$locationId = (int)($input['location_id'] ?? 0);
$locationIds = is_array($input['location_ids'] ?? null) ? array_map('intval', $input['location_ids']) : [];
$issuperadmin = (int)($input['issuperadmin'] ?? 0);
$isactive = (int)($input['isactive'] ?? 1);

if (empty($name) || empty($mobile) || empty($password)) jsonResponse(false, 'Name, mobile, and password are required');

$conn = getTenantConnection();

// Check if mobile already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->bind_param('s', $mobile);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $result->free();
    $stmt->close();
    jsonResponse(false, 'Mobile number already exists');
}
$result->free();
$stmt->close();

// Default primary location = first selected branch (or provided location_id)
if ($locationId <= 0 && !empty($locationIds)) $locationId = $locationIds[0];

$passwordHash = hashPassword($password);
$roleIdVal = $roleId > 0 ? $roleId : null;
$locationIdVal = $locationId > 0 ? $locationId : null;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO users (name, mobile, password_hash, role_id, location_id, issuperadmin, isactive) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssiiii', $name, $mobile, $passwordHash, $roleIdVal, $locationIdVal, $issuperadmin, $isactive);
    $stmt->execute();
    $insertId = $conn->insert_id;
    $stmt->close();

    // Save branch assignments (super admin gets all branches implicitly; we still record their pivots if provided)
    if (!empty($locationIds)) {
        $ins = $conn->prepare("INSERT IGNORE INTO user_locations (user_id, location_id) VALUES (?, ?)");
        foreach ($locationIds as $lid) {
            if ($lid <= 0) continue;
            $ins->bind_param('ii', $insertId, $lid);
            $ins->execute();
        }
        $ins->close();
    }

    $conn->commit();
    jsonResponse(true, 'User created successfully', ['id' => $insertId]);
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed to create user: ' . $e->getMessage());
}
