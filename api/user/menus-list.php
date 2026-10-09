<?php
require_once '../tenant-config.php';
$user = requireAuth();
$conn = getTenantConnection();

$menus = [];
$stmt = $conn->prepare("SELECT m.*, COALESCE(p.name, '') as parent_name FROM menus m LEFT JOIN menus p ON p.id = m.parent_id ORDER BY m.parent_id, m.sort_order, m.id");
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) $items[] = $row;
$result->free();
$stmt->close();

jsonResponse(true, 'Menus retrieved', ['items' => $items]);
