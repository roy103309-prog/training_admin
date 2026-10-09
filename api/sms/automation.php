<?php
require_once __DIR__ . '/config.php';

$conn = SmsService::boot();
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$data = SmsService::requestData();

try {
    switch ($method) {
        case 'GET':    handleList($conn, $data); break;
        case 'POST':   handleCreate($conn, $data); break;
        case 'PUT':    handleUpdate($conn, $data); break;
        case 'DELETE': handleDelete($conn, $data); break;
        default: SmsResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}

function handleList(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $sql = "SELECT id, uid, name, event, template_uid, delay_minutes, status, description FROM sms_automation_rules";
    if ($id !== '') {
        $sql .= " WHERE uid=? OR id=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql .= " ORDER BY id DESC";
        $result = $conn->query($sql);
    }
    $rules = [];
    while ($row = $result->fetch_assoc()) {
        $rules[] = [
            'id' => $row['uid'] ?? $row['id'],
            'name' => $row['name'],
            'event' => $row['event'],
            'template_uid' => $row['template_uid'],
            'delay_minutes' => (int)$row['delay_minutes'],
            'status' => $row['status'],
            'description' => $row['description']
        ];
    }
    $result->free();
    if ($id !== '' && isset($stmt)) $stmt->close();
    SmsResponse::success('Rules retrieved', ['rules' => $rules]);
}

function handleCreate(mysqli $conn, array $data): void {
    $name = trim($data['name'] ?? '');
    $event = trim($data['event'] ?? '');
    $templateUid = trim($data['template_uid'] ?? '');
    $delay = (int)($data['delay_minutes'] ?? 0);
    $description = $data['description'] ?? '';

    if ($name === '' || $event === '') SmsResponse::error('Name and event are required', 400);

    $uid = SmsService::ensureUid($data['uid'] ?? '', 'rule');
    $now = SmsService::now();
    $userId = SmsService::userId();

    $stmt = $conn->prepare("INSERT INTO sms_automation_rules (uid, name, event, template_uid, delay_minutes, status, description, created_by, created_at) VALUES (?, ?, ?, ?, ?, 'Active', ?, ?, ?)");
    $stmt->bind_param('ssssisss', $uid, $name, $event, $templateUid, $delay, $description, $userId, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Rule created', ['id' => $uid]);
    } else {
        SmsResponse::error('Failed to create rule: ' . $stmt->error, 500);
    }
    $stmt->close();
}

function handleUpdate(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $name = trim($data['name'] ?? '');
    $event = trim($data['event'] ?? '');
    $templateUid = trim($data['template_uid'] ?? '');
    $delay = (int)($data['delay_minutes'] ?? 0);
    $status = $data['status'] ?? 'Active';
    $description = $data['description'] ?? '';

    if ($id === '') SmsResponse::error('Rule ID is required', 400);

    $stmt = $conn->prepare("UPDATE sms_automation_rules SET name=?, event=?, template_uid=?, delay_minutes=?, status=?, description=? WHERE uid=? OR id=?");
    $stmt->bind_param('sssissss', $name, $event, $templateUid, $delay, $status, $description, $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Rule updated', ['id' => $id]);
    } else {
        SmsResponse::error('Failed to update rule', 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if ($id === '') SmsResponse::error('Rule ID is required', 400);
    $stmt = $conn->prepare("DELETE FROM sms_automation_rules WHERE uid=? OR id=?");
    $stmt->bind_param('ss', $id, $id);
    if ($stmt->execute()) {
        SmsResponse::success('Rule deleted');
    } else {
        SmsResponse::error('Failed to delete rule', 500);
    }
    $stmt->close();
}
