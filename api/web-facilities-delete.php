<?php
require_once __DIR__ . '/web-common.php';
nlRequirePost();
$user = requireAuth();
$in = nlInput();
// nlDeleteFile() only removes uploaded files, never the original assets/images/site-facility-* pictures
webDelete(getNewLifeConnection(), 'web_facilities', (int)($in['id'] ?? 0), 'image');
jsonResponse(true, 'Facility deleted');
