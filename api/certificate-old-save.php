<?php
require_once __DIR__ . '/certificate-common.php';
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

// multipart/form-data
$id = (int)($_POST['id'] ?? 0);
$old = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM old_certificates WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$old) jsonResponse(false, 'Record not found');
}
$name = nlClean($_POST['CrtName'] ?? '', 150);
$roll = nlClean($_POST['CrtRoll'] ?? '', 60);
$reg  = nlClean($_POST['CrtRegi'] ?? '', 60);
$addr = nlClean($_POST['CrtAddress'] ?? '', 500);
$dob  = nlDate($_POST['CrtDob'] ?? '');
if ($name === '' || $roll === '' || $reg === '') jsonResponse(false, 'Name, roll and registration number are required');

$newImg = nlUploadImage('image', 'old-certificates');
$img = $newImg ?? ($old['CrtImg'] ?? '');

if ($old) {
    $stmt = $conn->prepare("UPDATE old_certificates SET CrtName = ?, CrtDob = ?, CrtRoll = ?, CrtRegi = ?, CrtAddress = ?, CrtImg = ? WHERE id = ?");
    $stmt->bind_param('ssssssi', $name, $dob, $roll, $reg, $addr, $img, $id);
    $stmt->execute(); $stmt->close();
    if ($newImg && $old['CrtImg']) nlDeleteFile($old['CrtImg']);
    jsonResponse(true, 'Certificate updated');
}
$stmt = $conn->prepare("INSERT INTO old_certificates (CrtName, CrtDob, CrtRoll, CrtRegi, CrtAddress, CrtImg) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssss', $name, $dob, $roll, $reg, $addr, $img);
$stmt->execute(); $stmt->close();
jsonResponse(true, 'Certificate added');
