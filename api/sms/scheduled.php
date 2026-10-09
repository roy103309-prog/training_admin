<?php
require_once __DIR__ . '/config.php';

$conn = SmsService::boot();
SmsService::requireMethod(['GET']);

try {
    $sql = "SELECT uid, name, template_uid, group_uid, total_contacts, total_sent, send_mode, status, scheduled_at, created_at
            FROM sms_campaigns
            WHERE send_mode='Scheduled' OR status='Scheduled'
            ORDER BY scheduled_at ASC, id DESC LIMIT 200";
    $result = $conn->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id' => $row['uid'],
            'name' => $row['name'],
            'template_uid' => $row['template_uid'],
            'group_uid' => $row['group_uid'],
            'total_contacts' => (int)$row['total_contacts'],
            'total_sent' => (int)$row['total_sent'],
            'send_mode' => $row['send_mode'],
            'status' => $row['status'],
            'scheduled_at' => $row['scheduled_at'],
            'created_at' => $row['created_at']
        ];
    }
    $result->free();
    SmsResponse::success('Scheduled campaigns retrieved', ['campaigns' => $rows]);
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}
