<?php
/**
 * Employees of the current branch (TrainingAdmin users with isemployee = 1)
 * with their New Life extras: attendance device ID and document count.
 */
require_once __DIR__ . '/../newlife-common.php';
$user = requireAuth();
$locationId = nlLocationId();
$conn = getNewLifeConnection();

$stmt = $conn->prepare("SELECT u.id, u.employee_code, u.name, u.mobile, u.isactive,
                               COALESCE(d.device_id, u.id) AS device_id, (d.device_id IS NOT NULL) AS device_custom,
                               (SELECT COUNT(*) FROM employee_documents ed WHERE ed.user_id = u.id) AS doc_count
                        FROM " . nlCoreTable('users') . " u
                        LEFT JOIN employee_device_ids d ON d.user_id = u.id
                        WHERE u.isemployee = 1 AND (u.location_id = ? OR u.location_id IS NULL)
                        ORDER BY u.name");
$stmt->bind_param('i', $locationId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

jsonResponse(true, 'OK', ['items' => $items]);
