<?php
require_once __DIR__ . '/practical-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();
$in = nlInput();

/**
 * One category:             { id?, name, fee, max_jobs, is_active, sort_order }
 * All rows of the page:     { items: [ {...}, ... ] }   ("Save Pricing & Limits")
 * max_jobs 0 = no limit.
 */
function practicalCategoryFields(array $c) {
    $name = nlClean($c['name'] ?? '', 120);
    if ($name === '') throw new NlUserError('Job category name is required');
    return [
        'id' => (int)($c['id'] ?? 0),
        'name' => $name,
        'fee' => round(max(0, (float)($c['fee'] ?? 0)), 2),
        'max_jobs' => max(0, (int)($c['max_jobs'] ?? 0)),
        'is_active' => nlFlag($c['is_active'] ?? 1),
        'sort_order' => (int)($c['sort_order'] ?? 0),
    ];
}

function practicalCategoryWrite(mysqli $conn, array $f) {
    if ($f['id'] > 0) {
        $stmt = $conn->prepare("UPDATE job_categories SET name = ?, fee = ?, max_jobs = ?, is_active = ?, sort_order = ? WHERE id = ?");
        $stmt->bind_param('sdiiii', $f['name'], $f['fee'], $f['max_jobs'], $f['is_active'], $f['sort_order'], $f['id']);
        $stmt->execute(); $stmt->close();
        return $f['id'];
    }
    $stmt = $conn->prepare("INSERT INTO job_categories (name, fee, max_jobs, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sdiii', $f['name'], $f['fee'], $f['max_jobs'], $f['is_active'], $f['sort_order']);
    $stmt->execute();
    $id = (int)$conn->insert_id;
    $stmt->close();
    return $id;
}

if (isset($in['items'])) {
    if (!is_array($in['items']) || !$in['items']) jsonResponse(false, 'Nothing to save');
    $rows = array_map('practicalCategoryFields', array_values($in['items']));
    $names = array_map('mb_strtolower', array_column($rows, 'name'));
    if (count($names) !== count(array_unique($names))) jsonResponse(false, 'Two categories have the same name');
    $conn->begin_transaction();
    try {
        foreach ($rows as $f) practicalCategoryWrite($conn, $f);
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
    jsonResponse(true, 'Pricing & limits saved');
}

$f = practicalCategoryFields($in);
$id = practicalCategoryWrite($conn, $f);
jsonResponse(true, $f['id'] > 0 ? 'Job category updated' : 'Job category added', ['id' => $id]);
