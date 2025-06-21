<?php
/**
 * Simple Bootstrap for transition period
 * Minimal changes to work with existing system
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load existing database configuration
require_once __DIR__ . '/config/database.php';

// Load existing functions
require_once __DIR__ . '/includes/functions.php';

// Simple autoloader for new classes (but don't instantiate them yet)
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Keep the global $db for now (backward compatibility)
global $db;

// Helper functions for gradual migration
function db_connection() {
    global $db;
    return $db;
}

// Simple auth helper that works with existing session
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['tenant_id']);
}

function require_login($redirect = '/sari/login') {
    if (!is_logged_in()) {
        header("Location: $redirect");
        exit();
    }
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'tenant_id' => $_SESSION['tenant_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}
?>