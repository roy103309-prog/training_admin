<?php
require_once __DIR__ . '/practical-common.php';
// One out-student job for printing its slip again
$user = requireAuth();
$conn = getNewLifeConnection();
jsonResponse(true, 'OK', ['job' => practicalOutFind($conn, (int)($_GET['id'] ?? 0))]);
