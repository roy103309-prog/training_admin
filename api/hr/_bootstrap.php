<?php
require_once __DIR__ . '/../tenant-config.php';

/**
 * HR / Payroll tables (grades, salary_setup, allowance_deductions,
 * salaries, salary_items) and the employee columns on `users` are
 * provisioned at registration via database/tenant_schema.sql.
 * No runtime migration here.
 */
