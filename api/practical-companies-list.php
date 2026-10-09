<?php
require_once __DIR__ . '/practical-common.php';
// Overseas & partner companies, with how many students are linked to each
$user = requireAuth();
$conn = getNewLifeConnection();

$search = nlClean($_GET['search'] ?? '', 100);
$sql = "SELECT c.*, COUNT(o.student_id) AS student_count, COALESCE(SUM(o.overseas_status = 'selected'), 0) AS selected_count
        FROM companies c LEFT JOIN student_overseas o ON o.company_id = c.id";
$params = []; $types = '';
if ($search !== '') {
    $sql .= " WHERE (c.name LIKE ? OR c.country LIKE ? OR c.contact_person LIKE ? OR c.mobile LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like); $types .= 'ssss';
}
$stmt = $conn->prepare($sql . " GROUP BY c.id ORDER BY c.name");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

jsonResponse(true, 'OK', ['items' => $items]);
