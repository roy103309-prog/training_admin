<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/PortalSms.php';

$conn = SmsService::boot();
SmsService::requireMethod(['GET']);

try {
    $tenantId = (string)($_GET['tenant_id'] ?? SmsService::tenantId());
    if ($tenantId === '' || $tenantId === '0') {
        SmsResponse::error('No tenant_id available in session', 400);
    }
    $portal = PortalSms::instance();
    if (!$portal->isEnabled()) {
        SmsResponse::error('Family Portal SMS is disabled. Update api/config/portal.php', 500);
    }
    $balance = $portal->balance($tenantId);
    if (empty($balance['ok'])) {
        SmsResponse::error($balance['data']['message'] ?? 'Failed to load portal balance', $balance['http'] ?: 502);
    }
    SmsResponse::success('Balance loaded', $balance['data']['data'] ?? $balance['data']);
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}
