-- ============================================================
-- CONSOLIDATED TENANT SCHEMA (single file)
-- Base + HR/Payroll + Attendance + Basic Accounts + SMS modules.
-- Applied once per new tenant DB by createTenantDatabase() at
-- registration time. All former runtime migrations are folded in
-- here, so a freshly registered tenant has the complete schema.
-- ============================================================


-- ============================================================
-- LOCATION / BRANCH SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS locations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  address TEXT NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(100) NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_isactive (isactive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USER MANAGEMENT & RBAC
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menus (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(100) NULL,
  url VARCHAR(255) NULL,
  parent_id INT UNSIGNED NULL,
  sort_order INT NOT NULL DEFAULT 0,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_parent (parent_id),
  KEY idx_isactive (isactive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id INT UNSIGNED NOT NULL,
  menu_id INT UNSIGNED NOT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 0,
  can_add TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_menu (role_id, menu_id),
  KEY idx_role (role_id),
  KEY idx_menu (menu_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users: core auth + employee/payroll fields (HR fields folded in)
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  mobile VARCHAR(30) NOT NULL,
  employee_code VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  gender ENUM('male','female','other') NULL,
  joining_date DATE NULL,
  username VARCHAR(50) NULL,
  shift VARCHAR(50) NULL,
  password_hash VARCHAR(255) NOT NULL,
  avatar LONGTEXT NULL,
  role_id INT UNSIGNED NULL,
  grade_id INT UNSIGNED NULL,
  salary_type ENUM('daily','weekly','monthly') NULL,
  basic_salary DECIMAL(12,2) NULL DEFAULT 0,
  salary_status TINYINT(1) NOT NULL DEFAULT 1,
  isemployee TINYINT(1) NOT NULL DEFAULT 0,
  location_id INT UNSIGNED NULL,
  issuperadmin TINYINT(1) NOT NULL DEFAULT 0,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mobile (mobile),
  KEY idx_role (role_id),
  KEY idx_location (location_id),
  KEY idx_isactive (isactive),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-branch access per user
CREATE TABLE IF NOT EXISTS user_locations (
  user_id     INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, location_id),
  KEY idx_user (user_id),
  KEY idx_loc  (location_id),
  FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SETTINGS MODULE
-- ============================================================
CREATE TABLE IF NOT EXISTS ui_color_settings (
  setting_key   VARCHAR(100) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings_basic (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  website_name VARCHAR(255) NULL,
  tagline VARCHAR(255) NULL,
  logo VARCHAR(255) NULL,
  website_logo VARCHAR(255) NULL,
  favicon VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(100) NULL,
  address TEXT NULL,
  facebook_url VARCHAR(255) NULL,
  twitter_url VARCHAR(255) NULL,
  instagram_url VARCHAR(255) NULL,
  youtube_url VARCHAR(255) NULL,
  website_url VARCHAR(255) NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_location (location_id),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shared payment methods (used by Basic Accounts vouchers/reports)
CREATE TABLE IF NOT EXISTS payment_methods (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  mobile_no VARCHAR(50) NULL,
  note TEXT NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_location (location_id),
  KEY idx_isactive (isactive),
  FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HR / PAYROLL MODULE
-- ============================================================
CREATE TABLE IF NOT EXISTS grades (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  short_name VARCHAR(20) NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salary_setup (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  grade_id INT UNSIGNED NOT NULL,
  salary_type ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'monthly',
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_combo (location_id, role_id, grade_id, salary_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS allowance_deductions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  type ENUM('allowance','deduction') NOT NULL,
  value_type ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salaries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  period_type ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'monthly',
  period_label VARCHAR(30) NOT NULL,
  period_start DATE NULL,
  period_end DATE NULL,
  basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_allowance DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_period (location_id, user_id, period_type, period_label),
  KEY idx_loc (location_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salary_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  salary_id INT UNSIGNED NOT NULL,
  kind ENUM('allowance','deduction') NOT NULL,
  name VARCHAR(100) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_salary (salary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ATTENDANCE MODULE
-- ============================================================
CREATE TABLE IF NOT EXISTS att_holiday_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS att_holidays (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  holiday_type_id INT UNSIGNED NULL,
  holiday_date DATE NOT NULL,
  reason VARCHAR(255) NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id),
  KEY idx_date (holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS att_leave_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  short_name VARCHAR(20) NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-employee per-weekday attendance rule. day_of_week: 0=Sun .. 6=Sat
CREATE TABLE IF NOT EXISTS att_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  day_of_week TINYINT NOT NULL,
  in_time TIME NULL,
  out_time TIME NULL,
  late_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  absent_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_off TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_day (location_id, user_id, day_of_week),
  KEY idx_loc (location_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS att_records (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  att_date DATE NOT NULL,
  in_time TIME NULL,
  out_time TIME NULL,
  is_absent TINYINT(1) NOT NULL DEFAULT 0,
  source ENUM('manual','movement','leave') NOT NULL DEFAULT 'manual',
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_date (location_id, user_id, att_date),
  KEY idx_loc_date (location_id, att_date),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS att_leaves (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  leave_type_id INT UNSIGNED NULL,
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  days INT NOT NULL DEFAULT 1,
  reason VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id),
  KEY idx_user (user_id),
  KEY idx_dates (from_date, to_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS att_movements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  in_time TIME NULL,
  out_time TIME NULL,
  auto_present TINYINT(1) NOT NULL DEFAULT 1,
  place VARCHAR(150) NULL,
  purpose VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id),
  KEY idx_user (user_id),
  KEY idx_dates (from_date, to_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BASIC ACCOUNTS MODULE
-- ============================================================
CREATE TABLE IF NOT EXISTS acc_asset_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  type ENUM('asset','fixed_asset','receivable','payable') NOT NULL DEFAULT 'asset',
  name VARCHAR(150) NOT NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  opening_date DATE NULL,
  remark VARCHAR(255) NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acc_liability_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(50) NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acc_income_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(50) NULL,
  opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acc_expense_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(50) NULL,
  isactive TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acc_vouchers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  voucher_no VARCHAR(30) NOT NULL,
  type ENUM('income','expense','liability','transfer') NOT NULL,
  voucher_date DATE NOT NULL,
  asset_category_id INT UNSIGNED NULL,
  from_asset_id INT UNSIGNED NULL,
  to_asset_id INT UNSIGNED NULL,
  collected_by VARCHAR(100) NULL,
  total DECIMAL(14,2) NOT NULL DEFAULT 0,
  transaction_type ENUM('pay','receive') NULL,
  want_receipt TINYINT(1) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_voucher (location_id, voucher_no),
  KEY idx_loc_type_date (location_id, type, voucher_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS acc_voucher_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_id INT UNSIGNED NOT NULL,
  category_kind ENUM('income','expense','liability') NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  description VARCHAR(255) NULL,
  payment_method_id INT UNSIGNED NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_voucher (voucher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SMS MODULE
-- ============================================================
CREATE TABLE IF NOT EXISTS `sms_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `type` VARCHAR(100) DEFAULT NULL,
  `language` VARCHAR(30) DEFAULT 'Bangla',
  `content` TEXT NOT NULL,
  `variables` LONGTEXT DEFAULT NULL,
  `is_static` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Active',
  `created_by` VARCHAR(80) DEFAULT NULL,
  `updated_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_templates_uid` (`uid`),
  KEY `idx_sms_templates_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `type` VARCHAR(50) DEFAULT 'Manual',
  `color` VARCHAR(20) DEFAULT '#0d6efd',
  `criteria` TEXT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contact_groups_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `group_uid` VARCHAR(80) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Active',
  `notes` TEXT DEFAULT NULL,
  `created_by` VARCHAR(80) DEFAULT NULL,
  `updated_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contacts_uid` (`uid`),
  KEY `idx_contacts_phone` (`phone`),
  KEY `idx_contacts_group` (`group_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `template_uid` VARCHAR(80) DEFAULT NULL,
  `group_uid` VARCHAR(80) DEFAULT NULL,
  `total_contacts` INT(11) NOT NULL DEFAULT 0,
  `total_sent` INT(11) NOT NULL DEFAULT 0,
  `failed_count` INT(11) NOT NULL DEFAULT 0,
  `send_mode` VARCHAR(30) NOT NULL DEFAULT 'Instant',
  `status` VARCHAR(30) NOT NULL DEFAULT 'Draft',
  `scheduled_at` DATETIME DEFAULT NULL,
  `numbers_json` LONGTEXT DEFAULT NULL,
  `contact_uids_json` LONGTEXT DEFAULT NULL,
  `group_uids_json` LONGTEXT DEFAULT NULL,
  `last_run_at` DATETIME DEFAULT NULL,
  `created_by` VARCHAR(80) DEFAULT NULL,
  `updated_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_campaigns_uid` (`uid`),
  KEY `idx_sms_campaigns_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `request_id` VARCHAR(120) DEFAULT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `target_name` VARCHAR(150) DEFAULT NULL,
  `source` VARCHAR(50) DEFAULT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Pending',
  `template_uid` VARCHAR(80) DEFAULT NULL,
  `campaign_uid` VARCHAR(80) DEFAULT NULL,
  `message` TEXT,
  `units` INT(11) NOT NULL DEFAULT 1,
  `segments` INT(11) NOT NULL DEFAULT 1,
  `sms_type` VARCHAR(20) DEFAULT NULL,
  `language` VARCHAR(20) DEFAULT NULL,
  `cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `gateway` VARCHAR(80) DEFAULT NULL,
  `provider_msg_id` VARCHAR(120) DEFAULT NULL,
  `project_name` VARCHAR(100) DEFAULT NULL,
  `response` LONGTEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_logs_uid` (`uid`),
  KEY `idx_sms_logs_phone` (`phone`),
  KEY `idx_sms_logs_status` (`status`),
  KEY `idx_sms_logs_request_id` (`request_id`),
  KEY `idx_sms_logs_campaign` (`campaign_uid`),
  KEY `idx_sms_logs_template` (`template_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `sms_count` INT(11) NOT NULL DEFAULT 0,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `bonus_sms` INT(11) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Active',
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_packages_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `invoice_no` VARCHAR(80) NOT NULL,
  `transaction_ref` VARCHAR(150) DEFAULT NULL,
  `package_uid` VARCHAR(80) DEFAULT NULL,
  `package_name` VARCHAR(150) NOT NULL,
  `sms_count` INT(11) NOT NULL DEFAULT 0,
  `bonus_sms` INT(11) NOT NULL DEFAULT 0,
  `total_sms` INT(11) NOT NULL DEFAULT 0,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Pending',
  `balance_applied` TINYINT(1) NOT NULL DEFAULT 0,
  `gateway_response` LONGTEXT DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `applied_at` DATETIME DEFAULT NULL,
  `created_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_transactions_uid` (`uid`),
  KEY `idx_sms_transactions_status` (`status`),
  KEY `idx_sms_transactions_invoice` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_balance` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `total_sms` INT(11) NOT NULL DEFAULT 0,
  `used_sms` INT(11) NOT NULL DEFAULT 0,
  `remaining_sms` INT(11) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gateway_name` VARCHAR(80) DEFAULT NULL,
  `api_url` VARCHAR(255) DEFAULT NULL,
  `api_key` TEXT,
  `api_secret` TEXT,
  `sender_id` VARCHAR(80) DEFAULT NULL,
  `default_language` VARCHAR(30) DEFAULT 'Bangla',
  `sms_rate` DECIMAL(12,4) NOT NULL DEFAULT 0.2500,
  `low_balance_alert` INT(11) NOT NULL DEFAULT 200,
  `balance_mode` VARCHAR(30) DEFAULT 'Prepaid',
  `custom_package_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `timeout_seconds` INT(11) NOT NULL DEFAULT 30,
  `retry_attempts` INT(11) NOT NULL DEFAULT 3,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Active',
  `extras_json` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_automation_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `event` VARCHAR(80) NOT NULL,
  `template_uid` VARCHAR(80) DEFAULT NULL,
  `delay_minutes` INT(11) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Active',
  `description` TEXT DEFAULT NULL,
  `created_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_automation_rules_uid` (`uid`),
  KEY `idx_sms_automation_rules_event` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `voice_sms_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `audio_url` VARCHAR(500) DEFAULT NULL,
  `audio_name` VARCHAR(255) DEFAULT NULL,
  `group_uid` VARCHAR(80) DEFAULT NULL,
  `numbers_json` LONGTEXT DEFAULT NULL,
  `total_contacts` INT(11) NOT NULL DEFAULT 0,
  `total_sent` INT(11) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Draft',
  `scheduled_at` DATETIME DEFAULT NULL,
  `created_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voice_sms_campaigns_uid` (`uid`),
  KEY `idx_voice_sms_campaigns_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_audios` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `file_url` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `file_size` BIGINT UNSIGNED DEFAULT 0,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `created_by` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_audios_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `voice_sms_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(80) NOT NULL,
  `campaign_uid` VARCHAR(80) DEFAULT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Pending',
  `cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voice_sms_logs_uid` (`uid`),
  KEY `idx_voice_sms_logs_campaign` (`campaign_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFICATIONS + ROLE-BRANCH RESTRICTION
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc_read (location_id, is_read),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_locations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_loc (role_id, location_id),
  KEY idx_role (role_id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SEED DATA
-- ============================================================

-- Default branch/location
INSERT INTO locations (id, name, address, phone, email, isactive) VALUES
(1, 'Main Branch', '123 Main Street', '+1234567890', 'info@example.com', 1);

-- Default roles
INSERT INTO roles (id, name, isactive) VALUES
(1, 'Super Admin', 1),
(2, 'Admin', 1);

-- Default super admin user (password: admin123, stored as plain text)
INSERT INTO users (id, name, mobile, password_hash, role_id, location_id, issuperadmin, isactive) VALUES
(1, 'Super Admin', 'admin', 'admin123', 1, 1, 1, 1);

-- Seed multi-branch pivot from users.location_id
INSERT IGNORE INTO user_locations (user_id, location_id)
SELECT id, location_id FROM users WHERE location_id IS NOT NULL;

-- Default menus
INSERT INTO menus (id, name, url, icon, parent_id, sort_order, isactive) VALUES
-- 1. Dashboard
(1, 'Dashboard', 'new-dashboard.html', 'bx-home-circle', NULL, 1, 1),

-- 2. Attendance
(12, 'Attendance', '#', 'bx-time-five', NULL, 2, 1),

-- 3. Payroll
(13, 'Payroll', '#', 'bx-money', NULL, 3, 1),

-- 4. Basic Accounts
(14, 'Basic Accounts', '#', 'bx-calculator', NULL, 4, 1),

-- 5. User Management
(21, 'User Management', NULL, 'bx-user-circle', NULL, 5, 1),
(22, 'User Informations', 'user-list.html', 'bx-right-arrow-alt', 21, 1, 1),
(23, 'User Roles', 'user-roles.html', 'bx-right-arrow-alt', 21, 2, 1),

-- 6. Basic Settings
(24, 'Basic Settings', NULL, 'bx-wrench', NULL, 6, 1),
(25, 'Institute Information', 'settings-basic.html', 'bx-right-arrow-alt', 24, 1, 1),
(26, 'Menu Management', 'user-menus.html', 'bx-right-arrow-alt', 24, 2, 1),

-- 7. General Settings
(27, 'General Settings', '#', 'bx-slider-alt', NULL, 7, 1);

-- Grant all permissions to Super Admin role
INSERT INTO role_permissions (role_id, menu_id, can_view, can_add, can_edit, can_delete)
SELECT 1, id, 1, 1, 1, 1 FROM menus;

-- Default basic settings
INSERT INTO settings_basic (location_id, website_name, phone, email) VALUES
(1, 'Business Admin', '+1234567890', 'info@example.com');

-- Default payment methods
INSERT INTO payment_methods (location_id, name, isactive) VALUES
(1, 'Cash', 1),
(1, 'Bank', 1),
(1, 'Mobile Banking', 1);

-- Initialize SMS balance row
INSERT IGNORE INTO `sms_balance` (`id`, `total_sms`, `used_sms`, `remaining_sms`) VALUES (1, 0, 0, 0);

-- Initialize SMS settings row
INSERT IGNORE INTO `sms_settings` (`id`, `gateway_name`, `sender_id`, `sms_rate`, `status`) VALUES (1, 'SMS_GATEWAY', 'BUSINESS', 0.25, 'Active');

-- Seed default SMS packages
INSERT IGNORE INTO `sms_packages` (`uid`, `name`, `sms_count`, `price`, `bonus_sms`, `status`, `featured`, `description`) VALUES
('pkg_starter', 'Starter Pack', 500, 125.00, 0, 'Active', 0, '500 SMS units'),
('pkg_business', 'Business Pack', 2000, 450.00, 100, 'Active', 1, '2000 SMS + 100 bonus'),
('pkg_enterprise', 'Enterprise Pack', 10000, 2000.00, 1000, 'Active', 0, '10000 SMS + 1000 bonus');


-- ============================================================
-- >>> NEW LIFE MODULES (start)
-- Students, Certificates, Website Content, Employee Documents,
-- Device Import, General Settings, Practical Jobs.
--
-- Institutes registered earlier get these tables on their first
-- New Life request (api/config/newlife-connection.php runs only this
-- block), so every statement here must stay idempotent and no comment
-- or value may contain a semicolon.
--
-- user_id / created_by / payment_method_id / voucher_id / location_id
-- point to TrainingAdmin rows (users, payment_methods, acc_vouchers,
-- locations) and have no foreign keys.
-- ============================================================

-- ------------------------------------------------------------
-- General Settings (business rules of the New Life modules)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS app_settings (
  setting_key   VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- STUDENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL DEFAULT 1,
  category VARCHAR(40) NOT NULL DEFAULT 'Welding',
  name VARCHAR(255) NOT NULL,
  fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  duration VARCHAR(60) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_loc (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL DEFAULT 1,
  student_id VARCHAR(40) NOT NULL,
  admission_date DATE NOT NULL,
  name VARCHAR(150) NOT NULL,
  dob DATE NULL,
  blood_group VARCHAR(5) NOT NULL DEFAULT '',
  nid VARCHAR(40) NOT NULL DEFAULT '',
  passport_number VARCHAR(40) NOT NULL DEFAULT '',
  phone_number VARCHAR(30) NOT NULL DEFAULT '',
  present_address TEXT NULL,
  permanent_address TEXT NULL,
  course_id INT UNSIGNED NULL,
  course_category VARCHAR(40) NOT NULL DEFAULT 'Welding',
  course_name VARCHAR(255) NOT NULL DEFAULT '',
  course_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  duration VARCHAR(60) NOT NULL DEFAULT '',
  father_name VARCHAR(150) NOT NULL DEFAULT '',
  mother_name VARCHAR(150) NOT NULL DEFAULT '',
  guardian_relation VARCHAR(40) NOT NULL DEFAULT '',
  guardian_phone VARCHAR(30) NOT NULL DEFAULT '',
  reference_name VARCHAR(150) NOT NULL DEFAULT '',
  reference_remarks TEXT NULL,
  photo VARCHAR(500) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_student_id (student_id),
  KEY idx_loc (location_id),
  KEY idx_status (status),
  KEY idx_admission (admission_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- voucher_id = income voucher posted to the TrainingAdmin Accounts (acc_vouchers)
CREATE TABLE IF NOT EXISTS student_payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL DEFAULT 1,
  student_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_date DATETIME NOT NULL,
  payment_method_id INT UNSIGNED NULL,
  notes VARCHAR(255) NOT NULL DEFAULT '',
  voucher_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_student (student_id),
  KEY idx_loc_date (location_id, payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- CERTIFICATES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS certificates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL DEFAULT 1,
  student_id INT UNSIGNED NOT NULL,
  certificate_no VARCHAR(60) NOT NULL DEFAULT '',
  title VARCHAR(255) NOT NULL DEFAULT '',
  issue_date DATE NULL,
  file_path VARCHAR(500) NOT NULL DEFAULT '',
  description TEXT NULL,
  source VARCHAR(20) NOT NULL DEFAULT 'generated',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS old_certificates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CrtName VARCHAR(150) NOT NULL,
  CrtDob DATE NULL,
  CrtRoll VARCHAR(60) NOT NULL,
  CrtRegi VARCHAR(60) NOT NULL,
  CrtAddress VARCHAR(500) NOT NULL DEFAULT '',
  CrtImg VARCHAR(500) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_roll_reg (CrtRoll, CrtRegi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- WEBSITE CONTENT (read by a public website)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sliders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL DEFAULT '',
  subtitle VARCHAR(255) NOT NULL DEFAULT '',
  image_url VARCHAR(500) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(500) NOT NULL DEFAULT '',
  description TEXT NULL,
  video_url VARCHAR(500) NOT NULL,
  platform VARCHAR(30) NOT NULL DEFAULT 'youtube',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_name VARCHAR(150) NOT NULL,
  trade VARCHAR(150) NOT NULL DEFAULT '',
  country VARCHAR(100) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  photo_url VARCHAR(500) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS programs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(120) NOT NULL,
  title VARCHAR(255) NOT NULL,
  title_bn VARCHAR(255) NOT NULL DEFAULT '',
  subtitle VARCHAR(255) NOT NULL DEFAULT '',
  level VARCHAR(60) NOT NULL DEFAULT '',
  duration VARCHAR(60) NOT NULL DEFAULT '',
  price VARCHAR(60) NOT NULL DEFAULT '',
  description TEXT NULL,
  image VARCHAR(500) NOT NULL DEFAULT '',
  color VARCHAR(120) NOT NULL DEFAULT 'from-emerald-500 to-emerald-600',
  show_on_home TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  phone VARCHAR(50) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- HR extras (employees themselves stay in the TrainingAdmin users table)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS employee_documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ID each employee uses on the attendance device (default = employee id)
CREATE TABLE IF NOT EXISTS employee_device_ids (
  user_id INT UNSIGNED NOT NULL,
  device_id INT UNSIGNED NOT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_device (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Raw punches from the attendance device file
CREATE TABLE IF NOT EXISTS att_punch_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  punch_time DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_punch (user_id, punch_time),
  KEY idx_loc_time (location_id, punch_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PRACTICAL JOBS (welding test jobs of the students above)
-- A job's payable is taken from the student's deposit:
--   job balance = SUM(student_payments.amount) - SUM(jobs.payable)
-- so jobs post nothing to Accounts (the deposit already did).
-- ------------------------------------------------------------
-- max_jobs = jobs one student may take in the category (0 = no limit)
CREATE TABLE IF NOT EXISTS job_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  max_jobs INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- job_no = job date + daily serial, e.g. 20260818-003
-- category_name / price are copied, so later price changes keep old jobs as they were
-- verified_by = TrainingAdmin users.id of the supervisor
CREATE TABLE IF NOT EXISTS jobs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL DEFAULT 1,
  job_no VARCHAR(30) NOT NULL,
  job_date DATE NOT NULL,
  daily_serial INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  category_name VARCHAR(120) NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  payable DECIMAL(12,2) NOT NULL DEFAULT 0,
  verify_method VARCHAR(20) NOT NULL DEFAULT 'supervisor',
  verified_by INT UNSIGNED NULL,
  remarks VARCHAR(255) NOT NULL DEFAULT '',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_job_no (job_no),
  KEY idx_date (job_date),
  KEY idx_student_cat (student_id, category_id),
  KEY idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

-- Return / retrain entry of a job (one per job), made on the job's own date.
-- classification: retrain | plate_returned | failed. It does not change the job balance.
CREATE TABLE IF NOT EXISTS job_returns (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  return_date DATE NOT NULL,
  classification VARCHAR(20) NOT NULL,
  remarks VARCHAR(500) NOT NULL DEFAULT '',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_job (job_id),
  KEY idx_student (student_id),
  KEY idx_date (return_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Practical jobs of outside candidates (not students), paid in cash on the spot.
-- token_no = OUT-<date>-<daily serial>, e.g. OUT-20260818-001
-- voucher_id = income voucher (head "Practical Test Fee") in the TrainingAdmin Accounts
CREATE TABLE IF NOT EXISTS out_jobs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL DEFAULT 1,
  token_no VARCHAR(30) NOT NULL,
  job_date DATE NOT NULL,
  daily_serial INT UNSIGNED NOT NULL,
  candidate_name VARCHAR(150) NOT NULL,
  mobile VARCHAR(30) NOT NULL DEFAULT '',
  passport_number VARCHAR(40) NOT NULL DEFAULT '',
  agent_reference VARCHAR(150) NOT NULL DEFAULT '',
  category_id INT UNSIGNED NOT NULL,
  category_name VARCHAR(120) NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_to VARCHAR(150) NOT NULL DEFAULT '',
  payment_method_id INT UNSIGNED NULL,
  payment_ref VARCHAR(100) NOT NULL DEFAULT '',
  voucher_id INT UNSIGNED NULL,
  remarks VARCHAR(255) NOT NULL DEFAULT '',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_token (token_no),
  KEY idx_date (job_date),
  KEY idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Overseas placement of a student (one row per student, so the students table stays unchanged).
-- overseas_status: not_applied | interview | selected
CREATE TABLE IF NOT EXISTS student_overseas (
  student_id INT UNSIGNED NOT NULL,
  overseas_status VARCHAR(20) NOT NULL DEFAULT 'not_applied',
  destination_country VARCHAR(100) NOT NULL DEFAULT '',
  company_id INT UNSIGNED NULL,
  passport_submitted TINYINT(1) NOT NULL DEFAULT 0,
  remarks VARCHAR(500) NOT NULL DEFAULT '',
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id),
  KEY idx_status (overseas_status),
  KEY idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Overseas / partner companies that recruit the students
CREATE TABLE IF NOT EXISTS companies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(190) NOT NULL,
  country VARCHAR(100) NOT NULL DEFAULT '',
  contact_person VARCHAR(150) NOT NULL DEFAULT '',
  mobile VARCHAR(50) NOT NULL DEFAULT '',
  email VARCHAR(190) NOT NULL DEFAULT '',
  address VARCHAR(500) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- STORE (welding raw material: electrodes, filler wire, gas, plates, pipes)
-- current_stock is kept in step with store_ledger (IN adds, OUT subtracts).
-- code is NULL when not given, so several products may have no code.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS store_products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(40) NULL,
  name VARCHAR(190) NOT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
  current_stock DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- movement: in | out
CREATE TABLE IF NOT EXISTS store_ledger (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL DEFAULT 1,
  product_id INT UNSIGNED NOT NULL,
  txn_date DATE NOT NULL,
  movement VARCHAR(3) NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  reference VARCHAR(100) NOT NULL DEFAULT '',
  remarks VARCHAR(500) NOT NULL DEFAULT '',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_product (product_id),
  KEY idx_date (txn_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
-- <<< NEW LIFE MODULES (end)
;
