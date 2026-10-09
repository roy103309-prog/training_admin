<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_helpers/SmsTemplateEngine.php';

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
    $id = trim((string)($data['id'] ?? ''));
    $search = trim((string)($data['search'] ?? ''));
    $status = trim((string)($data['status'] ?? ''));

    $action = strtolower(trim((string)($data['action'] ?? '')));
    if ($action === 'variables') {
        handleVariablesCatalog($data);
        return;
    }
    if ($action === 'types') {
        SmsResponse::success('Template types retrieved', ['types' => SmsTemplateEngine::typesList()]);
    }

    $isStaticFilter = $data['is_static'] ?? null;

    $sql = "SELECT id, uid, name, type, language, content, variables, is_static, status, created_at FROM sms_templates WHERE 1=1";
    $params = [];
    $types = '';

    if ($id !== '') {
        $sql .= " AND (uid = ? OR id = ?)";
        $params[] = $id;
        $params[] = $id;
        $types .= 'ss';
    }
    if ($status !== '') {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    }
    if ($isStaticFilter !== null && $isStaticFilter !== '') {
        $sql .= " AND is_static = ?";
        $params[] = (int)((string)$isStaticFilter === '1' || strtolower((string)$isStaticFilter) === 'true' ? 1 : 0);
        $types .= 'i';
    }
    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR content LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }

    $sql .= " ORDER BY id DESC LIMIT 100";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        SmsResponse::error('Failed to prepare query', 500);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = [
            'id' => $row['uid'] ?? $row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'language' => $row['language'],
            'status' => $row['status'],
            'content' => $row['content'],
            'variables' => decodeVariables($row['variables'] ?? null),
            'is_static' => (int)($row['is_static'] ?? 0),
            'created_at' => $row['created_at']
        ];
    }
    $result->free();
    $stmt->close();

    SmsResponse::success('Templates retrieved', ['templates' => $templates]);
}

function handleCreate(mysqli $conn, array $data): void {
    $name = trim($data['name'] ?? '');
    $type = trim($data['type'] ?? '');
    $language = trim($data['language'] ?? 'Bangla');
    $content = trim($data['content'] ?? '');
    $isStatic = !empty($data['is_static']) ? 1 : 0;

    if (empty($name) || empty($content)) {
        SmsResponse::error('Name and content are required', 400);
    }
    if ($isStatic) {
        $type = '';
        $variables = [];
    } else {
        if (empty($type)) {
            SmsResponse::error('Type is required for non-static templates', 400);
        }
        if (!isset(SmsTemplateEngine::typeCatalog()[$type])) {
            SmsResponse::error('Invalid SMS type', 400);
        }
        $variables = SmsTemplateEngine::extractVariables($content);
    }

    $uid = SmsService::ensureUid($data['uid'] ?? '', 'template');
    $variablesJson = json_encode($variables, JSON_UNESCAPED_UNICODE);
    $now = SmsService::now();
    $userId = SmsService::userId();

    $stmt = $conn->prepare(
        "INSERT INTO sms_templates (uid, name, type, language, content, variables, is_static, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)"
    );
    $stmt->bind_param('ssssssiss', $uid, $name, $type, $language, $content, $variablesJson, $isStatic, $userId, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Template created', ['id' => $uid, 'variables' => $variables]);
    } else {
        SmsResponse::error('Failed to create template', 500);
    }
    $stmt->close();
}

function handleUpdate(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $name = trim($data['name'] ?? '');
    $content = trim($data['content'] ?? '');
    $type = trim($data['type'] ?? '');
    $language = trim($data['language'] ?? 'Bangla');
    $isStatic = !empty($data['is_static']) ? 1 : 0;

    if (empty($id)) {
        SmsResponse::error('Template ID is required', 400);
    }
    if ($name === '' || $content === '') {
        SmsResponse::error('Name and content are required', 400);
    }
    if ($isStatic) {
        $type = '';
        $variables = [];
    } else {
        if ($type === '') {
            SmsResponse::error('Type is required for non-static templates', 400);
        }
        if (!isset(SmsTemplateEngine::typeCatalog()[$type])) {
            SmsResponse::error('Invalid SMS type', 400);
        }
        $variables = SmsTemplateEngine::extractVariables($content);
    }

    $now = SmsService::now();
    $variablesJson = json_encode($variables, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("UPDATE sms_templates SET name = ?, type = ?, language = ?, content = ?, variables = ?, is_static = ?, updated_at = ? WHERE uid = ? OR id = ?");
    $stmt->bind_param('sssssisss', $name, $type, $language, $content, $variablesJson, $isStatic, $now, $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Template updated', ['id' => $id, 'variables' => $variables]);
    } else {
        SmsResponse::error('Failed to update template', 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if (empty($id)) {
        SmsResponse::error('Template ID is required', 400);
    }

    $stmt = $conn->prepare("DELETE FROM sms_templates WHERE uid = ? OR id = ?");
    $stmt->bind_param('ss', $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Template deleted');
    } else {
        SmsResponse::error('Failed to delete template', 500);
    }
    $stmt->close();
}

function decodeVariables($raw): array {
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function handleVariablesCatalog(array $data): void {
    $type = strtolower(trim((string)($data['type'] ?? '')));
    $catalog = SmsTemplateEngine::typeCatalog();
    if ($type !== '' && isset($catalog[$type])) {
        SmsResponse::success('Variables retrieved', [
            'type' => $type,
            'entity_type' => $catalog[$type]['entity_type'],
            'variables' => $catalog[$type]['variables'],
            'system' => array_keys(SmsTemplateEngine::systemVars())
        ]);
    }
    SmsResponse::success('Variables retrieved', [
        'types' => SmsTemplateEngine::typesList(),
        'system' => array_keys(SmsTemplateEngine::systemVars())
    ]);
}
