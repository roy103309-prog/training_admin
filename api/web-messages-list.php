<?php
require_once __DIR__ . '/web-common.php';
$user = requireAuth();
$conn = getNewLifeConnection();
$items = $conn->query("SELECT * FROM contact_messages ORDER BY id DESC LIMIT 500")->fetch_all(MYSQLI_ASSOC);
$unread = (int)$conn->query("SELECT COUNT(*) AS c FROM contact_messages WHERE is_read = 0")->fetch_assoc()['c'];
jsonResponse(true, 'OK', ['items' => $items, 'unread' => $unread]);
