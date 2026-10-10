<?php
// Public website endpoint (no login)
require __DIR__ . '/../includes/site-bootstrap.php';
header('Access-Control-Allow-Origin: *');
$rows = db_all('SELECT id, title, description, video_url, platform, is_active, sort_order FROM videos WHERE is_active = 1 ORDER BY sort_order, id');
foreach ($rows as &$r) { $r['id'] = (int)$r['id']; $r['is_active'] = (int)$r['is_active']; $r['sort_order'] = (int)$r['sort_order']; }
json_response($rows);
