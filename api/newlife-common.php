<?php
/**
 * Shared helpers of the New Life modules (api/student-*, api/certificate-*, api/web-*,
 * api/practical-* and the New Life files in api/attendance, api/hr, api/settings, api/accounts).
 * Session, login, branch and database are TrainingAdmin's (tenant-config.php, training_XXXXXX).
 */
require_once __DIR__ . '/tenant-config.php';

// Throw on SQL errors; the handler turns any uncaught error into a JSON reply
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validation error thrown inside a transaction; its message is shown to the user
class NlUserError extends RuntimeException {}
SSS
set_exception_handler(function ($e) {
    if ($e instanceof NlUserError) jsonResponse(false, $e->getMessage());
    error_log('[NewLife] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    $message = 'Server error. Please try again.';
    if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {
        $message = 'This record already exists (duplicate value).';
    }
    echo json_encode(['success' => false, 'message' => $message, 'data' => []]);
    exit;
});

function nlRequirePost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method');
}

// JSON request body, falling back to form fields
function nlInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : $_POST;
}

// Trimmed text without control characters (queries use prepared statements)
function nlClean($value, $maxLength = null) {
    if ($value === null || is_array($value)) return '';
    $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$value));
    return $maxLength ? mb_substr($value, 0, $maxLength) : $value;
}

// 'YYYY-MM-DD' or null
function nlDate($value) {
    $value = substr(trim((string)$value), 0, 10);
    if ($value === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : null;
}

function nlFlag($value) {
    return (!empty($value) && $value !== '0' && $value !== 'false') ? 1 : 0;
}

function nlLocationId() {
    return (int)getCurrentLocation();
}

/* ------------------------------------------------------------------
 *  General Settings (app_settings table)
 * ------------------------------------------------------------------ */

function nlSettingDefinitions() {
    static $defs = null;
    if ($defs === null) $defs = require __DIR__ . '/settings/_general-definitions.php';
    return $defs;
}

function nlSetting($key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $res = getNewLifeConnection()->query("SELECT setting_key, setting_value FROM app_settings");
        while ($r = $res->fetch_assoc()) $cache[$r['setting_key']] = $r['setting_value'];
        $res->free();
    }
    if (array_key_exists($key, $cache)) return $cache[$key];
    if ($default !== null) return $default;
    foreach (nlSettingDefinitions() as $group) {
        if (isset($group['fields'][$key])) return $group['fields'][$key]['default'] ?? null;
    }
    return null;
}

/* ------------------------------------------------------------------
 *  Files: uploads/training_XXXXXX/<subfolder>/<file> (the institute's TrainingAdmin upload folder)
 * ------------------------------------------------------------------ */

const NL_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

function nlMediaFolder() {
    return getTenantMediaFolder($_SESSION['tenant_id'] ?? null);
}

function nlUploadFile($file, $subfolder, array $allowedTypes = NL_IMAGE_TYPES, $maxBytes = 10485760) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'message' => 'File is larger than ' . round($maxBytes / 1048576) . ' MB'];
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes, true)) {
        return ['success' => false, 'message' => 'Invalid file type (allowed: ' . implode(', ', $allowedTypes) . ')'];
    }
    if (in_array($ext, NL_IMAGE_TYPES, true) && @getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'message' => 'The file is not a valid image'];
    }

    $relDir = nlMediaFolder() . trim(preg_replace('/[^a-z0-9_-]/i', '', $subfolder), '/') . '/';
    $absDir = dirname(__DIR__) . '/' . $relDir;
    if (!is_dir($absDir)) mkdir($absDir, 0755, true);

    $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $absDir . $name)) {
        return ['success' => false, 'message' => 'Failed to save the uploaded file'];
    }
    return ['success' => true, 'path' => $relDir . $name];
}

// Deletes only files inside this institute's upload folder
function nlDeleteFile($path) {
    $path = (string)$path;
    if ($path === '' || strpos($path, nlMediaFolder()) !== 0 || strpos($path, '..') !== false) return false;
    $abs = dirname(__DIR__) . '/' . $path;
    return is_file($abs) ? @unlink($abs) : false;
}

// Files of a multi-file input (name="field[]"), or of a single input
function nlUploadedFiles($field) {
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'])) return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

// New uploaded image of a single file field, or null when none was chosen
function nlUploadImage($field, $subfolder) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $up = nlUploadFile($_FILES[$field], $subfolder);
    if (!$up['success']) jsonResponse(false, $up['message']);
    return $up['path'];
}
