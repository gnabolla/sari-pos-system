<?php
/**
 * Simple Database Setup Script
 * Creates all tables directly without complex migration system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sari_sari_pos';

// Helper functions
function output($message, $type = 'info') {
    $symbols = [
        'success' => '✓',
        'error' => '✗',
        'warning' => '⚠',
        'info' => 'ℹ'
    ];
    
    $symbol = isset($symbols[$type]) ? $symbols[$type] : '';
    echo "$symbol $message\n";
}

// Start output
echo "<pre>";
echo "=================================\n";
echo "Sari-Sari POS Database Setup\n";
echo "=================================\n\n";

try {
    // Step 1: Connect to MySQL without database
    output("Connecting to MySQL server...", 'info');
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    output("Connected to MySQL server", 'success');
    
    // Step 2: Create database if not exists
    output("\nCreating database...", 'info');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    output("Database '$db_name' ready", 'success');
    
    // Step 3: Connect to the database
    output("\nConnecting to database...", 'info');
    $pdo->exec("USE `$db_name`");
    output("Connected to database", 'success');
    
    // Step 4: Create tables
    output("\nCreating tables...", 'info');
    
    // Drop existing tables first (in reverse order due to foreign keys)
    $tables_to_drop = [
        'audit_logs',
        'user_sessions', 
        'settings',
        'inventory_transactions',
        'sale_items',
        'sales',
        'products',
        'suppliers',
        'categories',
        'users',
        'tenants'
    ];
    
    foreach ($tables_to_drop as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    
    // Create tenants table
    $pdo->exec("
        CREATE TABLE tenants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            subscription_plan ENUM('free', 'basic', 'premium') DEFAULT 'free',
            subscription_status ENUM('active', 'inactive', 'trial') DEFAULT 'trial',
            trial_ends_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    output("Created tenants table", 'success');
    
    // Create users table
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            role ENUM('admin', 'manager', 'cashier') DEFAULT 'cashier',
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            UNIQUE KEY unique_email_per_tenant (tenant_id, email)
        )
    ");
    output("Created users table", 'success');
    
    // Create categories table
    $pdo->exec("
        CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            UNIQUE KEY unique_category_per_tenant (tenant_id, name)
        )
    ");
    output("Created categories table", 'success');
    
    // Create suppliers table
    $pdo->exec("
        CREATE TABLE suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            contact_person VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(20),
            address TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        )
    ");
    output("Created suppliers table", 'success');
    
    // Create products table
    $pdo->exec("
        CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            category_id INT,
            supplier_id INT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            barcode VARCHAR(255),
            sku VARCHAR(255),
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            stock_quantity INT NOT NULL DEFAULT 0,
            reorder_level INT DEFAULT 10,
            unit VARCHAR(50) DEFAULT 'piece',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
            UNIQUE KEY unique_barcode_per_tenant (tenant_id, barcode),
            UNIQUE KEY unique_sku_per_tenant (tenant_id, sku),
            INDEX idx_tenant_status (tenant_id, status),
            INDEX idx_stock_level (stock_quantity, reorder_level)
        )
    ");
    output("Created products table", 'success');
    
    // Create sales table
    $pdo->exec("
        CREATE TABLE sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            user_id INT,
            transaction_number VARCHAR(255) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_method ENUM('cash', 'card', 'gcash', 'paymaya', 'bank_transfer') NOT NULL,
            payment_status ENUM('pending', 'paid', 'partial', 'refunded') DEFAULT 'pending',
            amount_paid DECIMAL(10,2) DEFAULT 0.00,
            change_amount DECIMAL(10,2) DEFAULT 0.00,
            notes TEXT,
            customer_name VARCHAR(255),
            customer_phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_transaction_per_tenant (tenant_id, transaction_number),
            INDEX idx_payment_status (payment_status),
            INDEX idx_created_date (created_at)
        )
    ");
    output("Created sales table", 'success');
    
    // Create sale_items table
    $pdo->exec("
        CREATE TABLE sale_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_sale_product (sale_id, product_id)
        )
    ");
    output("Created sale_items table", 'success');
    
    // Create inventory_transactions table
    $pdo->exec("
        CREATE TABLE inventory_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            product_id INT NOT NULL,
            user_id INT,
            transaction_type ENUM('stock_in', 'stock_out', 'adjustment', 'sale', 'return') NOT NULL,
            quantity INT NOT NULL,
            previous_quantity INT NOT NULL,
            new_quantity INT NOT NULL,
            unit_cost DECIMAL(10,2),
            reference_id INT,
            reference_type VARCHAR(50),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_product_date (product_id, created_at),
            INDEX idx_transaction_type (transaction_type)
        )
    ");
    output("Created inventory_transactions table", 'success');
    
    // Create settings table
    $pdo->exec("
        CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            setting_key VARCHAR(255) NOT NULL,
            setting_value TEXT,
            setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            UNIQUE KEY unique_setting_per_tenant (tenant_id, setting_key)
        )
    ");
    output("Created settings table", 'success');
    
    // Create user_sessions table
    $pdo->exec("
        CREATE TABLE user_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NOT NULL,
            tenant_id INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            INDEX idx_user_activity (user_id, last_activity)
        )
    ");
    output("Created user_sessions table", 'success');
    
    // Create audit_logs table
    $pdo->exec("
        CREATE TABLE audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            table_name VARCHAR(255),
            record_id INT,
            old_values JSON,
            new_values JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_tenant_date (tenant_id, created_at),
            INDEX idx_action (action)
        )
    ");
    output("Created audit_logs table", 'success');
    
    // Step 5: Insert default data
    output("\nInserting default data...", 'info');
    
    // Insert default tenant
    $pdo->exec("
        INSERT INTO tenants (name, email, phone, address, subscription_plan, subscription_status)
        VALUES ('Demo Store', 'demo@sarisaripos.com', '09123456789', '123 Main St, Manila', 'trial', 'trial')
    ");
    $tenantId = $pdo->lastInsertId();
    output("Created demo tenant", 'success');
    
    // Insert default admin user (password: admin123)
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT INTO users (tenant_id, email, password, full_name, role, status)
        VALUES ($tenantId, 'admin@demo.com', '$hashedPassword', 'Admin User', 'admin', 'active')
    ");
    output("Created admin user (email: admin@demo.com, password: admin123)", 'success');
    
    // Insert default categories
    $categories = ['Beverages', 'Snacks', 'Canned Goods', 'Personal Care', 'Household Items'];
    foreach ($categories as $category) {
        $pdo->exec("
            INSERT INTO categories (tenant_id, name, status)
            VALUES ($tenantId, '$category', 'active')
        ");
    }
    output("Created default categories", 'success');
    
    // Complete!
    output("\n=================================", 'success');
    output("✨ Database setup completed!", 'success');
    output("=================================", 'success');
    output("\nYou can now login with:", 'info');
    output("Email: admin@demo.com", 'info');
    output("Password: admin123", 'info');
    
} catch (Exception $e) {
    output("\nError: " . $e->getMessage(), 'error');
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "</pre>";

// Add link to go to app
if (php_sapi_name() !== 'cli') {
    echo '<br><a href="/sari/" class="btn btn-primary">Go to Application</a>';
}
?>