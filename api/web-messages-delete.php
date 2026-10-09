<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
webDelete(getNewLifeConnection(), 'contact_messages', (int)($in['id'] ?? 0));
jsonResponse(true, 'Message deleted');
