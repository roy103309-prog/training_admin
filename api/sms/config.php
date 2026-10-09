<?php
/**
 * SMS Module Configuration
 * Integrates with existing TrainingAdmin tenant-config.php
 */

require_once __DIR__ . '/../tenant-config.php';

// SMS Response wrapper (uses existing jsonResponse from tenant-config.php)
class SmsResponse {
    public static function success($message = "Success", $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            "success" => true,
            "message" => $message,
            "data" => $data
        ]);
        exit;
    }

    public static function error($message = "Error", $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        $response = [
            "success" => false,
            "message" => $message
        ];
        if ($errors !== null) {
            $response["errors"] = $errors;
        }
        echo json_encode($response);
        exit;
    }
}

// SMS Validator
class SmsValidator {
    public static function validate($data, $rules) {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleArray = explode('|', $rule);

            foreach ($ruleArray as $r) {
                if ($r === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
                    break;
                }

                if (strpos($r, 'min:') === 0 && !empty($value)) {
                    $min = (int)substr($r, 4);
                    if (strlen($value) < $min) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least $min characters";
                        break;
                    }
                }

                if (strpos($r, 'max:') === 0 && !empty($value)) {
                    $max = (int)substr($r, 4);
                    if (strlen($value) > $max) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed $max characters";
                        break;
                    }
                }

                if ($r === 'email' && !empty($value)) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "Invalid email format";
                        break;
                    }
                }

                if ($r === 'numeric' && !empty($value)) {
                    if (!is_numeric($value)) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be numeric";
                        break;
                    }
                }
            }
        }

        return $errors;
    }
}

// SMS Service class - integrated with TrainingAdmin
class SmsService {
    private static $conn = null;
    private static $userId = 0;
    private static $tenantId = 0;

    public static function boot() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Use existing TrainingAdmin auth
        $user = requireAuth();
        self::$userId   = $user['user_id'];
        self::$tenantId = $user['tenant_id'] ?? 0;
        self::$conn = getTenantConnection();

        // Ensure SMS tables exist
        self::ensureSmsTables();

        return self::$conn;
    }

    public static function requireMethod($methods = []) {
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) {
            SmsResponse::error('Method not allowed', 405);
        }
    }

    public static function requestData() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $_GET;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        return is_array($input) ? $input : [];
    }

    public static function userId() {
        return self::$userId;
    }

    public static function tenantId() {
        return self::$tenantId;
    }

    public static function getConnection() {
        return self::$conn;
    }

    public static function close() {
        // Connection closed by tenant-config
    }

    public static function now() {
        return date('Y-m-d H:i:s');
    }

    public static function sanitize($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    public static function ensureUid($value, $prefix) {
        $uid = trim($value);
        if ($uid !== '') {
            return $uid;
        }
        return $prefix . '_' . str_replace('.', '', uniqid('', true));
    }

    public static function getBalance() {
        $conn = self::$conn;
        $result = $conn->query("SELECT total_sms, used_sms, remaining_sms FROM sms_balance WHERE id = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $result->free();
            return $row ?: ['total_sms' => 0, 'used_sms' => 0, 'remaining_sms' => 0];
        }
        return ['total_sms' => 0, 'used_sms' => 0, 'remaining_sms' => 0];
    }

    public static function deductBalance($units) {
        $conn = self::$conn;
        $now = self::now();
        $stmt = $conn->prepare("UPDATE sms_balance SET used_sms = used_sms + ?, remaining_sms = GREATEST(0, remaining_sms - ?), updated_at = ? WHERE id = 1 AND remaining_sms >= ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iisi", $units, $units, $now, $units);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    public static function getSettings() {
        $conn = self::$conn;
        $result = $conn->query("SELECT gateway_name, sms_rate FROM sms_settings WHERE id = 1 LIMIT 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $result->free();
            return $row ?: ['gateway_name' => 'SMS_GATEWAY', 'sms_rate' => 0.25];
        }
        return ['gateway_name' => 'SMS_GATEWAY', 'sms_rate' => 0.25];
    }

    private static function ensureSmsTables() {
        $conn = self::$conn;
        $tables = ['sms_templates', 'contact_groups', 'contacts', 'sms_campaigns',
                   'sms_logs', 'sms_packages', 'sms_transactions', 'sms_balance', 'sms_settings',
                   'sms_automation_rules', 'voice_sms_campaigns', 'voice_sms_logs'];

        $missing = [];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if (!$result || $result->num_rows === 0) {
                $missing[] = $table;
            }
            if ($result instanceof mysqli_result) $result->free();
        }

        // If any tables missing, they should be created via migration
        // The presence of this check ensures compatibility
    }
}
