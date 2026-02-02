<?php
require_once 'db.php';
require_once 'auth.php';

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
$userId = $_SESSION['user']['id'];

if (!$itemId || !$roomId) {
    http_response_code(400);
    exit('Invalid input: Missing required fields');
}

// Start transaction
$conn->begin_transaction();

try {
    // Get the current assigned quantity
    $stmt = $conn->prepare("
        SELECT quantity 
        FROM room_items 
        WHERE room_id = ? AND item_id = ?
    ");
    $stmt->bind_param("ii", $roomId, $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Item not found in this room');
    }
    
    $assignedQty = $result->fetch_assoc()['quantity'];
    $stmt->close();

    // Delete the room_items entry
    $stmt = $conn->prepare("
        DELETE FROM room_items 
        WHERE room_id = ? AND item_id = ?
    ");
    $stmt->bind_param("ii", $roomId, $itemId);
    $stmt->execute();
    $stmt->close();

    // Return the quantity back to global inventory
    $stmt = $conn->prepare("
        UPDATE items 
        SET quantity = quantity + ? 
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $assignedQty, $itemId);
    $stmt->execute();
    $stmt->close();

    // Log the action
    logAction($conn, $userId, "Removed {$assignedQty} items from room", $itemId, $roomId);

    // Commit transaction
    $conn->commit();
    
    http_response_code(200);
    echo 'Success: Item removed from room and returned to inventory';

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    error_log("removeItem.php error: " . $e->getMessage());
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}