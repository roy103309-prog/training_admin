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
    $sql = "SELECT id, uid, name, sms_count, price, bonus_sms, status, featured, description FROM sms_packages";
    if ($id !== '') {
        $sql .= " WHERE uid = ? OR id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql .= " WHERE status = 'Active' ORDER BY featured DESC, price ASC";
        $result = $conn->query($sql);
    }
    $packages = [];
    while ($row = $result->fetch_assoc()) {
        $packages[] = [
            'id' => $row['uid'] ?? $row['id'],
            'name' => $row['name'],
            'sms_count' => (int)$row['sms_count'],
            'price' => (float)$row['price'],
            'bonus_sms' => (int)$row['bonus_sms'],
            'status' => $row['status'],
            'featured' => (int)$row['featured'],
            'description' => $row['description']
        ];
    }
    $result->free();
    if ($id !== '' && isset($stmt)) $stmt->close();
    SmsResponse::success('Packages retrieved', ['packages' => $packages]);
}

function handleCreate(mysqli $conn, array $data): void {
    $name = trim($data['name'] ?? '');
    $smsCount = (int)($data['sms_count'] ?? 0);
    $price = (float)($data['price'] ?? 0);
    $bonus = (int)($data['bonus_sms'] ?? 0);
    $featured = !empty($data['featured']) ? 1 : 0;
    $description = $data['description'] ?? '';

    if ($name === '' || $smsCount <= 0) {
        SmsResponse::error('Name and sms count are required', 400);
    }

    $uid = SmsService::ensureUid($data['uid'] ?? '', 'pkg');
    $now = SmsService::now();

    $stmt = $conn->prepare("INSERT INTO sms_packages (uid, name, sms_count, price, bonus_sms, status, featured, description, created_at) VALUES (?, ?, ?, ?, ?, 'Active', ?, ?, ?)");
    $stmt->bind_param('ssidiiss', $uid, $name, $smsCount, $price, $bonus, $featured, $description, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Package created', ['id' => $uid]);
    } else {
        SmsResponse::error('Failed to create package: ' . $stmt->error, 500);
    }
    $stmt->close();
}

function handleUpdate(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    $name = trim($data['name'] ?? '');
    $smsCount = (int)($data['sms_count'] ?? 0);
    $price = (float)($data['price'] ?? 0);
    $bonus = (int)($data['bonus_sms'] ?? 0);
    $featured = !empty($data['featured']) ? 1 : 0;
    $description = $data['description'] ?? '';

    if ($id === '') {
        SmsResponse::error('Package ID is required', 400);
    }

    $stmt = $conn->prepare("UPDATE sms_packages SET name=?, sms_count=?, price=?, bonus_sms=?, featured=?, description=?, updated_at=NOW() WHERE uid=? OR id=?");
    $stmt->bind_param('sidiisss', $name, $smsCount, $price, $bonus, $featured, $description, $id, $id);

    if ($stmt->execute()) {
        SmsResponse::success('Package updated', ['id' => $id]);
    } else {
        SmsResponse::error('Failed to update package', 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn, array $data): void {
    $id = trim($data['id'] ?? '');
    if ($id === '') SmsResponse::error('Package ID is required', 400);
    $stmt = $conn->prepare("DELETE FROM sms_packages WHERE uid=? OR id=?");
    $stmt->bind_param('ss', $id, $id);
    if ($stmt->execute()) {
        SmsResponse::success('Package deleted');
    } else {
        SmsResponse::error('Failed to delete package', 500);
    }
    $stmt->close();
}
