<?php
require_once __DIR__ . '/config.php';

$conn = SmsService::boot();
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$data = SmsService::requestData();

try {
    switch ($method) {
        case 'GET':
            $action = strtolower(trim((string)($data['action'] ?? '')));
            if ($action === 'stats') {
                handleStats($conn, $data);
            } else {
                handleList($conn, $data);
            }
            break;
        default:
            SmsResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}

function buildFilters(array $data): array {
    $where = ' WHERE 1=1';
    $params = [];
    $types = '';

    $status = trim((string)($data['status'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $source = trim((string)($data['source'] ?? ''));
    $search = trim((string)($data['search'] ?? ''));
    $dateFrom = trim((string)($data['date_from'] ?? ''));
    $dateTo = trim((string)($data['date_to'] ?? ''));

    if ($status !== '') {
        $where .= ' AND status = ?';
        $params[] = $status;
        $types .= 's';
    }
    if ($phone !== '') {
        $where .= ' AND phone LIKE ?';
        $params[] = '%' . $phone . '%';
        $types .= 's';
    }
    if ($source !== '') {
        $where .= ' AND source = ?';
        $params[] = $source;
        $types .= 's';
    }
    if ($search !== '') {
        $where .= ' AND (target_name LIKE ? OR message LIKE ? OR phone LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    if ($dateFrom !== '') {
        $where .= ' AND DATE(COALESCE(sent_at, created_at)) >= ?';
        $params[] = $dateFrom;
        $types .= 's';
    }
    if ($dateTo !== '') {
        $where .= ' AND DATE(COALESCE(sent_at, created_at)) <= ?';
        $params[] = $dateTo;
        $types .= 's';
    }

    return [$where, $params, $types];
}

function handleList(mysqli $conn, array $data): void {
    $page = max(1, (int)($data['page'] ?? 1));
    $limit = (int)($data['limit'] ?? 25);
    if ($limit < 1) { $limit = 25; }
    if ($limit > 200) { $limit = 200; }
    $offset = ($page - 1) * $limit;

    list($where, $params, $types) = buildFilters($data);

    $countSql = 'SELECT COUNT(*) AS total FROM sms_logs' . $where;
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) { SmsResponse::error('Failed to prepare count query', 500); }
    if (!empty($params)) { $countStmt->bind_param($types, ...$params); }
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $total = (int)($countRes->fetch_assoc()['total'] ?? 0);
    $countRes->free();
    $countStmt->close();

    $sql = 'SELECT id, uid, request_id, phone, target_name, source, status, template_uid, campaign_uid, message, units, segments, sms_type, language, cost, gateway, provider_msg_id, project_name, sent_at, created_at FROM sms_logs'
        . $where . ' ORDER BY id DESC LIMIT ? OFFSET ?';

    $listParams = $params;
    $listTypes = $types . 'ii';
    $listParams[] = $limit;
    $listParams[] = $offset;

    $stmt = $conn->prepare($sql);
    if (!$stmt) { SmsResponse::error('Failed to prepare query', 500); }
    $stmt->bind_param($listTypes, ...$listParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'id' => $row['uid'] ?? $row['id'],
            'request_id' => $row['request_id'],
            'phone' => $row['phone'],
            'target_name' => $row['target_name'],
            'source' => $row['source'],
            'status' => $row['status'],
            'template_uid' => $row['template_uid'],
            'campaign_uid' => $row['campaign_uid'],
            'message' => $row['message'],
            'units' => (int)$row['units'],
            'segments' => (int)$row['segments'],
            'sms_type' => $row['sms_type'],
            'language' => $row['language'],
            'cost' => $row['cost'],
            'gateway' => $row['gateway'],
            'provider_msg_id' => $row['provider_msg_id'],
            'project_name' => $row['project_name'],
            'sent_at' => $row['sent_at'],
            'created_at' => $row['created_at']
        ];
    }
    $result->free();
    $stmt->close();

    $totalPages = $limit > 0 ? (int)ceil($total / $limit) : 0;

    SmsResponse::success('Logs retrieved', [
        'logs' => $logs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages
        ]
    ]);
}

function handleStats(mysqli $conn, array $data): void {
    list($where, $params, $types) = buildFilters($data);

    $sql = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Sent' THEN 1 ELSE 0 END) AS sent,
            SUM(CASE WHEN status = 'Failed' THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            COALESCE(SUM(units), 0) AS units,
            COALESCE(SUM(cost), 0) AS cost
        FROM sms_logs" . $where;

    $stmt = $conn->prepare($sql);
    if (!$stmt) { SmsResponse::error('Failed to prepare stats query', 500); }
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: [];
    $res->free();
    $stmt->close();

    $sourcesSql = 'SELECT COALESCE(source, \'-\') AS source, COUNT(*) AS cnt FROM sms_logs' . $where . ' GROUP BY source ORDER BY cnt DESC LIMIT 50';
    $sStmt = $conn->prepare($sourcesSql);
    $sources = [];
    if ($sStmt) {
        if (!empty($params)) { $sStmt->bind_param($types, ...$params); }
        $sStmt->execute();
        $sRes = $sStmt->get_result();
        while ($r = $sRes->fetch_assoc()) {
            $sources[] = ['source' => $r['source'], 'count' => (int)$r['cnt']];
        }
        $sRes->free();
        $sStmt->close();
    }

    SmsResponse::success('Stats retrieved', [
        'total' => (int)($row['total'] ?? 0),
        'sent' => (int)($row['sent'] ?? 0),
        'failed' => (int)($row['failed'] ?? 0),
        'pending' => (int)($row['pending'] ?? 0),
        'units' => (int)($row['units'] ?? 0),
        'cost' => (float)($row['cost'] ?? 0),
        'sources' => $sources
    ]);
}
