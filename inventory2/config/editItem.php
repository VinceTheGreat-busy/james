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

// Get and sanitize POST data
$itemId = sanitizeId($_POST['item_id'] ?? null);
$name = sanitizeInput($_POST['itemName'] ?? '');
$quantity = (int) ($_POST['quantity'] ?? 0);
$condition = sanitizeInput($_POST['condition'] ?? '');
$type = sanitizeInput($_POST['type'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');

// Validation
$errors = [];
if (!$itemId) {
    $errors[] = "Invalid item ID.";
}
if (!$name) {
    $errors[] = "Item name is required.";
}
if ($quantity < 1) {
    $errors[] = "Quantity must be at least 1.";
}
if (!$condition || $condition === 'Select') {
    $errors[] = "Please select a valid condition.";
}
if (!$type) {
    $errors[] = "Remarks/Type is required.";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
    exit;
}

try {
    // Check if item exists
    $checkStmt = $conn->prepare("SELECT id FROM items WHERE id = ?");
    $checkStmt->bind_param('i', $itemId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
        exit;
    }
    $checkStmt->close();

    // Update item
    $stmt = $conn->prepare("
        UPDATE items 
        SET name = ?, quantity = ?, conditions = ?, type = ?, description = ? 
        WHERE id = ?
    ");
    $stmt->bind_param('sisssi', $name, $quantity, $condition, $type, $description, $itemId);

    if ($stmt->execute()) {
        // Log the action
        $userId = getCurrentUserId();
        logAction($conn, $userId, "Edited item: {$name}", $itemId);

        echo json_encode([
            'status' => 'success',
            'message' => 'Item updated successfully.',
            'item' => [
                'id' => $itemId,
                'name' => $name,
                'quantity' => $quantity,
                'condition' => $condition,
                'type' => $type,
                'description' => $description
            ]
        ]);
    } else {
        throw new Exception('Failed to update item.');
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("editItem.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update item. Please try again.']);
}

$conn->close();