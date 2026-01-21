<?php
require_once '../config/db.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

$itemId = (int) $_POST['item_id'];
$roomId = (int) $_POST['room_id'];
$qty = (int) $_POST['quantity'];
$userId = $_SESSION['user']['id'];

/* Get available stock */
$stmt = $conn->prepare(
    "SELECT quantity FROM items WHERE id = ?"
);
$stmt->bind_param("i", $itemId);
$stmt->execute();
$available = $stmt->get_result()->fetch_assoc()['quantity'];
$stmt->close();

/* VALIDATION (this was missing before) */
if ($qty <= 0 || $qty > $available) {
    http_response_code(400);
    exit('Invalid quantity');
}

/* Insert or update room_items */
$stmt = $conn->prepare("
    INSERT INTO room_items (room_id, item_id, quantity, assigned_by)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
");
$stmt->bind_param("iiii", $roomId, $itemId, $qty, $userId);
$stmt->execute();
$stmt->close();

/* Reduce global stock */
$stmt = $conn->prepare("
    UPDATE items SET quantity = quantity - ? WHERE id = ?
");
$stmt->bind_param("ii", $qty, $itemId);
$stmt->execute();
$stmt->close();

echo 'OK';