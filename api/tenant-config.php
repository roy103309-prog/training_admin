<?php
// Multi-Tenant SAAS Configuration
// Manages tenant database connections

// Database connection settings (host, user, pass, db names, tenant prefix)
require_once __DIR__ . '/config/connection.php';

// Session configuration — no idle timeout, session cookie expires only on browser close or explicit logout
ini_set('session.gc_maxlifetime', 0);
session_set_cookie_params(0); // lifetime 0 = session cookie (cleared when browser closes)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Master database connection
function getMasterConnection() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(MASTER_DB_HOST, MASTER_DB_USER, MASTER_DB_PASS, MASTER_DB_NAME, MASTER_DB_PORT);

        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Master database connection failed']);
            exit;
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}

// Tenant database connection
function getTenantConnection($databaseName = null) {
    static $connections = [];

    // Get database name from session if not provided
    if ($databaseName === null) {
        $databaseName = $_SESSION['tenant_database'] ?? null;
    }

    if (!$databaseName) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No tenant database specified']);
        exit;
    }

    // Return existing connection if available
    if (isset($connections[$databaseName])) {
        return $connections[$databaseName];
    }

    // Create new connection
    $conn = new mysqli(MASTER_DB_HOST, MASTER_DB_USER, MASTER_DB_PASS, $databaseName, MASTER_DB_PORT);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Tenant database connection failed']);
        exit;
    }

    $conn->set_charset('utf8mb4');
    $connections[$databaseName] = $conn;

    return $conn;
}

// Response helper
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Hash a password for storing in a password_hash column
function hashPassword($plain) {
    return password_hash((string)$plain, PASSWORD_DEFAULT);
}

// Check a password against a stored value. Older rows still hold plain text,
// so a value that is not a hash is compared directly; login upgrades those rows.
function checkPassword($plain, $stored) {
    $plain = (string)$plain;
    $stored = (string)$stored;
    if ($stored === '') return false;
    if (password_get_info($stored)['algo']) return password_verify($plain, $stored);
    return hash_equals($stored, $plain);
}

// True when the stored value is plain text or uses an outdated hash
function passwordNeedsUpgrade($stored) {
    $stored = (string)$stored;
    return !password_get_info($stored)['algo'] || password_needs_rehash($stored, PASSWORD_DEFAULT);
}

// Check if user is authenticated
function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_logged_in']) || !isset($_SESSION['tenant_database'])) {
        http_response_code(401);
        jsonResponse(false, 'Unauthorized. Please login.');
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'tenant_id' => $_SESSION['tenant_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'mobile' => $_SESSION['user_mobile'] ?? '',
        'role_id' => $_SESSION['role_id'] ?? null,
        'location_id' => $_SESSION['location_id'] ?? null,
        'issuperadmin' => $_SESSION['issuperadmin'] ?? 0
    ];
}

// Get current location, snapping to a role-allowed branch if session value is stale.
function getCurrentLocation() {
    $current = $_SESSION['current_location_id'] ?? $_SESSION['location_id'] ?? null;
    if (!empty($_SESSION['issuperadmin'])) return $current;
    $roleId = $_SESSION['role_id'] ?? 0;
    if (!$roleId) return $current;
    try {
        $conn = getTenantConnection();
        $allowed = getRoleLocationIds($conn, (int)$roleId);
        if (is_array($allowed) && count($allowed) > 0 && !in_array((int)$current, $allowed, true)) {
            $_SESSION['current_location_id'] = $allowed[0];
            return $allowed[0];
        }
    } catch (Exception $e) { /* fall through */ }
    return $current;
}

/**
 * Get location IDs restricted by a role's role_locations rows.
 * Returns null = role has NO rows (unrestricted, backward compatible).
 * Returns array (possibly empty) when role has at least one row.
 */
function getRoleLocationIds($conn, $roleId) {
    $roleId = (int)$roleId;
    if ($roleId <= 0) return null;
    // Ensure table exists silently to avoid fatal on older DBs
    $stmt = $conn->prepare("SELECT location_id FROM role_locations WHERE role_id = ?");
    $stmt->bind_param('i', $roleId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = (int)$r['location_id'];
    $stmt->close();
    if (count($rows) === 0) return null;
    return $rows;
}

// Set current location
function setCurrentLocation($locationId) {
    $_SESSION['current_location_id'] = (int)$locationId;
}

// Check permission
function hasPermission($menuUrl, $action = 'view') {
    if (!empty($_SESSION['issuperadmin'])) {
        return true;
    }

    $conn = getTenantConnection();
    $roleId = $_SESSION['role_id'] ?? 0;

    $actionColumn = 'can_view';
    if ($action === 'add') $actionColumn = 'can_add';
    elseif ($action === 'edit') $actionColumn = 'can_edit';
    elseif ($action === 'delete') $actionColumn = 'can_delete';

    $stmt = $conn->prepare("
        SELECT rp.{$actionColumn}
        FROM role_permissions rp
        JOIN menus m ON m.id = rp.menu_id
        WHERE rp.role_id = ? AND m.url = ? AND m.isactive = 1
    ");
    $stmt->bind_param('is', $roleId, $menuUrl);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $hasAccess = (bool)$row[$actionColumn];
        $result->free();
        $stmt->close();
        return $hasAccess;
    }

    $result->free();
    $stmt->close();
    return false;
}

// Sanitize input
function sanitize($input) {
    $conn = getTenantConnection();
    return $conn->real_escape_string(trim($input));
}

// Upload file helper with tenant-specific folder and module subfolder
function uploadFile($file, $subfolder = '', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Always use tenant-specific media folder as base
    $tenantId = $_SESSION['tenant_id'] ?? null;
    $uploadDir = getTenantMediaFolder($tenantId);

    // Append module subfolder if provided (e.g. 'rooms', 'sliders', 'logo')
    if (!empty($subfolder)) {
        $uploadDir .= trim($subfolder, '/') . '/';
    }

    $uploadPath = dirname(__DIR__) . '/' . $uploadDir;
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = $uploadPath . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'path' => $uploadDir . $filename];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Delete file helper
function deleteFile($filePath) {
    $fullPath = dirname(__DIR__) . '/' . $filePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Decode a base64 data-URL, write it to the per-tenant uploads folder under
 * the given subfolder, and return the public relative URL.
 *
 * Usage:
 *   $input = $data['image'] ?? null;  // one of: null, '', 'data:image/...;base64,....', or an existing URL
 *   $url   = saveBase64Image($input, 'items', $oldUrlFromDb);
 *     - null                -> returns null, no change suggested (caller should skip column update)
 *     - ''                  -> returns '' and deletes $oldUrl (caller should set column = NULL/'')
 *     - data:image/...      -> saves file, deletes $oldUrl, returns 'uploads/<tenant_db>/<sub>/<name>.<ext>'
 *     - existing URL string -> returns it unchanged (nothing saved, nothing deleted)
 *
 * @param string|null $input       base64 data URL, existing URL, '', or null
 * @param string      $subfolder   module folder name e.g. 'users', 'items', 'categories', 'logo'
 * @param string|null $oldPath     previously stored path (for cleanup on replace/clear)
 * @param int         $maxBytes    max decoded size, default 3 MB
 * @return string|null             URL to store in DB, '' to clear, or null to leave unchanged
 */
function saveBase64Image($input, $subfolder, $oldPath = null, $maxBytes = 3145728) {
    // null => caller asked us to leave the column alone
    if ($input === null) return null;

    // empty string => clear image + delete old file
    if ($input === '' || $input === '0') {
        if ($oldPath && strpos($oldPath, 'uploads/') === 0) deleteFile($oldPath);
        return '';
    }

    // Not a data URL -> assume it's already a stored path/URL, keep as-is
    if (!preg_match('#^data:image/([a-zA-Z0-9+.\-]+);base64,#', $input, $m)) {
        return $input;
    }

    $mime = strtolower($m[1]);
    $extMap = [
        'jpeg' => 'jpg', 'jpg' => 'jpg', 'pjpeg' => 'jpg',
        'png'  => 'png', 'x-png' => 'png',
        'gif'  => 'gif',
        'webp' => 'webp',
        'svg+xml' => 'svg', 'svg' => 'svg',
    ];
    if (!isset($extMap[$mime])) return $input; // unknown type, don't touch
    $ext = $extMap[$mime];

    // Strip prefix and decode
    $raw = substr($input, strpos($input, ',') + 1);
    $bin = base64_decode($raw, true);
    if ($bin === false) return $input;
    if (strlen($bin) > $maxBytes) {
        // Too big; let caller decide whether to error. Here we just refuse to save.
        return $input;
    }

    // Resolve per-tenant folder
    $tenantId  = $_SESSION['tenant_id'] ?? null;
    $baseRel   = rtrim(getTenantMediaFolder($tenantId), '/') . '/' . trim($subfolder, '/') . '/';
    $baseAbs   = dirname(__DIR__) . '/' . $baseRel;
    if (!is_dir($baseAbs)) {
        @mkdir($baseAbs, 0755, true);
    }

    $filename = uniqid('', true) . '_' . time() . '.' . $ext;
    $filename = str_replace('.', '', substr($filename, 0, strrpos($filename, '.'))) . '.' . $ext;
    $absPath  = $baseAbs . $filename;

    if (file_put_contents($absPath, $bin) === false) {
        return $input; // save failed, keep old value
    }

    // Delete old file if it lived under uploads/
    if ($oldPath && strpos($oldPath, 'uploads/') === 0) {
        deleteFile($oldPath);
    }

    return $baseRel . $filename;
}

// Create new tenant database
function createTenantDatabase($databaseName) {
    $conn = new mysqli(MASTER_DB_HOST, MASTER_DB_USER, MASTER_DB_PASS, null, MASTER_DB_PORT);

    if ($conn->connect_error) {
        return false;
    }

    // Create database
    $safeName = $conn->real_escape_string($databaseName);
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        return false;
    }

    // Select the new database
    $conn->select_db($safeName);

    // Idempotent ALTER/INSERT inside the consolidated schema must not throw — log & continue.
    mysqli_report(MYSQLI_REPORT_OFF);

    // Single consolidated tenant schema (base + all feature modules folded in).
    $schemaFile = dirname(__DIR__) . '/database/tenant_schema.sql';
    if (!file_exists($schemaFile)) {
        return false;
    }

    $schema = file_get_contents($schemaFile);
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $statement) {
        if ($statement === '') continue;
        // MySQL handles leading "-- ..." line comments inside a statement itself.
        if (!$conn->query($statement)) {
            error_log("Tenant schema SQL Error: " . $conn->error);
        }
    }

    $conn->close();

    // Create tenant upload folder using database name
    $uploadDir = dirname(__DIR__) . '/uploads/' . $databaseName . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    return true;
}

// Generate unique database name based on tenant ID
function generateDatabaseName($tenantId) {
    return TENANT_DB_PREFIX . (TENANT_DB_BASE + intval($tenantId));
}

// Generate media folder name for tenant using database name
function getTenantMediaFolder($tenantId = null) {
    // First try to get database name from session
    $dbName = $_SESSION['tenant_database'] ?? null;

    if (!$dbName && $tenantId !== null) {
        $dbName = generateDatabaseName($tenantId);
    }

    if (!$dbName) {
        $tenantId = $tenantId ?? ($_SESSION['tenant_id'] ?? null);
        if ($tenantId) {
            $dbName = generateDatabaseName($tenantId);
        }
    }

    if (!$dbName) {
        return 'uploads/default/';
    }

    return 'uploads/' . $dbName . '/';
}
