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
    $groupUid = trim($data['group_uid'] ?? ($_GET['group_uid'] ?? ''));
    $sql = "SELECT id, uid, name, phone, group_uid, email, status FROM contacts";
    if ($id !== '') {
        $sql .= " WHERE uid = ? OR id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        if ($groupUid !== '') {
            $sql .= " WHERE group_uid = ?";
            $stmt = $conn->prepare($sql . " ORDER BY id DESC LIMIT 500");
            $stmt->bind_param('s', $groupUid);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql .= " ORDER BY id DESC LIMIT 500";
            $result = $conn->query($sql);
        }
    }
    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = [
            'id' => $row['uid'] ?? $row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'group_uid' => $row['group_uid'] ?? '',
            'email' => $row['email'],
            'status' => $row['status']
        ];
    }
    $result->free();
    if (isset($stmt)) $stmt->close();
    SmsResponse::success('Contacts retrieved', ['contacts' => $contacts]);
}

function handleCreate(mysqli $conn, array $data): void {
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $groupUid = trim($data['group_uid'] ?? '');

    if (empty($name) || empty($phone)) {
        SmsResponse::error('Name and phone are required', 400);
    }

    $uid = SmsService::ensureUid($data['uid'] ?? '', 'contact');
    $email = $data['email'] ?? '';
    $now = SmsService::now();

    $stmt = $conn->prepare("INSERT INTO contacts (uid, name, phone, group_uid, email, status, created_at) VALUES (?, ?, ?, ?, ?, 'Active', ?)");
    $stmt->bind_param('ssssss', $uid, $name, $phone, $groupUid, $email, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Contact created', ['id' => $uid]);
    } else {
        SmsResponse::error('Failed to create contact', 500);
    }
    $stmt->close();
}

function handleUpdate(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $groupUid = trim($data['group_uid'] ?? '');

    if (empty($id)) {
        SmsResponse::error('Contact ID is required', 400);
    }

    $stmt = $conn->prepare("UPDATE contacts SET name = ?, phone = ?, group_uid = ? WHERE uid = ? OR id = ?");
    $stmt->bind_param('sssss', $name, $phone, $groupUid, $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Contact updated', ['id' => $id]);
    } else {
        SmsResponse::error('Failed to update contact', 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if (empty($id)) {
        SmsResponse::error('Contact ID is required', 400);
    }

    $stmt = $conn->prepare("DELETE FROM contacts WHERE uid = ? OR id = ?");
    $stmt->bind_param('ss', $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Contact deleted');
    } else {
        SmsResponse::error('Failed to delete contact', 500);
    }
    $stmt->close();
}
