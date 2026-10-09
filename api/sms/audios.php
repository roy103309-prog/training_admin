<?php
/**
 * SMS Voice Audio Library API
 * - GET    : list audios (or single by id)
 * - POST   : upload audio (multipart/form-data: name, file)
 * - DELETE : delete audio (json: {id})
 *
 * Files are stored in tenant uploads folder under "sms_audio/" subfolder.
 */
require_once __DIR__ . '/config.php';

$conn   = SmsService::boot();
$method = strtoupper($_SERVER['REQUEST_METHOD']);


try {
    switch ($method) {
        case 'GET':
            handleList($conn);
            break;
        case 'POST':
            handleUpload($conn);
            break;
        case 'DELETE':
            handleDelete($conn);
            break;
        default:
            SmsResponse::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}

// sms_audios table is provisioned at registration via database/tenant_schema.sql.

function handleList(mysqli $conn): void {
    $id = trim($_GET['id'] ?? '');
    if ($id !== '') {
        $stmt = $conn->prepare("SELECT id, uid, name, file_url, file_name, file_size, mime_type, created_at FROM sms_audios WHERE uid=? OR id=? LIMIT 1");
        $stmt->bind_param('ss', $id, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT id, uid, name, file_url, file_name, file_size, mime_type, created_at FROM sms_audios ORDER BY id DESC LIMIT 200");
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'id'        => $row['uid'] ?? $row['id'],
            'name'      => $row['name'],
            'file_url'  => $row['file_url'],
            'file_name' => $row['file_name'],
            'file_size' => (int)$row['file_size'],
            'mime_type' => $row['mime_type'],
            'created_at'=> $row['created_at']
        ];
    }
    $result->free();
    if (isset($stmt)) $stmt->close();
    SmsResponse::success('Audios retrieved', ['audios' => $rows]);
}

function handleUpload(mysqli $conn): void {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') SmsResponse::error('Name is required', 400);
    if (!isset($_FILES['file'])) SmsResponse::error('Audio file is required', 400);

    $allowed = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'webm'];
    $upload  = uploadFile($_FILES['file'], 'sms_audio', $allowed);

    if (!$upload['success']) {
        SmsResponse::error($upload['message'] ?? 'Upload failed', 400);
    }

    $uid       = SmsService::ensureUid('', 'aud');
    $fileUrl   = $upload['path'];
    $fileName  = $_FILES['file']['name'];
    $fileSize  = (int)$_FILES['file']['size'];
    $mime      = $_FILES['file']['type'] ?? null;
    $userId    = SmsService::userId();
    $now       = SmsService::now();

    $stmt = $conn->prepare("INSERT INTO sms_audios (uid, name, file_url, file_name, file_size, mime_type, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssisss', $uid, $name, $fileUrl, $fileName, $fileSize, $mime, $userId, $now);

    if ($stmt->execute()) {
        SmsResponse::success('Audio uploaded', [
            'id'        => $uid,
            'file_url'  => $fileUrl,
            'file_name' => $fileName
        ]);
    } else {
        // Cleanup file on DB failure
        deleteFile($fileUrl);
        SmsResponse::error('Failed to save audio: ' . $stmt->error, 500);
    }
    $stmt->close();
}

function handleDelete(mysqli $conn): void {
    $data = SmsService::requestData();
    $id   = trim($data['id'] ?? '');
    if ($id === '') SmsResponse::error('Audio ID is required', 400);

    // Fetch path first to delete from disk
    $stmt = $conn->prepare("SELECT file_url FROM sms_audios WHERE uid=? OR id=? LIMIT 1");
    $stmt->bind_param('ss', $id, $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $res->free();
    $stmt->close();

    if (!$row) SmsResponse::error('Audio not found', 404);

    $stmt = $conn->prepare("DELETE FROM sms_audios WHERE uid=? OR id=?");
    $stmt->bind_param('ss', $id, $id);
    if ($stmt->execute()) {
        if (!empty($row['file_url'])) deleteFile($row['file_url']);
        SmsResponse::success('Audio deleted');
    } else {
        SmsResponse::error('Failed to delete', 500);
    }
    $stmt->close();
}
