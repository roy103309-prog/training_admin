<?php
require_once '../tenant-config.php';
$user = requireAuth();

$conn = getTenantConnection();
$stmt = $conn->prepare("SELECT id, name, address, phone, email, isactive, created_at
                        FROM locations ORDER BY id");
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();

jsonResponse(true, 'OK', ['items' => $items]);
