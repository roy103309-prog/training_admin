<?php
require_once __DIR__ . '/web-common.php';
$user = requireAuth();
jsonResponse(true, 'OK', ['items' => webAll(getNewLifeConnection(), 'web_facilities'), 'icons' => WEB_FACILITY_ICONS]);
