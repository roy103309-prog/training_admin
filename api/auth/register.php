<?php
require_once '../tenant-config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);
$businessName = trim($input['business_name'] ?? '');
$ownerName = trim($input['owner_name'] ?? '');
$mobile = trim($input['mobile'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

// Validation
if (empty($businessName)) {
    jsonResponse(false, 'Business name is required');
}

if (empty($ownerName)) {
    jsonResponse(false, 'Owner name is required');
}

if (empty($mobile)) {
    jsonResponse(false, 'Mobile number is required');
}

if (strlen($mobile) < 10) {
    jsonResponse(false, 'Invalid mobile number');
}

if (empty($password)) {
    jsonResponse(false, 'Password is required');
}

if (strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters');
}

if ($password !== $confirmPassword) {
    jsonResponse(false, 'Passwords do not match');
}

$masterConn = getMasterConnection();

// Check if mobile already exists
$stmt = $masterConn->prepare("SELECT id FROM tenants WHERE mobile = ?");
$stmt->bind_param('s', $mobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $result->free();
    $stmt->close();
    jsonResponse(false, 'Mobile number already registered');
}
$result->free();
$stmt->close();

// Store password as plain text
$passwordHash = $password;

// Create tenant record first (without database_name)
$stmt = $masterConn->prepare("
    INSERT INTO tenants (business_name, owner_name, mobile, email, password_hash, status, trial_ends_at)
    VALUES (?, ?, ?, ?, ?, 'active', DATE_ADD(NOW(), INTERVAL 30 DAY))
");
$stmt->bind_param('sssss', $businessName, $ownerName, $mobile, $email, $passwordHash);

if (!$stmt->execute()) {
    $stmt->close();
    jsonResponse(false, 'Failed to create account');
}

$tenantId = $masterConn->insert_id;
$stmt->close();

// Generate database name using tenant_id (e.g. training_100001, training_100002)
$databaseName = generateDatabaseName($tenantId);

// Update tenant record with database name
$stmt = $masterConn->prepare("UPDATE tenants SET database_name = ? WHERE id = ?");
$stmt->bind_param('si', $databaseName, $tenantId);
$stmt->execute();
$stmt->close();

// Create tenant database
if (!createTenantDatabase($databaseName)) {
    // Rollback - delete tenant record
    $masterConn->query("DELETE FROM tenants WHERE id = {$tenantId}");
    jsonResponse(false, 'Failed to create tenant database');
}

// Create media folder for this tenant
$mediaFolder = dirname(__DIR__) . '/' . getTenantMediaFolder($tenantId);
if (!is_dir($mediaFolder)) {
    mkdir($mediaFolder, 0755, true);
}

// Update default admin user in tenant database with owner info
$tenantConn = getTenantConnection($databaseName);
$stmt = $tenantConn->prepare("UPDATE users SET name = ?, mobile = ?, password_hash = ? WHERE id = 1");
$stmt->bind_param('sss', $ownerName, $mobile, $passwordHash);
$stmt->execute();
$stmt->close();

// Log activity
$stmt = $masterConn->prepare("INSERT INTO activity_log (tenant_id, activity_type, description) VALUES (?, 'registration', 'New tenant registered')");
$stmt->bind_param('i', $tenantId);
$stmt->execute();
$stmt->close();

// Serial = tenant database name with the configured prefix stripped (e.g. 100001)
$serial = substr($databaseName, strlen(TENANT_DB_PREFIX));

jsonResponse(true, 'Registration successful! You can now login.', [
    'tenant_id' => (string)$serial,
    'name' => $ownerName,
    'mobile' => $mobile
]);
