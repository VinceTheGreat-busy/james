<?php
require_once 'db.php';
require_once 'auth.php';

// Set plain text header for responses
header('Content-Type: text/plain');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized: Please log in');
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get and validate input
$itemId = sanitizeId($_POST['item_id'] ?? null);
$roomId = sanitizeId($_POST['room_id'] ?? null);
$qty = sanitizeId($_POST['quantity'] ?? null);
$userId = getCurrentUserId();

if (!$itemId || !$roomId || !$qty) {
    http_response_code(400);
    exit('Invalid input: Missing required fields');
}

if ($qty <= 0) {
    http_response_code(400);
    exit('Invalid quantity: Must be greater than 0');
}

// Start transaction
$conn->begin_transaction();

try {
    // Get available stock with row lock
    $stmt = $conn->prepare("SELECT name, quantity FROM items WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Item not found');
    }

    $itemData = $result->fetch_assoc();
    $available = (int) $itemData['quantity'];
    $itemName = $itemData['name'];
    $stmt->close();

    // Validate requested quantity
    if ($qty > $available) {
        throw new Exception("Insufficient stock: Only {$available} available");
    }

    // Verify room exists
    $stmt = $conn->prepare("SELECT rn FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $roomResult = $stmt->get_result();

    if ($roomResult->num_rows === 0) {
        throw new Exception('Room not found');
    }

    $roomData = $roomResult->fetch_assoc();
    $roomName = $roomData['rn'];
    $stmt->close();

    // Insert or update room_items
    $stmt = $conn->prepare("
        INSERT INTO room_items (room_id, item_id, quantity, assigned_by, assigned_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            quantity = quantity + VALUES(quantity),
            assigned_by = VALUES(assigned_by),
            assigned_at = NOW()
    ");
    $stmt->bind_param("iiii", $roomId, $itemId, $qty, $userId);
    $stmt->execute();
    $stmt->close();

    // Reduce global stock
    $stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
    $stmt->bind_param("ii", $qty, $itemId);
    $stmt->execute();
    $stmt->close();

    // Log the action
    logAction($conn, $userId, "Assigned {$qty} unit(s) of '{$itemName}' to room '{$roomName}'", $itemId, $roomId);

    // Commit transaction
    $conn->commit();

    http_response_code(200);
    echo "Success: {$qty} unit(s) of '{$itemName}' assigned to room '{$roomName}'";

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    error_log("assignItem.php error: " . $e->getMessage());
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}

$conn->close();