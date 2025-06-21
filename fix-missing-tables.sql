-- Fix missing tables for sari_sari_pos database
USE sari_sari_pos;

-- Create inventory_movements table if it doesn't exist
CREATE TABLE IF NOT EXISTS inventory_movements (
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
);

-- Add selling_price column to products table if it doesn't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cost_price;

-- Update any existing products that might have 0 selling_price
UPDATE products 
SET selling_price = cost_price * 1.4 
WHERE selling_price = 0 AND cost_price > 0;

-- Show tables to verify
SHOW TABLES;
DESCRIBE products;
DESCRIBE inventory_movements;