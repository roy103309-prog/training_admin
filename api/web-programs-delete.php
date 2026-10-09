<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
webDelete(getNewLifeConnection(), 'programs', (int)($in['id'] ?? 0), 'image');
jsonResponse(true, 'Program deleted');
