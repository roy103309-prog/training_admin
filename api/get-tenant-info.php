<?php
require_once 'tenant-config.php';

// Get authenticated user
$user = requireAuth();

// Get tenant ID from session
$tenantId = $_SESSION['tenant_id'] ?? null;

if (!$tenantId) {
    jsonResponse(false, 'No tenant found in session');
}

// Get tenant info from master database
$masterConn = getMasterConnection();
$stmt = $masterConn->prepare("SELECT business_name, owner_name, email, mobile, plan_type, status FROM tenants WHERE id = ?");
$stmt->bind_param('i', $tenantId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $result->free();
    $stmt->close();
    jsonResponse(false, 'Tenant not found');
}

$tenant = $result->fetch_assoc();
$result->free();
$stmt->close();

jsonResponse(true, 'Tenant information retrieved', $tenant);
