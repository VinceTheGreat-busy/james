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
    die("CSRF token validation failed.");
}

// Sanitize and validate inputs
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

// Insert into database using prepared statement
$stmt = $conn->prepare(
    "INSERT INTO items 
    (name, quantity, type, issue, conditions, room, description, date, created_by, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);

if ($stmt) {
    $user_id = $_SESSION['user_id'];
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
        $user_id
    );

    if ($stmt->execute()) {
        logAction($user_id, 'item_added', $stmt->insert_id);
        $_SESSION['success'] = "Item added successfully!";
    } else {
        error_log("Database error: " . $stmt->error);
        $_SESSION['error'] = "Failed to add item. Please try again.";
    }
    $stmt->close();
} else {
    error_log("Database error: " . $conn->error);
    $_SESSION['error'] = "Database error. Please try again.";
}

$conn->query("INSERT INTO item_history (item_id, action, details, performed_by) 
            VALUES ($name, 'Added', 'Item name: $name', '$user_id')");

$conn->close();
header('Location: ../index.php');
exit();