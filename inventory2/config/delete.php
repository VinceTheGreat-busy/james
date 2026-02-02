<?php
require_once 'db.php';
require_once 'auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Get and validate item ID
$itemId = sanitizeId($_POST['item_id'] ?? null);

if (!$itemId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID.']);
    exit;
}

try {
    // Get item name before deleting for logging
    $stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if (!$item) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
        exit;
    }

    // Check if item is assigned to any room
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM room_items WHERE item_id = ?");
    $checkStmt->bind_param('i', $itemId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $roomCheck = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($roomCheck['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot delete item. It is currently assigned to one or more rooms.'
        ]);
        exit;
    }

    // Delete item
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param('i', $itemId);

    if ($stmt->execute()) {
        // Log the action
        $userId = getCurrentUserId();
        logAction($conn, $userId, "Deleted item: {$item['name']}", $itemId);

        echo json_encode([
            'status' => 'success',
            'message' => 'Item deleted successfully.',
            'deleted_item' => $item['name']
        ]);
    } else {
        throw new Exception('Failed to delete item.');
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("delete.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete item. Please try again.']);
}

$conn->close();