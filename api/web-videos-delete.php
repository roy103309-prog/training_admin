<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
webDelete(getNewLifeConnection(), 'videos', (int)($in['id'] ?? 0));
jsonResponse(true, 'Video deleted');
