<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
$id = (int)($in['id'] ?? 0);
$read = nlFlag($in['is_read'] ?? 1);
$stmt = getNewLifeConnection()->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
$stmt->bind_param('ii', $read, $id);
$stmt->execute();
$stmt->close();
jsonResponse(true, $read ? 'Marked as read' : 'Marked as unread');
