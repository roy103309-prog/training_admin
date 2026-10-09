<?php
/**
 * FamilyPortal SMS API client (v5)
 * Auth model: project_id + tenant_id (no API key).
 */

class PortalSms
{
    private array $cfg;
    private static ?PortalSms $singleton = null;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg + [
            'enabled' => false,
            'project_id' => 0,
            'send_url' => '',
            'balance_url' => '',
            'timeout' => 6,
            'fail_quiet' => true,
        ];
    }

    public static function instance(): PortalSms
    {
        if (self::$singleton !== null) {
            return self::$singleton;
        }
        $cfg = require __DIR__ . '/../config/portal.php';
        self::$singleton = new self(is_array($cfg) ? $cfg : []);
        return self::$singleton;
    }

    public function isEnabled(): bool
    {
        return !empty($this->cfg['enabled'])
            && (int)($this->cfg['project_id'] ?? 0) > 0
            && trim((string)($this->cfg['send_url'] ?? '')) !== ''
            && trim((string)($this->cfg['balance_url'] ?? '')) !== '';
    }

    public function sendByTenant(string $tenantId, string $message, ?string $requestId = null, ?string $to = null, ?string $language = null, ?string $source = null, ?string $smsType = null): array
    {
        $payload = [
            'project_id' => (int)$this->cfg['project_id'],
            'tenant_id' => (string)$tenantId,
            'sms' => $message,
        ];
        if ($smsType !== null && trim($smsType) !== '') {
            $payload['sms_type'] = trim($smsType); // Masking | Non-Masking | Voice
        }
        if ($to !== null && trim($to) !== '') {
            $payload['to'] = preg_replace('/\D+/', '', $to);
        }
        if ($language !== null && trim($language) !== '') {
            $payload['language'] = strtolower(trim($language));
        }
        if ($source !== null && trim($source) !== '') {
            $payload['source'] = trim($source);
        }
        return $this->postJson((string)$this->cfg['send_url'], $payload, $requestId);
    }

    public function sendByPhone(string $tenantId, string $phone, string $message, ?string $requestId = null, ?string $language = null, ?string $source = null, ?string $smsType = null): array
    {
        return $this->sendByTenant($tenantId, $message, $requestId, $phone, $language, $source, $smsType);
    }

    /**
     * Bulk send in ONE portal call. Masking vs Non-Masking is decided by the
     * portal provider — not passed here.
     *
     * @param array $recipients each: ['to'=>'8801..', 'sms'=>'msg', 'name'=>'..',
     *                                  'request_id'=>'..', 'entity_ref'=>'..']
     *                          'sms' is optional if $defaultMessage given.
     */
    public function sendBulk(string $tenantId, array $recipients, string $defaultMessage = '', ?string $language = null, ?string $source = null, ?string $smsType = null): array
    {
        $payload = [
            'project_id' => (int)$this->cfg['project_id'],
            'tenant_id' => (string)$tenantId,
            'sms' => $defaultMessage,
            'recipients' => array_values($recipients),
        ];
        if ($smsType !== null && trim($smsType) !== '') {
            $payload['sms_type'] = trim($smsType); // Masking | Non-Masking | Voice
        }
        if ($language !== null && trim($language) !== '') {
            $payload['language'] = strtolower(trim($language));
        }
        if ($source !== null && trim($source) !== '') {
            $payload['source'] = trim($source);
        }
        return $this->postJson((string)$this->cfg['send_url'], $payload, null);
    }

    public function balance(string $tenantId): array
    {
        $query = http_build_query([
            'project_id' => (int)$this->cfg['project_id'],
            'tenant_id' => (string)$tenantId,
        ]);
        $url = (string)$this->cfg['balance_url'];
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        return $this->getJson($url . $sep . $query);
    }

    /**
     * Top up the tenant's single shared wallet. Global — NOT per type.
     */
    public function topup(string $tenantId, float $amount, string $paymentRef = '', string $note = ''): array
    {
        $payload = [
            'project_id' => (int)$this->cfg['project_id'],
            'tenant_id' => (string)$tenantId,
            'amount' => $amount,
            'payment_ref' => $paymentRef,
            'note' => $note,
        ];
        return $this->postJson((string)$this->cfg['topup_url'], $payload, null);
    }

    private function postJson(string $url, array $body, ?string $requestId): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => 0, 'data' => ['message' => 'Portal SMS disabled']];
        }

        $requestId = $requestId ?: bin2hex(random_bytes(12));
        $jsonBody = json_encode($body + ['request_id' => $requestId], JSON_UNESCAPED_UNICODE);
        if ($jsonBody === false) {
            return ['ok' => false, 'http' => 0, 'request_id' => $requestId, 'data' => ['message' => 'Failed to encode request body']];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int)$this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Request-ID: ' . $requestId,
            ],
            CURLOPT_POSTFIELDS => $jsonBody,
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('[PortalSms] HTTP POST error: ' . $err);
            if (empty($this->cfg['fail_quiet'])) {
                throw new RuntimeException('Portal SMS HTTP error: ' . $err);
            }
            return ['ok' => false, 'http' => 0, 'request_id' => $requestId, 'data' => ['message' => $err]];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = ['message' => 'Invalid JSON response', 'raw' => $raw];
        }
        return [
            'ok' => in_array($http, [200, 202], true) && !empty($data['status']),
            'http' => $http,
            'request_id' => $requestId,
            'data' => $data,
        ];
    }

    private function getJson(string $url): array
    {
        if (!$this->isEnabled()) {
            return ['ok' => false, 'http' => 0, 'data' => ['message' => 'Portal SMS disabled']];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int)$this->cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('[PortalSms] HTTP GET error: ' . $err);
            if (empty($this->cfg['fail_quiet'])) {
                throw new RuntimeException('Portal SMS HTTP error: ' . $err);
            }
            return ['ok' => false, 'http' => 0, 'data' => ['message' => $err]];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = ['message' => 'Invalid JSON response', 'raw' => $raw];
        }
        return [
            'ok' => $http === 200 && !empty($data['status']),
            'http' => $http,
            'data' => $data,
        ];
    }
}
