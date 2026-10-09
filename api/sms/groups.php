<?php
require_once __DIR__ . '/config.php';

$conn = SmsService::boot();
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$data = SmsService::requestData();

try {
    switch ($method) {
        case 'GET':
            handleList($conn, $data);
            break;
        case 'POST':
            handleCreate($conn, $data);
            break;
        case 'PUT':
            handleUpdate($conn, $data);
            break;
        case 'DELETE':
            handleDelete($conn, $data);
            break;
        default:
            SmsResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}

function handleList(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $sql = "SELECT id, uid, name, type, description, status FROM contact_groups";
    if ($id !== '') {
        $sql .= " WHERE uid = ? OR id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
    }
    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = [
            'id' => $row['uid'] ?? $row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'description' => $row['description'],
            'status' => $row['status']
        ];
    }
    $result->free();
    if ($id !== '' && isset($stmt)) $stmt->close();
    SmsResponse::success('Groups retrieved', ['groups' => $groups]);
}

function handleCreate(mysqli $conn, array $data): void {
    $name = trim($data['name'] ?? '');
    $type = $data['type'] ?? 'Manual';
    $description = $data['description'] ?? '';

    if (empty($name)) {
        SmsResponse::error('Group name is required', 400);
    }

    $uid = SmsService::ensureUid($data['uid'] ?? '', 'group');
    $now = SmsService::now();

    $stmt = $conn->prepare("INSERT INTO contact_groups (uid, name, type, description, status, created_at) VALUES (?, ?, ?, ?, 'Active', ?)");
    $stmt->bind_param('sssss', $uid, $name, $type, $description, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Group created', ['id' => $uid]);
    } else {
        SmsResponse::error('Failed to create group', 500);
    }
    $stmt->close();
}

function handleUpdate(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $name = trim($data['name'] ?? '');
    $type = $data['type'] ?? 'Manual';
    $description = $data['description'] ?? '';

    if (empty($id)) {
        SmsResponse::error('Group ID is required', 400);
    }

    $stmt = $conn->prepare("UPDATE contact_groups SET name = ?, type = ?, description = ? WHERE uid = ? OR id = ?");
    $stmt->bind_param('sssss', $name, $type, $description, $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Group updated', ['id' => $id]);
    } else {
        SmsResponse::error('Failed to update group', 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if (empty($id)) {
        SmsResponse::error('Group ID is required', 400);
    }

    $stmt = $conn->prepare("DELETE FROM contact_groups WHERE uid = ? OR id = ?");
    $stmt->bind_param('ss', $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Group deleted');
    } else {
        SmsResponse::error('Failed to delete group', 500);
    }
    $stmt->close();
}
