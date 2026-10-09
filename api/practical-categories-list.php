<?php
require_once __DIR__ . '/practical-common.php';
// Used by Job Price & Limits and by the job entry form
$user = requireAuth();
$conn = getNewLifeConnection();

$activeOnly = !empty($_GET['active']);
$res = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM jobs j WHERE j.category_id = c.id) AS job_count
                     FROM job_categories c" . ($activeOnly ? " WHERE c.is_active = 1" : "") . " ORDER BY c.sort_order, c.id");
$items = $res->fetch_all(MYSQLI_ASSOC);
$res->free();

jsonResponse(true, 'OK', ['items' => $items]);
