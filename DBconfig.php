<?php
/**
 * Database Connection File for Munch - Healthy Bakery Recipe Website
 * Using PDO (PHP Data Objects) 
 */


define('DBHOST', 'localhost');
define('DBNAME', 'munch');
define('DBUSER', 'root');
define('DBPASS', 'root');
define('DBPORT', '8889'); 

// ============================================
// Connecting with PDO (Object-Oriented)
// ============================================

$connectionString = "mysql:host=" . DBHOST . ";port=" . DBPORT . ";dbname=" . DBNAME . ";charset=utf8mb4";

// PDO options for better security and error handling
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // Use exceptions for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch as associative array
    PDO::ATTR_EMULATE_PREPARES => false,              // Use real prepared statements 
    PDO::ATTR_PERSISTENT => false
];

// ============================================
//  Handling Connection Errors 
// ============================================
try {
    // Create PDO connection 
    $pdo = new PDO($connectionString, DBUSER, DBPASS, $options);
    
    // Set connection to use UTF-8
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
} catch (PDOException $e) {
    // Handle connection errors
    // Log error for debugging 
    error_log("Database Connection Error: " . $e->getMessage());
    
    // User-friendly error message 
    die("<div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
            <h2 style='color: #e74c3c;'>️ Database Connection Error</h2>
            <p>Sorry, we're experiencing technical difficulties. Please try again later.</p>
            <p style='color: #7f8c8d; font-size: 14px;'>Error: " . $e->getMessage() . "</p>
        </div>");
}

// ============================================
// Session State Management
// ============================================
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// Helper Functions
// ============================================

/**
 * Check if user is logged in
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        header("Location: login.php");
        exit();
    }
}

/**
 *  Check if user is admin (for authorization)
 */
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        $_SESSION['error_message'] = "Access denied. Admin privileges required.";
        header("Location: login.php");
        exit();
    }
}

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 *  Helper function for prepared statements
 */
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);  // Slide 30: Prepare statement
        $stmt->execute($params);       // Execute with parameters
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 *  File upload helper
 */
function uploadFile($file, $targetDir, $userID) {
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Generate unique filename
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $timestamp = time();
    $fileName = "user_{$userID}_{$timestamp}." . $fileExtension;
    $targetPath = $targetDir . $fileName;
    
    // Check if image file 
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($fileExtension, $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }
    }
    return false;
}

/**
 *  Cookie helper functions
 */
function setRememberMeCookie($userId) {
    $expiryTime = time() + (30 * 24 * 60 * 60); // 30 days
    $token = bin2hex(random_bytes(32));
    
    // Store token in database 
    // Then set cookie
    setcookie('remember_token', $token, $expiryTime, '/', '', false, true);
}

/**
 * Helper for search functionality
 */
function getSearchValue($key, $default = '') {
    return isset($_GET[$key]) ? sanitizeInput($_GET[$key]) : $default;
}

/**
 * Redirect with message (using cookies/session)
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION[$type . '_message'] = $message;
    header("Location: $url");
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Riyadh');

?>
