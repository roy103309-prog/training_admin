<?php
// Public website endpoint (no login)
require __DIR__ . '/../includes/site-bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['error' => 'Method not allowed'], 405); }
$in = json_input();
$roll = trim((string)($in['rollNumber'] ?? ''));
$reg = trim((string)($in['regNumber'] ?? ''));
if ($roll === '' || $reg === '') { json_response(['error' => 'Roll and registration number are required'], 422); }
$row = db_one('SELECT CrtName, CrtDob, CrtRoll, CrtRegi, CrtAddress, CrtImg FROM old_certificates WHERE CrtRoll = ? AND CrtRegi = ? LIMIT 1', [$roll, $reg]);
if (!$row) { json_response(['error' => 'Certificate not found'], 404); }
$row['CrtImg'] = asset_url($row['CrtImg']);
$row['CrtImgType'] = 'path';
json_response($row);
