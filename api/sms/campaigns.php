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
    $sql = "SELECT id, uid, name, template_uid, group_uid, status, total_contacts, total_sent, send_mode, scheduled_at FROM sms_campaigns";
    if ($id !== '') {
        $sql .= " WHERE uid = ? OR id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql .= " ORDER BY id DESC LIMIT 100";
        $result = $conn->query($sql);
    }
    $campaigns = [];
    while ($row = $result->fetch_assoc()) {
        $campaigns[] = [
            'id' => $row['uid'] ?? $row['id'],
            'name' => $row['name'],
            'template_uid' => $row['template_uid'],
            'group_uid' => $row['group_uid'],
            'status' => $row['status'],
            'total_contacts' => $row['total_contacts'],
            'total_sent' => $row['total_sent'],
            'send_mode' => $row['send_mode'],
            'scheduled_at' => $row['scheduled_at']
        ];
    }
    $result->free();
    if ($id !== '' && isset($stmt)) $stmt->close();
    SmsResponse::success('Campaigns retrieved', ['campaigns' => $campaigns]);
}

function handleCreate(mysqli $conn, array $data): void {
    $name = trim($data['name'] ?? '');
    $templateUid = trim($data['template_uid'] ?? '');
    $groupUid = trim($data['group_uid'] ?? '');
    $sendMode = $data['send_mode'] ?? 'Instant';
    $scheduledAt = $data['scheduled_at'] ?? null;

    if (empty($name)) {
        SmsResponse::error('Campaign name is required', 400);
    }

    $uid = SmsService::ensureUid($data['uid'] ?? '', 'campaign');
    $now = SmsService::now();
    $userId = SmsService::userId();

    $stmt = $conn->prepare("INSERT INTO sms_campaigns (uid, name, template_uid, group_uid, status, send_mode, scheduled_at, created_by, created_at) VALUES (?, ?, ?, ?, 'Draft', ?, ?, ?, ?)");
    $stmt->bind_param('ssssssss', $uid, $name, $templateUid, $groupUid, $sendMode, $scheduledAt, $userId, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Campaign created', ['id' => $uid]);
    } else {
        SmsResponse::error('Failed to create campaign', 500);
    }
    $stmt->close();
}

function handleUpdate(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $name = trim($data['name'] ?? '');
    $templateUid = trim($data['template_uid'] ?? '');
    $groupUid = trim($data['group_uid'] ?? '');
    $sendMode = $data['send_mode'] ?? 'Instant';
    $scheduledAt = $data['scheduled_at'] ?? null;
    $status = $data['status'] ?? 'Draft';

    if (empty($id)) {
        SmsResponse::error('Campaign ID is required', 400);
    }

    $stmt = $conn->prepare("UPDATE sms_campaigns SET name = ?, template_uid = ?, group_uid = ?, status = ?, send_mode = ?, scheduled_at = ? WHERE uid = ? OR id = ?");
    $stmt->bind_param('ssssssss', $name, $templateUid, $groupUid, $status, $sendMode, $scheduledAt, $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Campaign updated', ['id' => $id]);
    } else {
        SmsResponse::error('Failed to update campaign', 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if (empty($id)) {
        SmsResponse::error('Campaign ID is required', 400);
    }

    $stmt = $conn->prepare("DELETE FROM sms_campaigns WHERE uid = ? OR id = ?");
    $stmt->bind_param('ss', $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Campaign deleted');
    } else {
        SmsResponse::error('Failed to delete campaign', 500);
    }
    $stmt->close();
}
