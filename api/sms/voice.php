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
    $sql = "SELECT id, uid, name, audio_url, audio_name, group_uid, total_contacts, total_sent, status, scheduled_at, created_at FROM voice_sms_campaigns";
    if ($id !== '') {
        $sql .= " WHERE uid=? OR id=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql .= " ORDER BY id DESC LIMIT 100";
        $result = $conn->query($sql);
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id' => $row['uid'] ?? $row['id'],
            'name' => $row['name'],
            'audio_url' => $row['audio_url'],
            'audio_name' => $row['audio_name'],
            'group_uid' => $row['group_uid'],
            'total_contacts' => (int)$row['total_contacts'],
            'total_sent' => (int)$row['total_sent'],
            'status' => $row['status'],
            'scheduled_at' => $row['scheduled_at'],
            'created_at' => $row['created_at']
        ];
    }
    $result->free();
    if ($id !== '' && isset($stmt)) $stmt->close();
    SmsResponse::success('Voice campaigns retrieved', ['campaigns' => $rows]);
}

function handleCreate(mysqli $conn, array $data): void {
    $name = trim($data['name'] ?? '');
    $audioUrl = trim($data['audio_url'] ?? '');
    $audioName = trim($data['audio_name'] ?? '');
    $groupUid = trim($data['group_uid'] ?? '');
    $scheduledAt = $data['scheduled_at'] ?? null;
    $status = $data['status'] ?? 'Draft';

    if ($name === '') SmsResponse::error('Name is required', 400);

    $uid = SmsService::ensureUid($data['uid'] ?? '', 'voice');
    $now = SmsService::now();
    $userId = SmsService::userId();

    $stmt = $conn->prepare("INSERT INTO voice_sms_campaigns (uid, name, audio_url, audio_name, group_uid, status, scheduled_at, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssssss', $uid, $name, $audioUrl, $audioName, $groupUid, $status, $scheduledAt, $userId, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Voice campaign created', ['id' => $uid]);
    } else {
        SmsResponse::error('Failed to create: ' . $stmt->error, 500);
    }
    $stmt->close();
}

function handleUpdate(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $name = trim($data['name'] ?? '');
    $audioUrl = trim($data['audio_url'] ?? '');
    $audioName = trim($data['audio_name'] ?? '');
    $groupUid = trim($data['group_uid'] ?? '');
    $status = $data['status'] ?? 'Draft';
    $scheduledAt = $data['scheduled_at'] ?? null;

    if ($id === '') SmsResponse::error('Campaign ID is required', 400);

    $stmt = $conn->prepare("UPDATE voice_sms_campaigns SET name=?, audio_url=?, audio_name=?, group_uid=?, status=?, scheduled_at=? WHERE uid=? OR id=?");
    $stmt->bind_param('ssssssss', $name, $audioUrl, $audioName, $groupUid, $status, $scheduledAt, $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Voice campaign updated', ['id' => $id]);
    } else {
        SmsResponse::error('Failed to update', 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if ($id === '') SmsResponse::error('Campaign ID is required', 400);
    $stmt = $conn->prepare("DELETE FROM voice_sms_campaigns WHERE uid=? OR id=?");
    $stmt->bind_param('ss', $id, $id);
    if ($stmt->execute()) SmsResponse::success('Voice campaign deleted');
    else SmsResponse::error('Failed to delete', 500);
    $stmt->close();
}
