<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Require login - redirect if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBasePath() . 'config/login.php');
        exit();
    }
}

// Store user session after login
function login($user_id, $username, $email) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['login_time'] = time();
}

// Logout user
function logout() {
    session_destroy();
    header('Location: ' . getBasePath() . 'index.php');
    exit();
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($input) {
    return trim(htmlspecialchars(stripslashes($input), ENT_QUOTES, 'UTF-8'));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate quantity
function validateQuantity($qty) {
    return is_numeric($qty) && $qty > 0 && (int)$qty == $qty;
}

// Validate date format
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d H:i', $date);
    return $d && $d->format('Y-m-d H:i') === $date;
}

// Get base path for redirects
function getBasePath() {
    $basePath = dirname($_SERVER['PHP_SELF']);
    return rtrim($basePath, '/') . '/';
}

// Log action to audit trail
function logAction($user_id, $action, $item_id = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, item_id, timestamp) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isi", $user_id, $action, $item_id);
        $stmt->execute();
        $stmt->close();
    }
}