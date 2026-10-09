<?php
require_once '../tenant-config.php';

// Return false from prepare() on bad SQL (e.g. missing email column) instead of throwing,
// so the mobile-only fallback queries can run.
mysqli_report(MYSQLI_REPORT_OFF);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);
// Accept either mobile or email as the login identifier
$mobile = trim($input['mobile'] ?? ($input['identifier'] ?? ''));
$password = $input['password'] ?? '';

if (empty($mobile) || empty($password)) {
    jsonResponse(false, 'Mobile/email and password are required');
}

// First, check master database for tenant owner login (mobile OR email)
$masterConn = getMasterConnection();
$stmt = $masterConn->prepare("SELECT id, business_name, database_name, password_hash, status FROM tenants WHERE mobile = ? OR email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('ss', $mobile, $mobile);
} else {
    // Fallback if tenants has no email column
    $stmt = $masterConn->prepare("SELECT id, business_name, database_name, password_hash, status FROM tenants WHERE mobile = ? LIMIT 1");
    $stmt->bind_param('s', $mobile);
}
$stmt->execute();
$result = $stmt->get_result();

$tenant = null;
$user = null;
$tenantConn = null;

if ($result->num_rows > 0) {
    $tenant = $result->fetch_assoc();
}
$result->free();
$stmt->close();

$ownerVerified = null; // tenant row whose owner password matched, for the hash upgrade below
if ($tenant) {
    if (checkPassword($password, $tenant['password_hash'])) {
        $ownerVerified = ['id' => (int)$tenant['id'], 'hash' => $tenant['password_hash']];
        if ($tenant['status'] !== 'active') {
            jsonResponse(false, 'Your account is suspended. Please contact support.');
        }

        $tenantConn = getTenantConnection($tenant['database_name']);
        $stmt = $tenantConn->prepare("
            SELECT u.id, u.name, u.mobile, u.password_hash, u.avatar, u.role_id, u.location_id,
                   u.issuperadmin, u.isactive, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE u.mobile = ? OR u.email = ?
        ");
        if (!$stmt) {
            $stmt = $tenantConn->prepare("
                SELECT u.id, u.name, u.mobile, u.password_hash, u.avatar, u.role_id, u.location_id,
                       u.issuperadmin, u.isactive, r.name as role_name
                FROM users u LEFT JOIN roles r ON r.id = u.role_id
                WHERE u.mobile = ?");
            $stmt->bind_param('s', $mobile);
        } else {
            $stmt->bind_param('ss', $mobile, $mobile);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $result->free();
            $stmt->close();

            if (!$user['isactive']) {
                jsonResponse(false, 'Your account is inactive. Please contact administrator.');
            }

            if (!checkPassword($password, $user['password_hash'])) {
                jsonResponse(false, 'Invalid credentials');
            }
        } else {
            $result->free();
            $stmt->close();
        }
    }
}

// If not matched as tenant owner, search tenant databases for the sub-user mobile
if (!$user) {
    $tenantStmt = $masterConn->prepare("SELECT id, business_name, database_name, status FROM tenants WHERE status = 'active'");
    $tenantStmt->execute();
    $tenantsResult = $tenantStmt->get_result();

    while ($tenantRow = $tenantsResult->fetch_assoc()) {
        try {
            $conn = getTenantConnection($tenantRow['database_name']);
            $stmt = $conn->prepare("
                SELECT u.id, u.name, u.mobile, u.password_hash, u.avatar, u.role_id, u.location_id,
                       u.issuperadmin, u.isactive, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                WHERE u.mobile = ? OR u.email = ?
                LIMIT 1
            ");
            if (!$stmt) {
                $stmt = $conn->prepare("
                    SELECT u.id, u.name, u.mobile, u.password_hash, u.avatar, u.role_id, u.location_id,
                           u.issuperadmin, u.isactive, r.name as role_name
                    FROM users u LEFT JOIN roles r ON r.id = u.role_id
                    WHERE u.mobile = ? LIMIT 1");
                $stmt->bind_param('s', $mobile);
            } else {
                $stmt->bind_param('ss', $mobile, $mobile);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $tempUser = $result->fetch_assoc();
                $result->free();
                $stmt->close();

                if ($tempUser['isactive'] && checkPassword($password, $tempUser['password_hash'])) {
                    $user = $tempUser;
                    $tenant = $tenantRow;
                    $tenantConn = $conn;
                    break;
                }
            } else {
                $result->free();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Skip unreachable tenant DBs
        }
    }

    $tenantsResult->free();
    $tenantStmt->close();
}

if (!$tenant || !$user) {
    jsonResponse(false, 'Invalid credentials');
}

if ($tenant['status'] !== 'active') {
    jsonResponse(false, 'Your account is suspended. Please contact support.');
}

if (!$user['isactive']) {
    jsonResponse(false, 'Your account is inactive. Please contact administrator.');
}

if (!checkPassword($password, $user['password_hash'])) {
    jsonResponse(false, 'Invalid credentials');
}

// Replace any plain-text password with a hash now that we know it is correct
if (passwordNeedsUpgrade($user['password_hash'])) {
    $newHash = hashPassword($password);
    $up = $tenantConn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($up) { $up->bind_param('si', $newHash, $user['id']); $up->execute(); $up->close(); }
}
if ($ownerVerified && passwordNeedsUpgrade($ownerVerified['hash'])) {
    $newHash = hashPassword($password);
    $up = $masterConn->prepare("UPDATE tenants SET password_hash = ? WHERE id = ?");
    if ($up) { $up->bind_param('si', $newHash, $ownerVerified['id']); $up->execute(); $up->close(); }
}

// Create session
$_SESSION['user_logged_in'] = true;
$_SESSION['tenant_id'] = $tenant['id'];
$_SESSION['tenant_database'] = $tenant['database_name'];
$_SESSION['business_name'] = $tenant['business_name'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_mobile'] = $user['mobile'];
$_SESSION['user_avatar'] = $user['avatar'] ?? null;
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['role_name'] = $user['role_name'];
$_SESSION['location_id'] = $user['location_id'];
$_SESSION['current_location_id'] = $user['location_id'];
$_SESSION['issuperadmin'] = $user['issuperadmin'];

// Get locations accessible to this user:
//   - Super admin: all active branches
//   - Regular user: branches from user_locations pivot (fallback to users.location_id if pivot empty)
$locations = [];
if ($user['issuperadmin']) {
    $locStmt = $tenantConn->query("SELECT id, name FROM locations WHERE isactive = 1 ORDER BY name");
    while ($loc = $locStmt->fetch_assoc()) $locations[] = $loc;
    $locStmt->free_result();
} else {
    $locStmt = $tenantConn->prepare("
        SELECT l.id, l.name FROM user_locations ul
        JOIN locations l ON l.id = ul.location_id
        WHERE ul.user_id = ? AND l.isactive = 1
        ORDER BY l.name");
    $locStmt->bind_param('i', $user['id']);
    $locStmt->execute();
    $locResult = $locStmt->get_result();
    while ($loc = $locResult->fetch_assoc()) $locations[] = $loc;
    $locResult->free();
    $locStmt->close();

    // Fallback to legacy users.location_id if pivot has no rows
    if (empty($locations) && !empty($user['location_id'])) {
        $locStmt = $tenantConn->prepare("SELECT id, name FROM locations WHERE id = ? AND isactive = 1");
        $locStmt->bind_param('i', $user['location_id']);
        $locStmt->execute();
        $locResult = $locStmt->get_result();
        if ($loc = $locResult->fetch_assoc()) $locations[] = $loc;
        $locResult->free();
        $locStmt->close();
    }
}

// Apply role-level branch restriction (intersect user_locations with role_locations)
if (!$user['issuperadmin'] && !empty($user['role_id'])) {
    $roleLocIds = getRoleLocationIds($tenantConn, (int)$user['role_id']);
    if (is_array($roleLocIds)) {
        $allowed = array_flip($roleLocIds);
        $locations = array_values(array_filter($locations, function ($l) use ($allowed) {
            return isset($allowed[(int)$l['id']]);
        }));
    }
}

if (empty($locations) && !$user['issuperadmin']) {
    jsonResponse(false, 'No branch assigned to your role. Contact administrator.');
}

// Default current location: first accessible branch
$defaultLocationId = !empty($locations) ? (int)$locations[0]['id'] : null;
$_SESSION['current_location_id'] = $defaultLocationId;
$_SESSION['accessible_location_ids'] = array_map(function ($l) { return (int)$l['id']; }, $locations);

// Log activity in master
$stmt = $masterConn->prepare("INSERT INTO activity_log (tenant_id, activity_type, description) VALUES (?, 'login', ?)");
$desc = "User {$user['name']} logged in";
$stmt->bind_param('is', $tenant['id'], $desc);
$stmt->execute();
$stmt->close();

jsonResponse(true, 'Login successful', [
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'mobile' => $user['mobile'],
        'role_name' => $user['role_name'],
        'avatar' => $user['avatar'] ?? null,
        'issuperadmin' => (bool)$user['issuperadmin']
    ],
    'business_name' => $tenant['business_name'],
    'locations' => $locations,
    'current_location_id' => $defaultLocationId
]);
