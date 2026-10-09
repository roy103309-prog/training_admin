<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
webDelete(getNewLifeConnection(), 'sliders', (int)($in['id'] ?? 0), 'image_url');
jsonResponse(true, 'Slide deleted');
