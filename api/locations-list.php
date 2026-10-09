<?php
require_once '../tenant-config.php';
$user = requireAuth();
$conn = getTenantConnection();

$locations = [];
$stmt = $conn->prepare("SELECT * FROM locations WHERE isactive = 1 ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) $items[] = $row;
$result->free();
$stmt->close();

jsonResponse(true, 'Locations retrieved', ['items' => $items]);
