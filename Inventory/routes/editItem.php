<?php
require '../config/auth.php';
require '../config/db.php';

// Require authentication
requireLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: ../index.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['error'] = "CSRF token validation failed.";
    header('Location: ../index.php');
    exit();
}

// Validate and sanitize inputs
$id = (int)($_POST['id'] ?? 0);
$name = sanitizeInput($_POST['name'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 0);
$type = sanitizeInput($_POST['type'] ?? '');
$issue = sanitizeInput($_POST['issue'] ?? '');
$conditions = sanitizeInput($_POST['conditions'] ?? '');
$room = sanitizeInput($_POST['room'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');
$date = sanitizeInput($_POST['date'] ?? '');

// Validate required fields
$errors = [];

if ($id <= 0) {
    $errors[] = "Invalid item ID.";
}

if (empty($name)) {
    $errors[] = "Item name is required.";
}

if (!validateQuantity($quantity)) {
    $errors[] = "Quantity must be a positive number.";
}

if (empty($type)) {
    $errors[] = "Item type is required.";
}

if (empty($conditions)) {
    $errors[] = "Condition is required.";
}

$validConditions = ['Available', 'Damaged', 'Under Repair'];
if (!in_array($conditions, $validConditions)) {
    $errors[] = "Invalid condition selected.";
}

if (empty($date)) {
    $errors[] = "Date is required.";
}

// If validation fails, redirect back with error message
if (!empty($errors)) {
    $_SESSION['error'] = implode(' | ', $errors);
    header('Location: ../index.php');
    exit();
}

// Update database using prepared statement
$stmt = $conn->prepare(
    "UPDATE items 
    SET name = ?, quantity = ?, type = ?, issue = ?, conditions = ?, room = ?, description = ?, date = ?, updated_at = NOW()
    WHERE id = ?"
);

if ($stmt) {
    $stmt->bind_param(
        "sisissssi",
        $name,
        $quantity,
        $type,
        $issue,
        $conditions,
        $room,
        $description,
        $date,
        $id
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            logAction($_SESSION['user_id'], 'item_updated', $id);
            $_SESSION['success'] = "Item updated successfully!";
        } else {
            $_SESSION['error'] = "Item not found or no changes made.";
        }
    } else {
        error_log("Database error: " . $stmt->error);
        $_SESSION['error'] = "Failed to update item. Please try again.";
    }
    $stmt->close();
} else {
    error_log("Database error: " . $conn->error);
    $_SESSION['error'] = "Database error. Please try again.";
}

$conn->close();
header('Location: ../index.php');
exit();
?>