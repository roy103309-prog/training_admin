<?php
require_once __DIR__ . '/config.php';

$conn = SmsService::boot();
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$data = SmsService::requestData();

try {
    switch ($method) {
        case 'GET':
            handleGet($conn);
            break;
        case 'POST':
        case 'PUT':
            handleUpdate($conn, $data);
            break;
        default:
            SmsResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}

function handleGet(mysqli $conn): void {
    $settings = SmsService::getSettings();
    $balance = SmsService::getBalance();
    SmsResponse::success('Settings loaded', [
        'settings' => $settings,
        'balance' => $balance
    ]);
}

function handleUpdate(mysqli $conn, array $data): void {
    $gatewayName = $data['gateway_name'] ?? 'SMS_GATEWAY';
    $smsRate = (float)($data['sms_rate'] ?? 0.25);
    $senderId = $data['sender_id'] ?? '';
    $apiKey = $data['api_key'] ?? '';
    $apiSecret = $data['api_secret'] ?? '';

    $now = SmsService::now();

    // Upsert settings
    $conn->query("DELETE FROM sms_settings WHERE id = 1");

    $stmt = $conn->prepare(
        "INSERT INTO sms_settings (id, gateway_name, sms_rate, sender_id, api_key, api_secret, status, updated_at) VALUES (1, ?, ?, ?, ?, ?, 'Active', ?)"
    );
    $stmt->bind_param('sdsss', $gatewayName, $smsRate, $senderId, $apiKey, $apiSecret, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Settings updated', SmsService::getSettings());
    } else {
        SmsResponse::error('Failed to update settings', 500);
    }
    $stmt->close();
}
