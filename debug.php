<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug: Starting...\n";

// Test 1: Check if .env file exists and can be read
if (file_exists(__DIR__ . '/.env')) {
    echo "Debug: .env file exists\n";
    $env = parse_ini_file(__DIR__ . '/.env');
    echo "Debug: .env parsed with " . count($env) . " variables\n";
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
} else {
    echo "Debug: .env file does not exist\n";
}

echo "Debug: DB_HOST = " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "Debug: DB_NAME = " . ($_ENV['DB_NAME'] ?? 'not set') . "\n";

// Test 2: Check autoloader
echo "Debug: Setting up autoloader...\n";
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    echo "Debug: Trying to load class '$class' from '$file'\n";
    if (file_exists($file)) {
        echo "Debug: File exists, including...\n";
        require_once $file;
        echo "Debug: File included successfully\n";
    } else {
        echo "Debug: File does not exist\n";
    }
});

// Test 3: Try to load functions
echo "Debug: Loading functions...\n";
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
    echo "Debug: Functions loaded\n";
} else {
    echo "Debug: Functions file not found\n";
}

// Test 4: Try to create Database instance
echo "Debug: Creating Database instance...\n";
try {
    $database = Database::getInstance();
    echo "Debug: Database instance created successfully\n";
} catch (Exception $e) {
    echo "Debug: Database creation failed: " . $e->getMessage() . "\n";
    echo "Debug: Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Debug: Complete\n";
?>