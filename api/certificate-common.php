<?php
require_once __DIR__ . '/newlife-common.php';

/**
 * Certificate tables (certificates, old_certificates).
 */

function certStudent(mysqli $conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$s) jsonResponse(false, 'Student not found');
    return $s;
}

// Next free number like CERT-2026-00001 (prefix from General Settings)
function certNextNumber(mysqli $conn) {
    $prefix = preg_replace('/[^A-Za-z0-9_-]/', '', (string)nlSetting('certificate_no_prefix', 'CERT')) ?: 'CERT';
    $base = $prefix . '-' . date('Y') . '-';
    $n = (int)$conn->query("SELECT COUNT(*) AS c FROM certificates")->fetch_assoc()['c'];
    $chk = $conn->prepare("SELECT 1 FROM certificates WHERE certificate_no = ?");
    do {
        $n++;
        $no = $base . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
        $chk->bind_param('s', $no); $chk->execute();
        $taken = (bool)$chk->get_result()->fetch_row();
    } while ($taken);
    $chk->close();
    return $no;
}
