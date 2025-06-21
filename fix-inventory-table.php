<?php
require_once 'config/database.php';

try {
    // Create inventory_movements table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS inventory_movements (
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
    
    $db->exec($sql);
    echo "inventory_movements table created successfully!\n";
    
    // Check if table exists and has data
    $count = $db->query("SELECT COUNT(*) FROM inventory_movements")->fetchColumn();
    echo "Table has $count records.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>