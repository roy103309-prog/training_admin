<?php
require_once __DIR__ . '/practical-common.php';
// Add (no id) or update a company
nlRequirePost();
$user = requireAuth();
$conn = getNewLifeConnection();

$in = nlInput();
$id = (int)($in['id'] ?? 0);
$f = [
    'name' => nlClean($in['name'] ?? '', 190),
    'country' => nlClean($in['country'] ?? '', 100),
    'contact_person' => nlClean($in['contact_person'] ?? '', 150),
    'mobile' => nlClean($in['mobile'] ?? '', 50),
    'email' => nlClean($in['email'] ?? '', 190),
    'address' => nlClean($in['address'] ?? '', 500),
    'is_active' => nlFlag($in['is_active'] ?? 1),
];
if ($f['name'] === '') jsonResponse(false, 'Company name is required');
if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Invalid email address');

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE companies SET name = ?, country = ?, contact_person = ?, mobile = ?, email = ?, address = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param('ssssssii', $f['name'], $f['country'], $f['contact_person'], $f['mobile'], $f['email'], $f['address'], $f['is_active'], $id);
    $stmt->execute(); $stmt->close();
    jsonResponse(true, 'Company updated', ['id' => $id]);
}
$stmt = $conn->prepare("INSERT INTO companies (name, country, contact_person, mobile, email, address, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssssi', $f['name'], $f['country'], $f['contact_person'], $f['mobile'], $f['email'], $f['address'], $f['is_active']);
$stmt->execute();
$id = (int)$conn->insert_id;
$stmt->close();
jsonResponse(true, 'Company added', ['id' => $id]);
