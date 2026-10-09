<?php
require_once '../tenant-config.php';

header('Content-Type: application/json');

$user = requireAuth();

$conn = getTenantConnection();

// Get menu items with permissions
if ($user['issuperadmin']) {
    // Super admin sees all active menus
    $result = $conn->query("
        SELECT id, name, icon, url, parent_id, sort_order
        FROM menus
        WHERE isactive = 1
        ORDER BY parent_id, sort_order, id
    ");
    $stmt = null; // No prepared statement for superadmin
} else {
    // Regular users see only permitted menus
    $roleId = $user['role_id'];
    $stmt = $conn->prepare("
        SELECT DISTINCT m.id, m.name, m.icon, m.url, m.parent_id, m.sort_order,
               rp.can_view, rp.can_add, rp.can_edit, rp.can_delete
        FROM menus m
        JOIN role_permissions rp ON rp.menu_id = m.id
        WHERE rp.role_id = ? AND rp.can_view = 1 AND m.isactive = 1
        ORDER BY m.parent_id, m.sort_order, m.id
    ");
    $stmt->bind_param('i', $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
}

$menus = [];
$permissions = [];

while ($row = $result->fetch_assoc()) {
    $menus[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'icon' => $row['icon'],
        'url' => $row['url'],
        'parent_id' => $row['parent_id'] ? (int)$row['parent_id'] : null,
        'sort_order' => (int)$row['sort_order']
    ];

    // Store permissions for each menu
    if (!$user['issuperadmin']) {
        $permissions[$row['id']] = [
            'can_view' => (bool)$row['can_view'],
            'can_add' => (bool)$row['can_add'],
            'can_edit' => (bool)$row['can_edit'],
            'can_delete' => (bool)$row['can_delete']
        ];
    } else {
        // Super admin has all permissions
        $permissions[$row['id']] = [
            'can_view' => true,
            'can_add' => true,
            'can_edit' => true,
            'can_delete' => true
        ];
    }
}

// Build hierarchical structure
$tree = [];
$lookup = [];

foreach ($menus as $menu) {
    $menu['children'] = [];
    $menu['permissions'] = $permissions[$menu['id']];
    $lookup[$menu['id']] = $menu;
}

foreach ($lookup as $id => $menu) {
    if ($menu['parent_id'] === null) {
        $tree[] = &$lookup[$id];
    } else if (isset($lookup[$menu['parent_id']])) {
        $lookup[$menu['parent_id']]['children'][] = &$lookup[$id];
    }
}

// Clean up
if (isset($result) && is_object($result)) {
    $result->free();
}
if (isset($stmt) && is_object($stmt)) {
    $stmt->close();
}

jsonResponse(true, 'Menu retrieved', [
    'menus' => $tree,
    'flat_menus' => $menus,
    'permissions' => $permissions
]);
