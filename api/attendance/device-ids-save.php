<?php
/**
 * Attendance device ID of each employee. Without an own ID the employee's
 * id is used. Body: { items: [{ user_id, device_id }] } — empty device_id resets to default.
 */
require_once __DIR__ . '/../newlife-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$items = is_array($in['items'] ?? null) ? $in['items'] : [];
if (!$items) jsonResponse(false, 'Nothing to save');

$conn->begin_transaction();
try {
    $set = $conn->prepare("INSERT INTO employee_device_ids (user_id, device_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE device_id = VALUES(device_id)");
    $del = $conn->prepare("DELETE FROM employee_device_ids WHERE user_id = ?");
    foreach ($items as $it) {
        $uid = (int)($it['user_id'] ?? 0);
        if ($uid <= 0) continue;
        $raw = trim((string)($it['device_id'] ?? ''));
        if ($raw === '' || (int)$raw === $uid) {
            $del->bind_param('i', $uid); $del->execute();
            continue;
        }
        if (!ctype_digit($raw) || (int)$raw <= 0) throw new NlUserError('Device ID must be a positive number');
        $device = (int)$raw;
        $set->bind_param('ii', $uid, $device); $set->execute();
    }
    $set->close(); $del->close();

    // Every employee must end up with a different device ID
    $res = $conn->query("SELECT u.name, COALESCE(d.device_id, u.id) AS device_id
                         FROM " . nlCoreTable('users') . " u LEFT JOIN employee_device_ids d ON d.user_id = u.id
                         WHERE u.isemployee = 1");
    $seen = [];
    while ($r = $res->fetch_assoc()) {
        if (isset($seen[$r['device_id']])) {
            throw new NlUserError('Device ID ' . $r['device_id'] . ' is used by both ' . $seen[$r['device_id']] . ' and ' . $r['name']);
        }
        $seen[$r['device_id']] = $r['name'];
    }
    $res->free();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
jsonResponse(true, 'Device IDs saved');
