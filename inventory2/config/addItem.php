<?php
require_once 'db.php';
require_once 'auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/dashboard.php');
    exit;
}

// Sanitize input
$itemName = sanitizeInput($_POST['itemName'] ?? '');
$quantity = (int) ($_POST['quantity'] ?? 0);
$condition = sanitizeInput($_POST['condition'] ?? '');
$type = sanitizeInput($_POST['type'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');

// Validation
if (
    $itemName === '' ||
    $quantity < 1 ||
    $condition === '' ||
    $condition === 'Select' ||
    $type === ''
) {
    $_SESSION['error'] = 'All required fields must be filled correctly.';
    header('Location: ../views/dashboard.php');
    exit;
}

try {
    // Insert item
    $stmt = $conn->prepare("
        INSERT INTO items (name, quantity, conditions, type, description)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sisss",
        $itemName,
        $quantity,
        $condition,
        $type,
        $description
    );

    if ($stmt->execute()) {
        $itemId = $stmt->insert_id;

        // Log the action
        $userId = getCurrentUserId();
        logAction($conn, $userId, "Added new item: {$itemName}", $itemId);

        $_SESSION['success'] = 'Item added successfully.';
    } else {
        throw new Exception('Failed to add item.');
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("addItem.php error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to add item. Please try again.';
}

header('Location: ../views/dashboard.php');
exit;