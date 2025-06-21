<?php
echo "<h1>POS System Test</h1>";
echo "<div style='max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    echo "<p>Database: " . $db->query("SELECT DATABASE()")->fetchColumn() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 2: Check Tables
echo "<h2>2. Database Tables Check</h2>";
try {
    $tables = ['tenants', 'users', 'categories', 'products', 'sales', 'sale_items', 'inventory_movements'];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking tables: " . $e->getMessage() . "</p>";
}

// Test 3: Check Products Table Structure
echo "<h2>3. Products Table Structure</h2>";
try {
    $result = $db->query("DESCRIBE products");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'tenant_id', 'name', 'cost_price', 'selling_price', 'stock_quantity'];
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] == $col) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "<p style='color: green;'>✓ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Column '$col' missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking products table: " . $e->getMessage() . "</p>";
}

// Test 4: Check Sample Data
echo "<h2>4. Sample Data Check</h2>";
try {
    // Check tenants
    $result = $db->query("SELECT COUNT(*) FROM tenants");
    $tenantCount = $result->fetchColumn();
    echo "<p>Tenants: $tenantCount</p>";
    
    // Check users
    $result = $db->query("SELECT COUNT(*) FROM users");
    $userCount = $result->fetchColumn();
    echo "<p>Users: $userCount</p>";
    
    // Check products
    $result = $db->query("SELECT COUNT(*) FROM products");
    $productCount = $result->fetchColumn();
    echo "<p>Products: $productCount</p>";
    
    if ($tenantCount > 0 && $userCount > 0) {
        echo "<p style='color: green;'>✓ Sample data exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No sample data found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking sample data: " . $e->getMessage() . "</p>";
}

// Test 5: Check Functions
echo "<h2>5. Functions Check</h2>";
try {
    require_once 'includes/functions.php';
    echo "<p style='color: green;'>✓ Functions file loaded</p>";
    
    // Test some functions
    if (function_exists('sanitize_input')) {
        echo "<p style='color: green;'>✓ sanitize_input function exists</p>";
    }
    if (function_exists('format_currency')) {
        echo "<p style='color: green;'>✓ format_currency function exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error loading functions: " . $e->getMessage() . "</p>";
}

// Test 6: Session Functions
echo "<h2>6. Session Functions Check</h2>";
try {
    require_once 'bootstrap-simple.php';
    
    if (function_exists('is_logged_in')) {
        echo "<p style='color: green;'>✓ is_logged_in function exists</p>";
    }
    if (function_exists('current_user')) {
        echo "<p style='color: green;'>✓ current_user function exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error loading session functions: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If tables are missing, run: <a href='/sari/fix-all-tables.php'>Fix Database Tables</a></li>";
echo "<li>Test login: <a href='/sari/login'>Login Page</a></li>";
echo "<li>Test dashboard: <a href='/sari/dashboard'>Dashboard</a></li>";
echo "<li>Test products: <a href='/sari/products'>Products</a></li>";
echo "</ul>";

echo "</div>";
?>