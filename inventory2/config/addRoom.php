<?php
require_once 'db.php';
require_once 'auth.php';

requireLogin();

header('Content-Type: application/json');

$rn = trim($_POST['rn'] ?? '');
$name = trim($_POST['name'] ?? '');
$floorId = sanitizeId($_POST['floor_number'] ?? null);   // the <select> value is the floor id (int)

if ($rn === '' || $name === '' || !$floorId) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Make sure the floor actually exists
$floor = fetchOne($conn, "SELECT id FROM floors WHERE id = ?", [$floorId], 'i');
if (!$floor) {
    echo json_encode(['success' => false, 'message' => 'Selected floor does not exist.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO rooms (rn, name, floor_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $rn, $name, $floorId);   // two strings + one int

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Room added successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Room already exists or could not be created.']);
}

$stmt->close();
$conn->close();