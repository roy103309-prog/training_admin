<?php
/**
 * Family Portal SMS integration — credentials.
 *
 * SMS API v5 uses project_id + tenant_id authentication.
 * No API key is required.
 */
return [
    'enabled'     => true,
    'project_id'  => 3, // FamilyPortal projects.id for TrainingAdmin
    'send_url'    => 'http://localhost/FamilyPortal/api/sms/send.php',
    'balance_url' => 'http://localhost/FamilyPortal/api/sms/balance.php',
    'topup_url'   => 'http://localhost/FamilyPortal/api/sms/topup.php',
    'timeout'     => 6, // seconds for HTTP call
    // If true, never raise exceptions — log + swallow. Recommended: true.
    'fail_quiet'  => true,
];
