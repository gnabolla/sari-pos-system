-- Fix for missing selling_price column in products table
-- This script checks if the column exists and adds it if missing

USE sari_pos;

-- Check if selling_price column exists, if not add it
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cost_price;

-- Update any existing products that might have 0 selling_price
UPDATE products 
SET selling_price = cost_price * 1.4 
WHERE selling_price = 0 AND cost_price > 0;

-- Display the current structure to verify
DESCRIBE products;