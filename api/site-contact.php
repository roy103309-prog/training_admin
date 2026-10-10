<?php
// Public website endpoint (no login)
require __DIR__ . '/../includes/site-bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['success' => false, 'error' => 'Method not allowed'], 405); }
$in = json_input();
$name = trim((string)($in['name'] ?? ''));
$email = trim((string)($in['email'] ?? ''));
$phone = trim((string)($in['phone'] ?? ''));
$message = trim((string)($in['message'] ?? ''));
if ($name === '' || $message === '' || mb_strlen($name) > 150 || mb_strlen($message) > 5000) {
    json_response(['success' => false, 'error' => 'Invalid input'], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'error' => 'Invalid email'], 422);
}
db_exec('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)', [$name, mb_substr($email, 0, 190), mb_substr($phone, 0, 50), $message]);
json_response(['success' => true]);
