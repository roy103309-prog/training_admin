<?php
/**
 * Public website (New Life) — shared helpers.
 * Pages: site.php, site-program.php, site-certificate.php, site-new-certificate.php (URL: /site/...)
 * Data: the institute's TrainingAdmin database (WEBSITE_TENANT_DB), content managed from
 * Admin -> Settings -> General Settings (Logo & Branding, Hero Sliders, Videos, Reviews, Programs).
 */
declare(strict_types=1);

define('SITE_ROOT', dirname(__DIR__));

// Database server and the website's institute database
require_once SITE_ROOT . '/api/config/connection.php';

date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/site-icons.php';

/** PDO connection (lazy, shared). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', MASTER_DB_HOST, MASTER_DB_PORT, WEBSITE_TENANT_DB);
    $pdo = new PDO($dsn, MASTER_DB_USER, MASTER_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+06:00'");
    return $pdo;
}

function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** HTML escape */
function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base URL path of TrainingAdmin (e.g. /TrainingAdmin). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $app = realpath(SITE_ROOT) ?: SITE_ROOT;
    $rel = '';
    if ($docRoot !== '' && stripos($app, $docRoot) === 0) {
        $rel = substr($app, strlen($docRoot));
    }
    $base = rtrim(str_replace('\\', '/', $rel), '/');
    return $base;
}

/** Website page URL. url('') => /TrainingAdmin/site, url('#about') => /TrainingAdmin/site#about, url('certificate') => /TrainingAdmin/site/certificate */
function url(string $path = ''): string
{
    if (preg_match('#^(https?:|mailto:|tel:|data:|//)#i', $path)) {
        return $path;
    }
    $site = base_path() . '/site';
    if ($path === '') {
        return $site;
    }
    if ($path[0] === '#') {
        return $site . $path;
    }
    return $site . '/' . ltrim($path, '/');
}

/** URL of a file inside TrainingAdmin (assets/..., uploads/training_XXXXXX/..., api/...). */
function asset_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:|data:|//)#i', $path)) {
        return $path;
    }
    $parts = array_map('rawurlencode', array_map('rawurldecode', explode('/', ltrim($path, '/'))));
    return base_path() . '/' . implode('/', $parts);
}

/** Website settings (Admin -> Logo & Branding) */
function settings(): array
{
    static $s = null;
    if ($s !== null) {
        return $s;
    }
    $s = [];
    try {
        foreach (db_all('SELECT setting_key, setting_value FROM web_settings') as $r) {
            $s[$r['setting_key']] = $r['setting_value'];
        }
    } catch (Throwable $e) {
        $s = [];
    }
    return $s;
}

function setting(string $key, string $default = ''): string
{
    $s = settings();
    return isset($s[$key]) && $s[$key] !== '' ? (string)$s[$key] : $default;
}

/** Language strings */
function lang(): array
{
    static $l = null;
    if ($l === null) {
        $l = require __DIR__ . '/site-lang.php';
    }
    return $l;
}

/** Translatable text: renders Bangla, JS swaps to English. */
function t(string $key): string
{
    $l = lang();
    $bn = $l['bn'][$key] ?? $key;
    return '<span data-i18n="' . e($key) . '">' . e($bn) . '</span>';
}

/** Raw string for a key */
function tr(string $key, string $lang = 'bn'): string
{
    $l = lang();
    return $l[$lang][$key] ?? ($l['bn'][$key] ?? $key);
}

/** Inline bilingual text from given values (e.g. DB/settings). */
function tx(string $bn, string $en): string
{
    if ($en === '' || $en === $bn) {
        return e($bn);
    }
    return '<span data-bn="' . e($bn) . '" data-en="' . e($en) . '">' . e($bn) . '</span>';
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (is_array($data)) {
        return $data;
    }
    return $_POST ?: [];
}

/** Programs list */
function programs(bool $homeOnly = false): array
{
    try {
        $sql = 'SELECT * FROM programs WHERE is_active = 1' . ($homeOnly ? ' AND show_on_home = 1' : '') . ' ORDER BY sort_order, id';
        return db_all($sql);
    } catch (Throwable $e) {
        return [];
    }
}

function initials(string $name, bool $firstLast = false): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
    if (!$parts) {
        return '';
    }
    if ($firstLast) {
        $a = mb_substr($parts[0], 0, 1);
        $b = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
        return mb_strtoupper($a . $b);
    }
    return mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2))));
}

/** Extract a YouTube video id from many URL forms. */
function youtube_id(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    $p = parse_url($url);
    if ($p && !empty($p['host'])) {
        $host = preg_replace('/^www\./', '', strtolower($p['host']));
        $segs = array_values(array_filter(explode('/', $p['path'] ?? '')));
        if ($host === 'youtu.be' && $segs) {
            return substr($segs[0], 0, 11);
        }
        if (str_ends_with($host, 'youtube.com')) {
            parse_str($p['query'] ?? '', $q);
            if (!empty($q['v'])) {
                return substr($q['v'], 0, 11);
            }
            if (count($segs) >= 2 && in_array($segs[0], ['embed', 'shorts', 'live'], true)) {
                return substr($segs[1], 0, 11);
            }
        }
    }
    if (preg_match('~(?:youtu\.be/|v/|shorts/|embed/|watch\?v=|&v=)([^#&?]{11})~', $url, $m)) {
        return $m[1];
    }
    return null;
}

/** Digits → Bangla digits */
function bn_digits(string $s): string
{
    return strtr($s, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
}
