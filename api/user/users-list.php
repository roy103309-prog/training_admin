<?php
require_once '../tenant-config.php';
$user = requireAuth();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(10, (int)($_GET['limit'] ?? 10)));
$search = sanitize($_GET['search'] ?? '');
$offset = ($page - 1) * $limit;
$conn = getTenantConnection();

$countQuery = "SELECT COUNT(*) as total FROM users";
$params = []; $types = '';
if ($search) { $countQuery .= " WHERE name LIKE ? OR mobile LIKE ?"; $params[] = "%$search%"; $params[] = "%$search%"; $types .= 'ss'; }

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc()['total'];
$result->free();
$stmt->close();

$query = "SELECT u.*, r.name as role_name, l.name as location_name FROM users u LEFT JOIN roles r ON r.id = u.role_id LEFT JOIN locations l ON l.id = u.location_id";
if ($search) $query .= " WHERE u.name LIKE ? OR u.mobile LIKE ?";
$query .= " ORDER BY u.id DESC LIMIT ? OFFSET ?";
$params[] = $limit; $params[] = $offset; $types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$items = [];
$userIds = [];
while ($row = $result->fetch_assoc()) {
    unset($row['password_hash']); // Don't send password hash
    unset($row['avatar']);        // Skip heavy base64 in list view
    $row['location_ids'] = [];
    $items[] = $row;
    $userIds[] = (int)$row['id'];
}
$result->free();
$stmt->close();

// Load branch assignments for this page's users in one shot
if (!empty($userIds)) {
    $idx = [];
    foreach ($items as $k => $it) $idx[$it['id']] = $k;
    $inList = implode(',', array_fill(0, count($userIds), '?'));
    $types2 = str_repeat('i', count($userIds));
    $stmt2 = $conn->prepare("SELECT user_id, location_id FROM user_locations WHERE user_id IN ($inList)");
    $stmt2->bind_param($types2, ...$userIds);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
        $k = $idx[$r['user_id']] ?? null;
        if ($k !== null) $items[$k]['location_ids'][] = (int)$r['location_id'];
    }
    $res2->free();
    $stmt2->close();
}

jsonResponse(true, 'Users retrieved', ['items' => $items, 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => (int)$total, 'pages' => ceil($total / $limit)]]);
