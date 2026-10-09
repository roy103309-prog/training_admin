<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/PortalSms.php';
require_once __DIR__ . '/_helpers/SmsTemplateEngine.php';

$conn = SmsService::boot();
SmsService::requireMethod(['POST']);
$data = SmsService::requestData();

try {
    handleSend($conn, $data);
} catch (Exception $e) {
    SmsResponse::error('Server error: ' . $e->getMessage(), 500);
}

function handleSend(mysqli $conn, array $data): void
{
    $message = trim((string)($data['message'] ?? ''));
    $recipients = $data['recipients'] ?? [];
    $templateId = trim((string)($data['template_id'] ?? ''));
    $templateVars = is_array($data['template_vars'] ?? null) ? $data['template_vars'] : [];
    $templateType = strtolower(trim((string)($data['template_type'] ?? '')));
    $globalEntityRef = trim((string)($data['entity_ref'] ?? ''));
    $entityRefs = is_array($data['entity_refs'] ?? null) ? array_values($data['entity_refs']) : [];
    $source = trim((string)($data['source'] ?? 'Manual Send'));
    $sendMode = strtolower(trim((string)($data['send_mode'] ?? 'bulk')));
    $language = strtolower(trim((string)($data['language'] ?? '')));

    if ($templateId !== '') {
        $template = getTemplateById($conn, $templateId);
        if (!$template) {
            SmsResponse::error('Template not found', 404);
        }
        if ($message === '') {
            $message = (string)$template['content'];
        }
        if ($language === '') {
            $language = strtolower((string)($template['language'] ?? ''));
        }
        if ($templateType === '') {
            $templateType = strtolower((string)($template['type'] ?? ''));
        }
    }

    if ($message === '') {
        SmsResponse::error('Message is required', 400);
    }

    if (!is_array($recipients) || empty($recipients)) {
        SmsResponse::error('At least one recipient is required', 400);
    }
    if ($sendMode === 'single' && count($recipients) !== 1) {
        SmsResponse::error('Single mode requires exactly one recipient', 400);
    }

    // Normalize recipients: each should have 'phone' and optional 'name'
    $normalizedRecipients = [];
    $rows = max(count($recipients), count($entityRefs));
    for ($idx = 0; $idx < $rows; $idx++) {
        $r = $recipients[$idx] ?? [];
        $phone = '';
        $name = 'Recipient';
        $entityRef = '';

        if (is_string($r)) {
            $phone = preg_replace('/\D+/', '', $r);
        } elseif (is_array($r)) {
            $phone = preg_replace('/\D+/', '', (string)($r['phone'] ?? ''));
            $name = trim((string)($r['name'] ?? 'Recipient'));
            $entityRef = trim((string)($r['entity_ref'] ?? ''));
            if ($entityRef === '' && isset($r['entity_id'])) {
                $entityRef = trim((string)$r['entity_id']);
            }
        }

        if ($entityRef === '' && isset($entityRefs[$idx])) {
            $entityRef = trim((string)$entityRefs[$idx]);
        }
        if ($entityRef === '') {
            $entityRef = $globalEntityRef;
        }

        if ($phone === '' && $entityRef === '') {
            continue;
        }
        $normalizedRecipients[] = ['phone' => $phone, 'name' => $name, 'entity_ref' => $entityRef];
    }

    if (empty($normalizedRecipients)) {
        SmsResponse::error('No valid recipients found', 400);
    }

    $portal = PortalSms::instance();
    if (!$portal->isEnabled()) {
        SmsResponse::error('Family Portal SMS is disabled. Update api/config/portal.php', 500);
    }
    $tenantId = (string)($data['tenant_id'] ?? ($_SESSION['tenant_id'] ?? SmsService::tenantId()));

    // ------------------------------------------------------------------
    // 1) Resolve every recipient's final message + phone, build the
    //    portal payload. Recipients without a phone fail locally only.
    // ------------------------------------------------------------------
    $meta = [];            // request_id => prepared row meta
    $portalRecipients = [];
    $localFailures = [];   // rows that never reach the portal

    foreach ($normalizedRecipients as $recipient) {
        $phone = (string)$recipient['phone'];
        $requestId = 'ha-' . SmsService::userId() . '-' . bin2hex(random_bytes(8));

        $resolvedVars = SmsTemplateEngine::resolveVariables(
            $conn, $templateType, $recipient,
            (string)($recipient['entity_ref'] ?? ''), $templateVars
        );
        $resolvedMessage = SmsTemplateEngine::applyTemplate($message, $resolvedVars);
        if ($resolvedMessage === '') {
            $resolvedMessage = $message;
        }
        if ($phone === '') {
            $phone = preg_replace('/\D+/', '', (string)($resolvedVars['CUSTOMER_PHONE'] ?? ($resolvedVars['USER_PHONE'] ?? '')));
        }

        $targetName = trim((string)($recipient['name'] ?? ''));
        if ($targetName === '' || $targetName === 'Recipient') {
            $targetName = (string)($resolvedVars['CUSTOMER_NAME'] ?? ($resolvedVars['USER_NAME'] ?? 'Recipient'));
        }

        $messageLanguage = $language !== '' ? $language : detectLanguage($resolvedMessage);
        $isUnicode = $messageLanguage === 'bangla' || preg_match('/[^\x00-\x7F]/u', $resolvedMessage) === 1;
        $unitSize = $isUnicode ? 70 : 160;
        $unitsPer = max(1, (int)ceil(mb_strlen($resolvedMessage, 'UTF-8') / $unitSize));

        $row = [
            'request_id'  => $requestId,
            'phone'       => $phone,
            'target_name' => $targetName,
            'message'     => $resolvedMessage,
            'language'    => $messageLanguage,
            'units'       => $unitsPer,
            'entity_ref'  => (string)($recipient['entity_ref'] ?? ''),
        ];

        if ($phone === '') {
            $row['status'] = 'Failed';
            $row['error']  = 'Phone could not be resolved from recipient/entity data';
            $localFailures[] = $row;
            continue;
        }

        $meta[$requestId] = $row;
        $portalRecipients[] = [
            'to'         => $phone,
            'name'       => $targetName,
            'sms'        => $resolvedMessage,
            'request_id' => $requestId,
            'entity_ref' => $row['entity_ref'],
        ];
    }

    // ------------------------------------------------------------------
    // 2) ONE bulk call to the portal (portal decides masking vs non-masking)
    // ------------------------------------------------------------------
    $portalData = ['results' => [], 'project_name' => null, 'balance' => null];
    if (!empty($portalRecipients)) {
        $portalRes = $portal->sendBulk($tenantId, $portalRecipients, $message, $language !== '' ? $language : null, $source);
        $portalData = is_array($portalRes['data'] ?? null) ? $portalRes['data'] : $portalData;
    }
    $projectName = (string)($portalData['data']['project_name'] ?? $portalData['project_name'] ?? '');
    $portalResults = $portalData['data']['results'] ?? ($portalData['results'] ?? []);
    $portalBalance = $portalData['data']['balance'] ?? ($portalData['balance'] ?? null);

    // Index portal results by request_id
    $byReq = [];
    foreach ((array)$portalResults as $pr) {
        if (!empty($pr['request_id'])) {
            $byReq[(string)$pr['request_id']] = $pr;
        }
    }

    // ------------------------------------------------------------------
    // 3) Persist local logs (mirror of portal status/ids) + build reply
    // ------------------------------------------------------------------
    $now = SmsService::now();
    $actor = (string)SmsService::userId();
    $sentLogs = [];
    $sentCount = 0;

    $conn->begin_transaction();
    try {
        $stmtLog = $conn->prepare(
            "INSERT INTO sms_logs
               (uid, request_id, phone, target_name, source, status, template_uid, message,
                units, segments, sms_type, language, cost, gateway, provider_msg_id, project_name,
                response, sent_at, created_at, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        if (!$stmtLog) {
            throw new Exception('Failed to prepare log insert: ' . $conn->error);
        }

        // 3a) recipients that went to the portal
        foreach ($meta as $requestId => $row) {
            $pr = $byReq[$requestId] ?? null;
            $ok = $pr ? (strtolower((string)($pr['status'] ?? '')) === 'sent') : false;
            $status = $ok ? 'Sent' : 'Failed';
            if ($ok) { $sentCount++; }

            $logUid = SmsService::ensureUid('', 'log');
            $smsType = (string)($pr['sms_type'] ?? '');
            $providerMsgId = (string)($pr['provider_message_id'] ?? '');
            $cost = (float)($pr['cost'] ?? 0);
            $unitsPer = (int)($pr['units'] ?? $row['units']);
            $gateway = 'FamilyPortal';
            $responseJson = $pr !== null ? json_encode($pr, JSON_UNESCAPED_UNICODE) : null;
            $sentAt = $ok ? $now : null;

            $stmtLog->bind_param(
                "ssssssssiissdsssssss",
                $logUid, $requestId, $row['phone'], $row['target_name'], $source, $status, $templateId, $row['message'],
                $unitsPer, $unitsPer, $smsType, $row['language'], $cost, $gateway, $providerMsgId, $projectName,
                $responseJson, $sentAt, $now, $actor
            );
            if (!$stmtLog->execute()) {
                throw new Exception('Failed to insert log: ' . $stmtLog->error);
            }

            $sentLogs[] = [
                'id' => $logUid,
                'request_id' => $requestId,
                'phone' => $row['phone'],
                'target_name' => $row['target_name'],
                'source' => $source,
                'status' => $status,
                'sms_type' => $smsType,
                'units' => $unitsPer,
                'cost' => $cost,
                'language' => $row['language'],
                'message' => $row['message'],
                'entity_ref' => $row['entity_ref'],
                'provider_msg_id' => $providerMsgId,
                'project_name' => $projectName,
                'portal' => $pr,
                'error' => $pr['error'] ?? null,
                'created_at' => $now,
            ];
        }

        // 3b) recipients that failed before reaching the portal (no phone)
        foreach ($localFailures as $row) {
            $logUid = SmsService::ensureUid('', 'log');
            $status = 'Failed';
            $zeroUnits = 0;
            $emptyType = '';
            $zeroCost = 0.0;
            $gateway = 'FamilyPortal';
            $emptyMsgId = '';
            $responseJson = json_encode(['error' => $row['error']], JSON_UNESCAPED_UNICODE);
            $nullSent = null;

            $stmtLog->bind_param(
                "ssssssssiissdsssssss",
                $logUid, $row['request_id'], $row['phone'], $row['target_name'], $source, $status, $templateId, $row['message'],
                $zeroUnits, $zeroUnits, $emptyType, $row['language'], $zeroCost, $gateway, $emptyMsgId, $projectName,
                $responseJson, $nullSent, $now, $actor
            );
            if (!$stmtLog->execute()) {
                throw new Exception('Failed to insert log: ' . $stmtLog->error);
            }

            $sentLogs[] = [
                'id' => $logUid,
                'request_id' => $row['request_id'],
                'phone' => '',
                'target_name' => $row['target_name'],
                'source' => $source,
                'status' => $status,
                'units' => 0,
                'cost' => 0,
                'language' => $row['language'],
                'message' => $row['message'],
                'error' => $row['error'],
                'created_at' => $now,
            ];
        }

        $stmtLog->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        SmsResponse::error('Transaction error: ' . $e->getMessage(), 500);
    }

    SmsResponse::success('SMS sent successfully', [
        'project_name' => $projectName,
        'sent_count' => $sentCount,
        'failed_count' => count($sentLogs) - $sentCount,
        'total_units' => array_sum(array_map(static function ($r) { return (int)($r['units'] ?? 0); }, $sentLogs)),
        'total_cost' => array_sum(array_map(static function ($r) { return (float)($r['cost'] ?? 0); }, $sentLogs)),
        'balance' => $portalBalance,
        'logs' => $sentLogs,
    ], $sentCount > 0 ? 200 : 502);
}

function getTemplateById(mysqli $conn, string $id): ?array
{
    $stmt = $conn->prepare("SELECT uid, name, type, language, content, variables FROM sms_templates WHERE uid = ? OR id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $id, $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if ($res) {
        $res->free();
    }
    $stmt->close();
    return $row ?: null;
}

function detectLanguage(string $message): string
{
    return preg_match('/[^\x00-\x7F]/u', $message) === 1 ? 'bangla' : 'english';
}
