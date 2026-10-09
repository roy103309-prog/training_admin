<?php
require_once '../tenant-config.php';

header('Content-Type: application/json');

/** Safe scalar query; returns 0 if table missing or query fails. */
function dashScalar(mysqli $conn, $sql) {
    try {
        $res = @$conn->query($sql);
        if (!$res) return 0;
        $row = $res->fetch_row();
        $res->free();
        return $row ? $row[0] : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

try {
    // Check authentication
    $user = requireAuth();
    $locationId = getCurrentLocation();
    if (!$locationId) jsonResponse(false, 'No location selected');

    $masterConn = getMasterConnection();
    $conn = getTenantConnection();
    $tenantId = $_SESSION['tenant_id'];
    $today = date('Y-m-d');

    $stats = [];

    // Institution Information from master database
    $tenantStmt = $masterConn->prepare("SELECT t.id, t.mobile, t.business_name, t.owner_name
                                        FROM tenants t
                                        WHERE t.id = ?
                                        LIMIT 1");
    $tenantStmt->bind_param('i', $tenantId);
    $tenantStmt->execute();
    $tenantResult = $tenantStmt->get_result();
    $tenantInfo = $tenantResult->fetch_assoc();
    $tenantStmt->close();

    // Owner info from tenant database (first super admin user)
    $ownerStmt = $conn->prepare("SELECT name, mobile, avatar FROM users WHERE issuperadmin = 1 ORDER BY id ASC LIMIT 1");
    $ownerStmt->execute();
    $ownerResult = $ownerStmt->get_result();
    $ownerInfo = $ownerResult->fetch_assoc();
    $ownerStmt->close();

    // Basic settings for institution name
    $settingsStmt = $conn->prepare("SELECT website_name, logo FROM settings_basic WHERE location_id = ? LIMIT 1");
    $settingsStmt->bind_param('i', $locationId);
    $settingsStmt->execute();
    $settingsResult = $settingsStmt->get_result();
    $settings = $settingsResult->fetch_assoc();
    $settingsStmt->close();

    $tenantDatabase = $_SESSION['tenant_database'] ?? generateDatabaseName($tenantId);
    $dbSerial = preg_replace('/^[a-z]+_/', '', $tenantDatabase);

    $stats['institution'] = [
        'name' => $settings['website_name'] ?? $tenantInfo['business_name'] ?? 'Business Admin',
        'id' => $dbSerial,
        'mobile' => $tenantInfo['mobile'] ?? '',
        'logo' => $settings['logo'] ?? '',
        'year' => date('Y'),
        'database_name' => $tenantDatabase,
    ];

    $stats['owner'] = [
        'name' => $ownerInfo['name'] ?? $tenantInfo['owner_name'] ?? '',
        'email' => '',
        'mobile' => $ownerInfo['mobile'] ?? $tenantInfo['mobile'] ?? '',
        'image' => !empty($ownerInfo['avatar']) ? $ownerInfo['avatar'] : '',
    ];

    // SMS Balance (placeholder - integrate with SMS provider API)
    $stats['sms_balance'] = 1000;

    // Core counts (guaranteed tables)
    $stats['total_users']     = (int) dashScalar($conn, "SELECT COUNT(*) FROM users");
    $stats['total_employees'] = (int) dashScalar($conn, "SELECT COUNT(*) FROM users WHERE isactive = 1");
    $stats['total_roles']     = (int) dashScalar($conn, "SELECT COUNT(*) FROM roles WHERE isactive = 1");
    $stats['total_branches']  = (int) dashScalar($conn, "SELECT COUNT(*) FROM locations WHERE isactive = 1");

    // Attendance snapshot for today
    $stats['present_today']  = (int) dashScalar($conn, "SELECT COUNT(*) FROM att_records WHERE location_id = $locationId AND att_date = '$today' AND is_absent = 0");
    $stats['absent_today']   = (int) dashScalar($conn, "SELECT COUNT(*) FROM att_records WHERE location_id = $locationId AND att_date = '$today' AND is_absent = 1");
    $stats['on_leave_today'] = (int) dashScalar($conn, "SELECT COUNT(*) FROM att_leaves WHERE location_id = $locationId AND status = 'approved' AND '$today' BETWEEN from_date AND to_date");

    $conn->close();

    jsonResponse(true, 'Dashboard statistics retrieved successfully', $stats);

} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}
