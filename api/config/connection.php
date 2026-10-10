<?php
/**
 * Database connection settings.
 * Edit these values to point the application at a different database
 * server or to rename the master / tenant databases.
 */

// Master (portal) database
define('MASTER_DB_HOST', 'localhost');
define('MASTER_DB_PORT', 3306);   // change if your MySQL/MariaDB runs on a different port
define('MASTER_DB_USER', 'root');
define('MASTER_DB_PASS', '');
define('MASTER_DB_NAME', 'training_master');

// Tenant database naming: <prefix><base + tenant_id>  e.g. training_100001
define('TENANT_DB_PREFIX', 'training_');
define('TENANT_DB_BASE', 100000);

// Institute database the public website (site.php, /site) shows
define('WEBSITE_TENANT_DB', 'training_100001');

/* ------------------------------------------------------------------
 *  New Life modules: their tables live in the institute's own database
 *  (training_100001 ...), in the "NEW LIFE MODULES" block of
 *  database/tenant_schema.sql. New institutes get them at registration;
 *  institutes registered earlier get them on their first New Life request
 *  (once per session and schema version).
 * ------------------------------------------------------------------ */

// Bump after changing the NEW LIFE MODULES block of database/tenant_schema.sql
define('NL_SCHEMA_VERSION', 6);

// CREATE TABLE statements of the NEW LIFE MODULES block (it must not contain ';' inside values)
function nlSchemaStatements() {
    $sql = @file_get_contents(dirname(__DIR__, 2) . '/database/tenant_schema.sql');
    if ($sql === false) throw new RuntimeException('Schema file not found: tenant_schema.sql');
    $start = strpos($sql, '>>> NEW LIFE MODULES (start)');
    $end = strpos($sql, '<<< NEW LIFE MODULES (end)');
    if ($start === false || $end === false || $end < $start) throw new RuntimeException('NEW LIFE MODULES block not found in tenant_schema.sql');
    // From the line after the start marker to the line of the end marker
    $start = strpos($sql, "\n", $start);
    $end = strrpos(substr($sql, 0, $end), "\n");
    $block = substr($sql, $start, $end - $start);
    $block = preg_replace('/^\s*--.*$/m', '', $block);
    return array_values(array_filter(array_map('trim', explode(';', $block))));
}

// Create the New Life tables in a TrainingAdmin tenant database. Safe to call again. Throws on error.
function nlEnsureSchema(mysqli $conn) {
    foreach (nlSchemaStatements() as $statement) {
        if (!$conn->query($statement)) throw new RuntimeException($conn->error);
    }
}

/**
 * Connection to the logged-in institute's TrainingAdmin database, with the
 * New Life tables created / updated once per session when needed.
 */
function getNewLifeConnection() {
    static $conn = null;
    if ($conn !== null) return $conn;

    $databaseName = (string)($_SESSION['tenant_database'] ?? '');
    if ((int)($_SESSION['tenant_id'] ?? 0) <= 0 || $databaseName === '') {
        http_response_code(401);
        jsonResponse(false, 'Unauthorized. Please login.');
    }
    $tenantConn = getTenantConnection($databaseName);

    if ((int)($_SESSION['nl_schema'][$databaseName] ?? 0) !== NL_SCHEMA_VERSION) {
        try {
            nlEnsureSchema($tenantConn);
        } catch (Throwable $e) {
            error_log('[NewLife] Schema update of ' . $databaseName . ' failed: ' . $e->getMessage());
            http_response_code(500);
            jsonResponse(false, 'New Life tables could not be prepared');
        }
        $_SESSION['nl_schema'][$databaseName] = NL_SCHEMA_VERSION;
    }

    $conn = $tenantConn;
    return $conn;
}

/**
 * Name of a TrainingAdmin table (users, payment_methods, acc_vouchers ...) in New Life
 * queries. The tables share one database now, so this is the quoted table name.
 */
function nlCoreTable($table) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        http_response_code(500);
        jsonResponse(false, 'Invalid table name');
    }
    return "`{$table}`";
}
