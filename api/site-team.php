<?php
// Public website endpoint (no login)
require __DIR__ . '/../includes/site-bootstrap.php';
header('Access-Control-Allow-Origin: *');
$rows = db_all("SELECT id, name, phone, email, designation AS role, image, category FROM web_team WHERE is_active = 1 ORDER BY sort_order, id");
foreach ($rows as &$r) { $r['id'] = (int)$r['id']; $r['image'] = asset_url($r['image']); }
json_response($rows);
