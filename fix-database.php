<?php
// Fix database structure issues
require_once 'config/database.php';

echo "<h2>Database Structure Fix</h2>";

try {
    // Check if selling_price column exists
    $checkQuery = "SHOW COLUMNS FROM products LIKE 'selling_price'";
    $result = $db->query($checkQuery);
    
    if ($result->rowCount() == 0) {
        echo "<p>Column 'selling_price' not found. Adding it now...</p>";
        
        // Add the selling_price column
        $alterQuery = "ALTER TABLE products ADD COLUMN selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cost_price";
        $db->exec($alterQuery);
        
        echo "<p style='color: green;'>✓ Column 'selling_price' added successfully!</p>";
        
        // Update existing products to have a selling price
        $updateQuery = "UPDATE products SET selling_price = cost_price * 1.4 WHERE selling_price = 0 AND cost_price > 0";
        $db->exec($updateQuery);
        
        echo "<p style='color: green;'>✓ Existing products updated with selling prices.</p>";
    } else {
        echo "<p style='color: blue;'>✓ Column 'selling_price' already exists.</p>";
    }
    
    // Show current table structure
    echo "<h3>Current Products Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $describeQuery = "DESCRIBE products";
    $stmt = $db->query($describeQuery);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p style='color: green; font-weight: bold;'>Database structure check complete!</p>";
    echo "<p><a href='/sari/products'>Go back to Products page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>