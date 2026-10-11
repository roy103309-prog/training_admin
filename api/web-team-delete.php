<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
webDelete(getNewLifeConnection(), 'web_team', (int)($in['id'] ?? 0), 'image');
jsonResponse(true, 'Team member deleted');
