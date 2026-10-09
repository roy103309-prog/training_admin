<?php
require_once '../tenant-config.php';
$user = requireAuth();
$locationId = (int)(getCurrentLocation() ?: 0);

$conn = getTenantConnection();

$limit = min(50, max(1, (int)($_GET['limit'] ?? 15)));

$stmt = $conn->prepare("SELECT id, type, title, message, link, is_read, created_at
                        FROM notifications
                        WHERE location_id = ?
                        ORDER BY id DESC LIMIT ?");
$stmt->bind_param('ii', $locationId, $limit);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($r = $res->fetch_assoc()) $items[] = $r;
$stmt->close();

$cs = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE location_id = ? AND is_read = 0");
$cs->bind_param('i', $locationId);
$cs->execute();
$unread = (int)$cs->get_result()->fetch_assoc()['c'];
$cs->close();

jsonResponse(true, 'OK', ['items' => $items, 'unread' => $unread]);
