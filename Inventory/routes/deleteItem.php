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

// Validate item ID
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "Invalid item ID.";
    header('Location: ../index.php');
    exit();
}

// Delete using prepared statement
$stmt = $conn->prepare("DELETE FROM items WHERE id = ?");

if ($stmt) {
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            logAction($_SESSION['user_id'], 'item_deleted', $id);
            $_SESSION['success'] = "Item deleted successfully!";
        } else {
            $_SESSION['error'] = "Item not found.";
        }
    } else {
        error_log("Database error: " . $stmt->error);
        $_SESSION['error'] = "Failed to delete item. Please try again.";
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