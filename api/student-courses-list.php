<?php
require_once __DIR__ . '/student-common.php';
// Used by the course page and by admission / student forms
$user = requireAuth();
$conn = getNewLifeConnection();

$activeOnly = !empty($_GET['active']);
$res = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.course_id = c.id) AS student_count
                     FROM courses c" . ($activeOnly ? " WHERE c.is_active = 1" : "") . " ORDER BY c.sort_order, c.id");
$items = $res->fetch_all(MYSQLI_ASSOC);
$res->free();

jsonResponse(true, 'OK', ['items' => $items]);
