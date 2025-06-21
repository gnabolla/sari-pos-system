<?php
/**
 * Application Bootstrap
 * Initialize core services and dependencies
 */

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila');

// Autoloader for classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load legacy functions for backward compatibility
require_once __DIR__ . '/includes/functions.php';

// Initialize core services
try {
    $database = Database::getInstance();
    $auth = new Auth($database);
} catch (Exception $e) {
    error_log("Bootstrap error: " . $e->getMessage());
    echo "Error initializing application: " . $e->getMessage();
    die();
}

// Make services globally available for transition period
$GLOBALS['db'] = $database->getConnection(); // For backward compatibility
$GLOBALS['database'] = $database;
$GLOBALS['auth'] = $auth;

// Global helper functions
function db(): Database {
    return $GLOBALS['database'];
}

function auth(): Auth {
    return $GLOBALS['auth'];
}

function user(): ?array {
    return auth()->getUser();
}

function redirect(string $url): void {
    header("Location: {$url}");
    exit();
}

function csrf_token(): string {
    return auth()->generateCsrfToken();
}

function csrf_field(): string {
    $token = csrf_token();
    return "<input type='hidden' name='_token' value='{$token}'>";
}

function old(string $key, $default = ''): string {
    return $_SESSION['old_input'][$key] ?? $default;
}

function flash(string $key, $value = null) {
    if ($value === null) {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    
    $_SESSION['flash'][$key] = $value;
}

function session(string $key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

function config(string $key, $default = null) {
    $keys = explode('.', $key);
    $config = $_ENV;
    
    foreach ($keys as $segment) {
        if (!isset($config[$segment])) {
            return $default;
        }
        $config = $config[$segment];
    }
    
    return $config;
}

function asset(string $path): string {
    $baseUrl = $_ENV['APP_URL'] ?? '';
    return rtrim($baseUrl, '/') . '/assets/' . ltrim($path, '/');
}

function url(string $path = ''): string {
    $baseUrl = $_ENV['APP_URL'] ?? '/sari';
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

// Error handlers
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error = "Error: {$message} in {$file} on line {$line}";
    error_log($error);
    
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>Error:</strong> {$message}<br>";
        echo "<strong>File:</strong> {$file}<br>";
        echo "<strong>Line:</strong> {$line}";
        echo "</div>";
    }
    
    return true;
});

set_exception_handler(function ($exception) {
    $error = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($error);
    
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Trace:</strong><pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "An error occurred. Please try again later.";
        echo "</div>";
    }
});

// Check session timeout for authenticated users
if (auth()->isLoggedIn()) {
    auth()->checkTimeout();
}