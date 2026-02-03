<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: text/plain');

if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized: Please log in');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$itemId = sanitizeId($_POST['item_id'] ?? null);
$roomId = sanitizeId($_POST['room_id'] ?? null);
$userId = getCurrentUserId();

if (!$itemId || !$roomId) {
    http_response_code(400);
    exit('Invalid input: item_id and room_id are required');
}

$conn->begin_transaction();

try {
    // Lock the row and grab assigned qty + item name
    $stmt = $conn->prepare(
        "SELECT ri.quantity AS assigned_qty, i.name
         FROM room_items ri
         JOIN  items i ON ri.item_id = i.id
         WHERE ri.room_id = ? AND ri.item_id = ?
         FOR UPDATE"
    );
    $stmt->bind_param('ii', $roomId, $itemId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('Item is not assigned to this room');
    }

    $assignedQty = (int) $row['assigned_qty'];
    $itemName = $row['name'];

    // Delete the room_items row
    $stmt = $conn->prepare("DELETE FROM room_items WHERE room_id = ? AND item_id = ?");
    $stmt->bind_param('ii', $roomId, $itemId);
    $stmt->execute();
    $stmt->close();

    // Return stock to global inventory
    $stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
    $stmt->bind_param('ii', $assignedQty, $itemId);
    $stmt->execute();
    $stmt->close();

    // Grab room number for the log
    $stmt = $conn->prepare("SELECT rn FROM rooms WHERE id = ?");
    $stmt->bind_param('i', $roomId);
    $stmt->execute();
    $roomRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    logAction(
        $conn,
        $userId,
        "Removed {$assignedQty} unit(s) of '{$itemName}' from room '{$roomRow['rn']}'",
        $itemId,
        $roomId
    );

    $conn->commit();

    http_response_code(200);
    echo "Success: '{$itemName}' removed from room '{$roomRow['rn']}' ({$assignedQty} unit(s) returned to stock)";

} catch (Exception $e) {
    $conn->rollback();
    error_log("removeItem.php error: " . $e->getMessage());
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}

$conn->close();