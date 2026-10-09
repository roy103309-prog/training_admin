<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
webDelete(getNewLifeConnection(), 'reviews', (int)($in['id'] ?? 0), 'photo_url');
jsonResponse(true, 'Review deleted');
