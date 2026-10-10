<?php
// Public website endpoint (no login)
require __DIR__ . '/../includes/site-bootstrap.php';
header('Access-Control-Allow-Origin: *');
$rows = db_all('SELECT id, student_name, trade, country, message, rating, photo_url, is_active, sort_order FROM reviews WHERE is_active = 1 ORDER BY sort_order, id');
foreach ($rows as &$r) { foreach (['id', 'rating', 'is_active', 'sort_order'] as $k) { $r[$k] = (int)$r[$k]; } $r['photo_url'] = asset_url($r['photo_url']); }
json_response($rows);
