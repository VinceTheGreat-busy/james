<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user']['id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: config/login.php');
        exit;
    }
}

function login($user)
{
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ];
}

function logout()
{
    session_destroy();
    header('Location: index.php');
    exit;
}

function logAction($conn, $userId, $action, $itemId = null, $roomId = null)
{
    $stmt = $conn->prepare("
        INSERT INTO audit_log (user_id, action, item_id, room_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isii", $userId, $action, $itemId, $roomId);
    $stmt->execute();
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

// Generate CSRF token
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


/*
|--------------------------------------------------------------------------
| SANITIZATION & VALIDATION
|--------------------------------------------------------------------------
*/

// Sanitize input
function sanitizeInput($input)
{
    return trim(htmlspecialchars(stripslashes($input), ENT_QUOTES, 'UTF-8'));
}

// Validate email
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate quantity (integer > 0)
function validateQuantity($qty)
{
    return is_numeric($qty) && (int) $qty > 0 && (int) $qty == $qty;
}

// Validate date (format: YYYY-MM-DD HH:MM)
function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d H:i', $date);
    return $d && $d->format('Y-m-d H:i') === $date;
}