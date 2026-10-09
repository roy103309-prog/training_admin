<?php
require_once '../tenant-config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
$user = requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$roleId = (int)($input['role_id'] ?? 0);
$permissions = $input['permissions'] ?? [];
$locationIds = $input['location_ids'] ?? [];
if ($roleId <= 0) jsonResponse(false, 'Role ID required');

$conn = getTenantConnection();


$conn->begin_transaction();

try {
    // Replace permissions
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->bind_param('i', $roleId);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, menu_id, can_view, can_add, can_edit, can_delete) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($permissions as $menuId => $perms) {
        $menuId    = (int)$menuId;
        $canView   = (int)($perms['can_view'] ?? 0);
        $canAdd    = (int)($perms['can_add'] ?? 0);
        $canEdit   = (int)($perms['can_edit'] ?? 0);
        $canDelete = (int)($perms['can_delete'] ?? 0);
        $stmt->bind_param('iiiiii', $roleId, $menuId, $canView, $canAdd, $canEdit, $canDelete);
        $stmt->execute();
    }

    // Replace role_locations
    $delLoc = $conn->prepare("DELETE FROM role_locations WHERE role_id = ?");
    $delLoc->bind_param('i', $roleId);
    $delLoc->execute();

    if (is_array($locationIds) && count($locationIds) > 0) {
        $insLoc = $conn->prepare("INSERT IGNORE INTO role_locations (role_id, location_id) VALUES (?, ?)");
        foreach ($locationIds as $lid) {
            $lid = (int)$lid;
            if ($lid <= 0) continue;
            $insLoc->bind_param('ii', $roleId, $lid);
            $insLoc->execute();
        }
    }

    $conn->commit();
    jsonResponse(true, 'Permissions updated successfully');
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Failed to update permissions: ' . $e->getMessage());
}
