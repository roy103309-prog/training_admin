<?php
/**
 * Import punches from the attendance device export (.dat / .txt / .csv / .xlsx).
 * Each row needs an employee device ID and a date-time.
 *  - Raw punches are kept in att_punch_logs (training_XXXXXX).
 *  - Each touched day is written to the TrainingAdmin attendance (att_records):
 *    first punch = in time, last punch = out time, so the existing
 *    Attendance reports show it.
 * The day status (Present / Late / Early Leave / Half Day) is returned as a summary:
 *   1 punch = Half Day, entry later than in-time + late minutes = Late Present,
 *   exit 30+ minutes before out-time = Early Leave, otherwise Present.
 */
require_once __DIR__ . '/../newlife-common.php';
nlRequirePost();
$user = requireAuth();
$locationId = nlLocationId();
if (!$locationId) jsonResponse(false, 'No location selected');
$conn = getNewLifeConnection();

const ATT_EARLY_MINUTES = 30;
const ATT_DEFAULT_GRACE = 15;
const ATT_DEFAULT_IN = '09:00:00';
const ATT_DEFAULT_OUT = '18:00:00';

/** Text rows from the first worksheet of an .xlsx file */
function attXlsxRows($path) {
    if (!class_exists('ZipArchive')) jsonResponse(false, 'Excel files need the PHP zip extension. Save the file as .csv and upload again.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) jsonResponse(false, 'Unable to read the Excel file');
    $shared = [];
    if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        foreach (simplexml_load_string($xml)->si as $si) {
            $text = isset($si->t) ? (string)$si->t : '';
            foreach ($si->r as $run) $text .= (string)$run->t;
            $shared[] = trim($text);
        }
    }
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheet === false) jsonResponse(false, 'Worksheet not found in the Excel file');
    $rows = [];
    foreach (simplexml_load_string($sheet)->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $v = (string)($c->v ?? '');
            $t = (string)$c['t'];
            if ($t === 's') $v = $shared[(int)$v] ?? '';
            elseif ($t === 'inlineStr') $v = (string)($c->is->t ?? '');
            elseif (is_numeric($v) && (float)$v > 30000 && (float)$v < 80000) $v = gmdate('Y-m-d H:i:s', (int)round(((float)$v - 25569) * 86400));
            $cells[] = $v;
        }
        $rows[] = implode("\t", $cells);
    }
    return $rows;
}

/** [deviceId, 'Y-m-d H:i:s'] pairs from many export formats */
function attParseLines(array $lines) {
    $out = [];
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '') continue;
        if (preg_match('/(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})[ T]+(\d{1,2}):(\d{2})(?::(\d{2}))?/', $line, $m, PREG_OFFSET_CAPTURE)) {
            $dt = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1][0], $m[2][0], $m[3][0], $m[4][0], $m[5][0], $m[6][0] ?? 0);
        } elseif (preg_match('/(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})[ T]+(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)?/i', $line, $m, PREG_OFFSET_CAPTURE)) {
            $h = (int)$m[4][0];
            $ap = strtoupper($m[7][0] ?? '');
            if ($ap === 'PM' && $h < 12) $h += 12;
            if ($ap === 'AM' && $h === 12) $h = 0;
            $dt = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[3][0], $m[2][0], $m[1][0], $h, $m[5][0], $m[6][0] ?? 0);
        } else {
            continue;
        }
        $pos = $m[0][1];
        $before = substr($line, 0, $pos);
        $after = substr($line, $pos + strlen($m[0][0]));
        $id = null;
        if (preg_match('/(\d+)/', $before, $im)) $id = (int)$im[1];
        elseif (preg_match('/^\D*(\d+)/', $after, $im)) $id = (int)$im[1];
        $ts = strtotime($dt);
        if (!$id || $ts === false || date('Y-m-d H:i:s', $ts) !== $dt) continue;
        $out[] = [$id, $dt];
    }
    return $out;
}

/** Rebuild one day in the TrainingAdmin attendance from the raw punches; returns the day status */
function attRecompute(mysqli $conn, $locationId, $userId, $date) {
    $stmt = $conn->prepare("SELECT TIME(punch_time) AS t FROM att_punch_logs WHERE user_id = ? AND DATE(punch_time) = ? ORDER BY punch_time");
    $stmt->bind_param('is', $userId, $date); $stmt->execute();
    $punches = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 't'); $stmt->close();
    if (!$punches) return null;

    // Office hours of that weekday from Attendance Settings, else defaults
    $dow = (int)date('w', strtotime($date));
    $stmt = $conn->prepare("SELECT in_time, out_time, late_min FROM " . nlCoreTable('att_settings') . " WHERE location_id = ? AND user_id = ? AND day_of_week = ?");
    $stmt->bind_param('iii', $locationId, $userId, $dow); $stmt->execute();
    $rule = $stmt->get_result()->fetch_assoc() ?: []; $stmt->close();
    $shiftStart = ($rule['in_time'] ?? null) ?: ATT_DEFAULT_IN;
    $shiftEnd = ($rule['out_time'] ?? null) ?: ATT_DEFAULT_OUT;
    $grace = isset($rule['late_min']) ? (int)$rule['late_min'] : ATT_DEFAULT_GRACE;

    $n = count($punches);
    $entry = $punches[0];
    $exit = $n > 1 ? $punches[$n - 1] : null;
    $toMin = function ($t) { return (int)substr($t, 0, 2) * 60 + (int)substr($t, 3, 2); };
    if ($n === 1) $status = 'Half Day';
    elseif ($toMin($entry) > $toMin($shiftStart) + $grace) $status = 'Late Present';
    elseif ($toMin($exit) < $toMin($shiftEnd) - ATT_EARLY_MINUTES) $status = 'Early Leave';
    else $status = 'Present';

    $stmt = $conn->prepare("INSERT INTO " . nlCoreTable('att_records') . " (location_id, user_id, att_date, in_time, out_time, is_absent, source)
                            VALUES (?, ?, ?, ?, ?, 0, 'manual')
                            ON DUPLICATE KEY UPDATE in_time = VALUES(in_time), out_time = VALUES(out_time), is_absent = 0");
    $stmt->bind_param('iisss', $locationId, $userId, $date, $entry, $exit);
    $stmt->execute(); $stmt->close();
    return $status;
}

// ---------------------------------------------------------------- read file
$f = $_FILES['attendance_file'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK) jsonResponse(false, 'Please choose an attendance file');
if ($f['size'] > 20 * 1024 * 1024) jsonResponse(false, 'File is larger than 20 MB');
$ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
if ($ext === 'xlsx') {
    $lines = attXlsxRows($f['tmp_name']);
} elseif ($ext === 'xls') {
    $raw = (string)file_get_contents($f['tmp_name']);
    if (strpos($raw, "\xD0\xCF\x11\xE0") === 0) jsonResponse(false, 'Old .xls format is not supported. Open it in Excel and "Save As" .xlsx or .csv, then upload again.');
    $lines = preg_split('/\r\n|\r|\n/', strip_tags(str_replace(['</td>', '</tr>'], ["\t", "\n"], $raw)));
} elseif (in_array($ext, ['dat', 'txt', 'csv'], true)) {
    $raw = (string)file_get_contents($f['tmp_name']);
    if (strpos($raw, "\xFF\xFE") === 0 || strpos($raw, "\xFE\xFF") === 0) $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
    $lines = preg_split('/\r\n|\r|\n/', $raw);
} else {
    jsonResponse(false, 'Unsupported file type. Use .dat, .txt, .csv or .xlsx');
}

$punches = attParseLines($lines);
if (!$punches) jsonResponse(false, 'No attendance rows could be read from this file');

// Device ID -> employee of this branch (own device ID, otherwise the employee id)
$map = [];
$stmt = $conn->prepare("SELECT u.id, COALESCE(d.device_id, u.id) AS device_id
                        FROM " . nlCoreTable('users') . " u LEFT JOIN employee_device_ids d ON d.user_id = u.id
                        WHERE u.isemployee = 1 AND (u.location_id = ? OR u.location_id IS NULL)");
$stmt->bind_param('i', $locationId); $stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $map[(int)$r['device_id']] = (int)$r['id'];
$stmt->close();

$inserted = 0; $skipped = 0; $unknown = []; $touched = [];
$statusCount = ['Present' => 0, 'Late Present' => 0, 'Early Leave' => 0, 'Half Day' => 0];
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT IGNORE INTO att_punch_logs (location_id, user_id, punch_time) VALUES (?, ?, ?)");
    foreach ($punches as $p) {
        if (!isset($map[$p[0]])) { $skipped++; $unknown[$p[0]] = true; continue; }
        $uid = $map[$p[0]];
        $stmt->bind_param('iis', $locationId, $uid, $p[1]);
        $stmt->execute();
        if ($stmt->affected_rows > 0) $inserted++;
        $touched[$uid . '|' . substr($p[1], 0, 10)] = true;
    }
    $stmt->close();

    foreach (array_keys($touched) as $key) {
        [$uid, $date] = explode('|', $key);
        $status = attRecompute($conn, $locationId, (int)$uid, $date);
        if ($status) $statusCount[$status]++;
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}

$days = count($touched);
$msg = "Imported {$inserted} new punch(es); {$days} day(s) updated";
if ($skipped) $msg .= "; {$skipped} row(s) skipped (unknown device ID: " . implode(', ', array_slice(array_keys($unknown), 0, 10)) . (count($unknown) > 10 ? ' ...' : '') . ')';
jsonResponse(true, $msg, ['inserted' => $inserted, 'updated' => $days, 'skipped' => $skipped, 'status' => $statusCount]);
