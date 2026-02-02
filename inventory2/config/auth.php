<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
}

/**
 * Require user to be logged in, redirect to login if not
 * FIXED: Now properly checks the current directory structure
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        // Get the current script path
        $currentPath = $_SERVER['SCRIPT_NAME'];
        
        // Determine the correct login URL based on directory depth
        if (strpos($currentPath, '/views/') !== false) {
            // We're in views folder, go up one level
            $loginUrl = '../config/login.php';
        } elseif (strpos($currentPath, '/config/') !== false) {
            // We're in config folder, stay in same level
            $loginUrl = 'login.php';
        } else {
            // We're in root, go into config
            $loginUrl = 'config/login.php';
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }
}

/**
 * Get the correct login URL based on current path
 */
function getLoginUrl()
{
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    
    if (strpos($scriptPath, '/views/') !== false) {
        return '../config/login.php';
    } elseif (strpos($scriptPath, '/config/') !== false) {
        return 'login.php';
    }
    
    return 'config/login.php';
}

/**
 * Login user and set session data
 * FIXED: Now properly accepts user array parameter
 */
function login($user)
{
    // Handle both array and individual parameters for backward compatibility
    if (is_array($user)) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'room_id' => $user['room_id'] ?? null,
            'floor_id' => $user['floor_id'] ?? null,
            'floor' => $user['floor'] ?? null
        ];
    } else {
        // Legacy support: if called with individual parameters
        $args = func_get_args();
        $_SESSION['user'] = [
            'id' => $args[0] ?? null,
            'username' => $args[1] ?? null,
            'email' => $args[2] ?? null,
            'room_id' => $args[3] ?? null,
            'floor_id' => $args[4] ?? null,
            'floor' => $args[5] ?? null
        ];
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Logout user and destroy session
 */
function logout()
{
    // Clear session variables
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login
    header('Location: ' . getLoginUrl());
    exit;
}

/**
 * Get current logged-in user data
 */
function getCurrentUser()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user ID
 */
function getCurrentUserId()
{
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Check if current user has a specific role
 * (For future implementation if roles are added)
 */
function hasRole($role)
{
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}

/**
 * Log user actions to audit trail
 * IMPROVED: Added error handling and IP tracking
 */
function logAction($conn, $userId, $action, $itemId = null, $roomId = null)
{
    try {
        // Get client IP and user agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $conn->prepare("
            INSERT INTO audit_log (user_id, action, item_id, room_id, timestamp, ip_address, user_agent)
            VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ");
        
        $stmt->bind_param("isiiss", $userId, $action, $itemId, $roomId, $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log action: " . $e->getMessage());
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

/**
 * Generate CSRF token
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token (call after successful form submission)
 */
function regenerateCSRFToken()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/*
|--------------------------------------------------------------------------
| SANITIZATION & VALIDATION
|--------------------------------------------------------------------------
*/

/**
 * Sanitize input to prevent XSS
 */
function sanitizeInput($input)
{
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(htmlspecialchars(stripslashes($input ?? ''), ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate email format
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate quantity (positive integer)
 */
function validateQuantity($qty)
{
    return is_numeric($qty) && (int)$qty > 0 && (int)$qty == $qty;
}

/**
 * Validate date format (YYYY-MM-DD HH:MM:SS)
 */
function validateDateTime($datetime)
{
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate username (alphanumeric and underscore only, 3-20 chars)
 */
function validateUsername($username)
{
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

/**
 * Validate password strength
 */
function validatePassword($password)
{
    // At least 8 characters, one uppercase, one lowercase, one number
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Sanitize and validate ID
 */
function sanitizeId($id)
{
    return filter_var($id, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

/**
 * Sanitize string for SQL LIKE queries
 */
function sanitizeLikeString($string)
{
    return str_replace(['%', '_'], ['\\%', '\\_'], $string);
}

/*
|--------------------------------------------------------------------------
| SECURITY HELPERS
|--------------------------------------------------------------------------
*/

/**
 * Check for suspicious activity
 */
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300)
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $attempts = $_SESSION['rate_limit'][$key] ?? [];
    
    // Remove old attempts outside time window
    $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Check if exceeded
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    
    // Add new attempt
    $attempts[] = $now;
    $_SESSION['rate_limit'][$key] = $attempts;
    
    return true;
}

/**
 * Clear rate limit for a specific key
 */
function clearRateLimit($key)
{
    if (isset($_SESSION['rate_limit'][$key])) {
        unset($_SESSION['rate_limit'][$key]);
    }
}

/**
 * Prevent clickjacking attacks
 */
function setSecurityHeaders()
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Call requireLogin() at the end of files that need authentication
// Example: require_once 'auth.php'; requireLogin();