<?php
echo "<h1>🧪 POS System Comprehensive Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
.test-section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
.pass { color: green; } .fail { color: red; } .warning { color: orange; } .info { color: blue; }
.button { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin: 5px; display: inline-block; }
</style>";

$allTestsPassed = true;

// Test 1: Database Setup
echo "<div class='test-section'>";
echo "<h2>🗄️ Database & Configuration Test</h2>";
try {
    require_once 'config/database.php';
    echo "<p class='pass'>✓ Database connection successful</p>";
    
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "<p class='info'>Database: $dbName</p>";
    
    // Check required tables
    $requiredTables = ['tenants', 'users', 'categories', 'products', 'sales', 'sale_items', 'inventory_movements'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<p class='pass'>✓ Table '$table' exists</p>";
        } else {
            echo "<p class='fail'>✗ Table '$table' missing</p>";
            $missingTables[] = $table;
            $allTestsPassed = false;
        }
    }
    
    if (!empty($missingTables)) {
        echo "<p class='warning'>⚠ Missing tables found. <a href='/sari/fix-all-tables.php' class='button'>Fix Database</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ Database error: " . $e->getMessage() . "</p>";
    $allTestsPassed = false;
}
echo "</div>";

// Test 2: Core Files
echo "<div class='test-section'>";
echo "<h2>📁 Core Files Test</h2>";
$coreFiles = [
    'index.php' => 'Main router',
    'login.php' => 'Login page',
    'dashboard.php' => 'Dashboard',
    'products.php' => 'Products management',
    'pos.php' => 'Point of Sale',
    'inventory.php' => 'Inventory management',
    'includes/functions.php' => 'Core functions',
    'assets/js/pos-manager.js' => 'POS JavaScript',
    'views/header.php' => 'Header template',
    'views/footer.php' => 'Footer template'
];

foreach ($coreFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='pass'>✓ $description ($file)</p>";
    } else {
        echo "<p class='fail'>✗ Missing: $description ($file)</p>";
        $allTestsPassed = false;
    }
}
echo "</div>";

// Test 3: Sample Data
echo "<div class='test-section'>";
echo "<h2>📊 Sample Data Test</h2>";
try {
    // Check for sample data
    $tenantCount = $db->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $productCount = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $categoryCount = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    echo "<p class='info'>📈 Data Summary:</p>";
    echo "<ul>";
    echo "<li>Tenants: $tenantCount</li>";
    echo "<li>Users: $userCount</li>";
    echo "<li>Categories: $categoryCount</li>";
    echo "<li>Products: $productCount</li>";
    echo "</ul>";
    
    if ($tenantCount > 0 && $userCount > 0) {
        echo "<p class='pass'>✓ Sample data exists - system ready for testing</p>";
        
        // Get sample credentials
        $user = $db->query("SELECT username, email FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "<p class='info'>🔑 Sample login credentials:</p>";
            echo "<ul><li>Username: <strong>" . $user['username'] . "</strong></li>";
            echo "<li>Password: <strong>admin123</strong> (default)</li></ul>";
        }
    } else {
        echo "<p class='warning'>⚠ No sample data found - you'll need to create accounts manually</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ Error checking sample data: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Routing & PWA Cleanup
echo "<div class='test-section'>";
echo "<h2>🔄 Routing & PWA Cleanup Test</h2>";

// Check if PWA files are removed
$pwaFiles = ['sw.js', 'manifest.json', 'offline.html', 'js/offline-storage.js', 'js/network-manager.js'];
$pwaFound = [];

foreach ($pwaFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $pwaFound[] = $file;
    }
}

if (empty($pwaFound)) {
    echo "<p class='pass'>✓ PWA files successfully removed</p>";
} else {
    echo "<p class='fail'>✗ PWA files still present: " . implode(', ', $pwaFound) . "</p>";
    $allTestsPassed = false;
}

// Test routes
echo "<p class='info'>🧭 Testing key routes:</p>";
$routes = [
    '/sari/' => 'Homepage/Dashboard',
    '/sari/login' => 'Login page',
    '/sari/products' => 'Products page',
    '/sari/pos' => 'POS page',
    '/sari/inventory' => 'Inventory page'
];

foreach ($routes as $route => $description) {
    echo "<li><a href='$route' target='_blank'>$description</a></li>";
}
echo "</div>";

// Test 5: JavaScript Functionality
echo "<div class='test-section'>";
echo "<h2>⚡ JavaScript Test</h2>";
if (file_exists(__DIR__ . '/assets/js/pos-manager.js')) {
    $jsContent = file_get_contents(__DIR__ . '/assets/js/pos-manager.js');
    
    // Check if PWA code is removed
    $pwaTerms = ['serviceWorker', 'offlineStorage', 'syncOfflineSales', 'networkManager'];
    $foundPwaTerms = [];
    
    foreach ($pwaTerms as $term) {
        if (strpos($jsContent, $term) !== false) {
            $foundPwaTerms[] = $term;
        }
    }
    
    if (empty($foundPwaTerms)) {
        echo "<p class='pass'>✓ PWA code removed from JavaScript</p>";
    } else {
        echo "<p class='fail'>✗ PWA code still present: " . implode(', ', $foundPwaTerms) . "</p>";
        $allTestsPassed = false;
    }
    
    // Check core POS functions
    $coreFunctions = ['addToCart', 'removeFromCart', 'processCheckout', 'filterProducts'];
    $missingFunctions = [];
    
    foreach ($coreFunctions as $func) {
        if (strpos($jsContent, $func) !== false) {
            echo "<p class='pass'>✓ Core function '$func' present</p>";
        } else {
            $missingFunctions[] = $func;
        }
    }
    
    if (!empty($missingFunctions)) {
        echo "<p class='fail'>✗ Missing core functions: " . implode(', ', $missingFunctions) . "</p>";
        $allTestsPassed = false;
    }
} else {
    echo "<p class='fail'>✗ POS Manager JavaScript file missing</p>";
    $allTestsPassed = false;
}
echo "</div>";

// Test Summary
echo "<div class='test-section'>";
echo "<h2>📋 Test Summary</h2>";
if ($allTestsPassed) {
    echo "<p class='pass' style='font-size: 18px; font-weight: bold;'>🎉 All tests passed! System is ready for use.</p>";
    echo "<h3>🚀 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='/sari/login' class='button'>Test Login</a></li>";
    echo "<li><a href='/sari/products' class='button'>Add Products</a></li>";
    echo "<li><a href='/sari/pos' class='button'>Test POS</a></li>";
    echo "<li><a href='/sari/inventory' class='button'>Test Inventory</a></li>";
    echo "</ol>";
} else {
    echo "<p class='fail' style='font-size: 18px; font-weight: bold;'>❌ Some tests failed. Please fix the issues above.</p>";
    echo "<h3>🔧 Quick Fixes:</h3>";
    echo "<ul>";
    echo "<li><a href='/sari/fix-all-tables.php' class='button'>Fix Database Tables</a></li>";
    echo "<li><a href='/sari/test-system.php' class='button'>Detailed System Test</a></li>";
    echo "</ul>";
}

echo "<h3>📖 Manual Testing Guide:</h3>";
echo "<ol>";
echo "<li><strong>Login Test:</strong> Go to <a href='/sari/login'>/sari/login</a> and use the sample credentials</li>";
echo "<li><strong>Product Test:</strong> Add a new product in <a href='/sari/products'>/sari/products</a></li>";
echo "<li><strong>POS Test:</strong> Try adding products to cart and processing a sale in <a href='/sari/pos'>/sari/pos</a></li>";
echo "<li><strong>Inventory Test:</strong> Adjust stock levels in <a href='/sari/inventory'>/sari/inventory</a></li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;'>";
echo "<p><strong>💡 Pro Tips:</strong></p>";
echo "<ul>";
echo "<li>If database tables are missing, run the fix script first</li>";
echo "<li>Use browser developer tools (F12) to check for JavaScript errors</li>";
echo "<li>Check PHP error logs if pages show errors</li>";
echo "<li>PWA features have been completely removed - system runs as traditional web app</li>";
echo "</ul>";
echo "</div>";
?>