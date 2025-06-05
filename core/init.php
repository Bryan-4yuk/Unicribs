<?php
// Start session safely with proper settings
// Start session safely with proper settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => false, // Should be true in production with HTTPS
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
    
    // Initialize user_type if not set (for backward compatibility)
    if (!isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
        // You may need to query the database to get user_type
        // This is just a fallback
        $_SESSION['user_type'] = 'student'; // Default fallback
    }
}
// Define base URL
define('BASE_URL', 'http://localhost/UNICRIBS/');

// Database connection
require 'database/connection.php';

// Autoload classes
spl_autoload_register(function($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    } elseif (file_exists(__DIR__ . '/classes/' . strtolower($class) . '.php')) {
        require __DIR__ . '/classes/' . strtolower($class) . '.php';
    }
});
// Initialize classes
$user = new User($pdo);
$room = new Room($pdo);
$booking = new Booking($pdo);