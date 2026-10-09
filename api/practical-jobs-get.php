<?php
require_once __DIR__ . '/practical-common.php';
// One job for printing its token again
$user = requireAuth();
$conn = getNewLifeConnection();
jsonResponse(true, 'OK', ['job' => practicalJobForToken($conn, (int)($_GET['id'] ?? 0))]);
