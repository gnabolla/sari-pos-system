<?php
// Fix all missing tables in the database
require_once 'config/database.php';

echo "<h2>Database Tables Fix</h2>";

try {
    // Check and create inventory_movements table if it doesn't exist
    $checkTable = "SHOW TABLES LIKE 'inventory_movements'";
    $result = $db->query($checkTable);
    
    if ($result->rowCount() == 0) {
        echo "<p>Table 'inventory_movements' not found. Creating it now...</p>";
        
        $createTable = "CREATE TABLE inventory_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT NOT NULL,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
            quantity INT NOT NULL,
            reference_type ENUM('sale', 'purchase', 'adjustment', 'return') NOT NULL,
            reference_id INT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $db->exec($createTable);
        
        echo "<p style='color: green;'>✓ Table 'inventory_movements' created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>✓ Table 'inventory_movements' already exists.</p>";
    }
    
    // Check for selling_price column in products table
    $checkColumn = "SHOW COLUMNS FROM products LIKE 'selling_price'";
    $result = $db->query($checkColumn);
    
    if ($result->rowCount() == 0) {
        echo "<p>Column 'selling_price' not found in products table. Adding it now...</p>";
        
        $alterQuery = "ALTER TABLE products ADD COLUMN selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cost_price";
        $db->exec($alterQuery);
        
        echo "<p style='color: green;'>✓ Column 'selling_price' added successfully!</p>";
        
        // Update existing products
        $updateQuery = "UPDATE products SET selling_price = cost_price * 1.4 WHERE selling_price = 0 AND cost_price > 0";
        $db->exec($updateQuery);
        
        echo "<p style='color: green;'>✓ Existing products updated with selling prices.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Column 'selling_price' already exists in products table.</p>";
    }
    
    // Show all tables in the database
    echo "<h3>Current Tables in Database:</h3>";
    echo "<ul>";
    $showTables = "SHOW TABLES";
    $stmt = $db->query($showTables);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";
    
    echo "<br><p style='color: green; font-weight: bold;'>Database structure check complete!</p>";
    echo "<p><a href='/sari/dashboard'>Go to Dashboard</a> | <a href='/sari/products'>Go to Products</a> | <a href='/sari/inventory'>Go to Inventory</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>