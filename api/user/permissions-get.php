<?php
require_once '../tenant-config.php';
$user = requireAuth();
$roleId = (int)($_GET['role_id'] ?? 0);
if ($roleId <= 0) jsonResponse(false, 'Role ID required');

$conn = getTenantConnection();


// Get all menus ordered hierarchically (parent then its children)
$menus = [];
$menuStmt = $conn->query("
    SELECT m.id, m.name, m.parent_id, m.sort_order,
           COALESCE(p.sort_order, m.sort_order) as parent_sort,
           CASE WHEN m.parent_id IS NULL THEN 0 ELSE 1 END as is_child
    FROM menus m
    LEFT JOIN menus p ON m.parent_id = p.id
    WHERE m.isactive = 1
    ORDER BY parent_sort, is_child, m.parent_id, m.sort_order, m.id
");
while ($row = $menuStmt->fetch_assoc()) $menus[] = $row;

// Get permissions for this role
$permissions = [];
$permStmt = $conn->prepare("SELECT menu_id, can_view, can_add, can_edit, can_delete FROM role_permissions WHERE role_id = ?");
$permStmt->bind_param('i', $roleId);
$permStmt->execute();
$result = $permStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $permissions[$row['menu_id']] = [
        'can_view' => (bool)$row['can_view'],
        'can_add' => (bool)$row['can_add'],
        'can_edit' => (bool)$row['can_edit'],
        'can_delete' => (bool)$row['can_delete']
    ];
}

// Load all active locations
$locations = [];
$locRes = $conn->query("SELECT id, name FROM locations WHERE isactive = 1 ORDER BY id");
while ($r = $locRes->fetch_assoc()) $locations[] = $r;

// Load location ids assigned to this role
$roleLocations = [];
$rlStmt = $conn->prepare("SELECT location_id FROM role_locations WHERE role_id = ?");
$rlStmt->bind_param('i', $roleId);
$rlStmt->execute();
$rlRes = $rlStmt->get_result();
while ($r = $rlRes->fetch_assoc()) $roleLocations[] = (int)$r['location_id'];
$rlStmt->close();

jsonResponse(true, 'Permissions retrieved', [
    'menus' => $menus,
    'permissions' => $permissions,
    'locations' => $locations,
    'role_locations' => $roleLocations
]);
