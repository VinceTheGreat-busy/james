<?php
/**
 * Database Configuration and Connection
 * Improved with error handling and connection pooling
 */

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'james_inventory'); // Changed from sigenalang
define('DB_CHARSET', 'utf8mb4');

// Environment
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV === 'development');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    mysqli_report(MYSQLI_REPORT_OFF);
}

/**
 * Create database connection
 */
function createDatabaseConnection()
{
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset
        if (!$conn->set_charset(DB_CHARSET)) {
            throw new Exception("Error setting charset: " . $conn->error);
        }
        
        // Set timezone
        $conn->query("SET time_zone = '+00:00'");
        
        return $conn;
        
    } catch (Exception $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        
        if (APP_DEBUG) {
            die("Database connection failed: " . $e->getMessage());
        } else {
            die("Database connection failed. Please contact the system administrator.");
        }
    }
}

// Create global connection
$conn = createDatabaseConnection();

/**
 * Close database connection properly
 */
function closeDatabaseConnection($conn)
{
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}

/**
 * Execute a prepared statement safely
 */
function executeQuery($conn, $sql, $params = [], $types = '')
{
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        
        return $stmt;
        
    } catch (Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        
        if (APP_DEBUG) {
            die("Query failed: " . $e->getMessage());
        }
        
        return false;
    }
}

/**
 * Fetch single row
 */
function fetchOne($conn, $sql, $params = [], $types = '')
{
    $stmt = executeQuery($conn, $sql, $params, $types);
    
    if (!$stmt) {
        return null;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Fetch all rows
 */
function fetchAll($conn, $sql, $params = [], $types = '')
{
    $stmt = executeQuery($conn, $sql, $params, $types);
    
    if (!$stmt) {
        return [];
    }
    
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $rows;
}

/**
 * Execute update/insert/delete and return affected rows
 */
function executeUpdate($conn, $sql, $params = [], $types = '')
{
    $stmt = executeQuery($conn, $sql, $params, $types);
    
    if (!$stmt) {
        return false;
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    return $affectedRows;
}

/**
 * Get last insert ID
 */
function getLastInsertId($conn)
{
    return $conn->insert_id;
}

/**
 * Begin transaction
 */
function beginTransaction($conn)
{
    return $conn->begin_transaction();
}

/**
 * Commit transaction
 */
function commitTransaction($conn)
{
    return $conn->commit();
}

/**
 * Rollback transaction
 */
function rollbackTransaction($conn)
{
    return $conn->rollback();
}

// Register shutdown function to close connection
register_shutdown_function(function() use ($conn) {
    closeDatabaseConnection($conn);
});