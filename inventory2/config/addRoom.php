<?php
require_once 'db.php';
require_once 'auth.php';

requireLogin();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rn = trim($_POST['rn'] ?? '');
$name = trim($_POST['name'] ?? '');
$roomFloor = trim($_POST['floor_number'] ?? '');

if ($rn === '' || $name === '' || $roomFloor === '') {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO rooms (rn, name, floor_id) VALUES (?, ?, ?)");
$stmt->bind_param("ss", $rn, $name, $roomFloor);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Room already exists']);
}

$stmt->close();