<?php
require_once __DIR__ . '/../tenant-config.php';

/**
 * Attendance tables (att_holiday_types, att_holidays, att_leave_types,
 * att_settings, att_records, att_leaves, att_movements) are provisioned
 * at registration via database/tenant_schema.sql. No runtime migration here.
 */

function attDayNames() {
    return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
}
