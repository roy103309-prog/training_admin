<?php
require_once __DIR__ . '/config.php';

$conn = SmsService::boot();
SmsService::requireMethod(['GET']);
$data = SmsService::requestData();

try {
    $from = trim($data['from'] ?? '');
    $to = trim($data['to'] ?? '');

    $where = "1=1";
    $params = [];
    $types = '';
    if ($from !== '') { $where .= " AND DATE(created_at) >= ?"; $params[] = $from; $types .= 's'; }
    if ($to !== '')   { $where .= " AND DATE(created_at) <= ?"; $params[] = $to;   $types .= 's'; }

    // Summary
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status='Sent' THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status='Failed' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending,
                COALESCE(SUM(units),0) AS units,
                COALESCE(SUM(cost),0) AS cost
            FROM sms_logs WHERE $where";
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    // Daily trend (last 30 days in range)
    $sqlDaily = "SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(cost),0) AS cost
                 FROM sms_logs WHERE $where GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 30";
    $stmt = $conn->prepare($sqlDaily);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $daily = [];
    while ($row = $res->fetch_assoc()) {
        $daily[] = ['date' => $row['d'], 'count' => (int)$row['c'], 'cost' => (float)$row['cost']];
    }
    $stmt->close();

    // By source breakdown
    $sqlSource = "SELECT COALESCE(source,'-') AS source, COUNT(*) AS c FROM sms_logs WHERE $where GROUP BY source ORDER BY c DESC LIMIT 20";
    $stmt = $conn->prepare($sqlSource);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $bySource = [];
    while ($row = $res->fetch_assoc()) {
        $bySource[] = ['source' => $row['source'], 'count' => (int)$row['c']];
    }
    $stmt->close();

    SmsResponse::success('Report loaded', [
        'summary' => [
            'total' => (int)($summary['total'] ?? 0),
            'sent' => (int)($summary['sent'] ?? 0),
            'failed' => (int)($summary['failed'] ?? 0),
            'pending' => (int)($summary['pending'] ?? 0),
            'units' => (int)($summary['units'] ?? 0),
            'cost' => (float)($summary['cost'] ?? 0),
        ],
        'daily' => $daily,
        'by_source' => $bySource
    ]);
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}
