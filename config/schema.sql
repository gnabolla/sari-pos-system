CREATE DATABASE IF NOT EXISTS sari_pos;
USE sari_pos;

-- Tenants table for multi-tenancy
CREATE TABLE tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE NOT NULL,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    address TEXT,
    plan ENUM('free', 'basic', 'premium', 'enterprise') DEFAULT 'free',
    trial_ends_at DATE DEFAULT NULL,
    subscription_ends_at DATE DEFAULT NULL,
    storage_used_mb INT DEFAULT 0,
    storage_limit_mb INT DEFAULT 100, -- 100MB free
    api_calls_this_month INT DEFAULT 0,
    api_calls_limit INT DEFAULT 1000, -- 1000 free API calls/month
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Product categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    barcode VARCHAR(100),
    unit VARCHAR(50) DEFAULT 'piece',
    cost_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 5,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Sales transactions
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_number VARCHAR(100) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    payment_method ENUM('cash', 'card', 'digital') DEFAULT 'cash',
    payment_status ENUM('paid', 'pending', 'cancelled') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sale items
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Inventory movements for tracking stock changes
CREATE TABLE inventory_movements (
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

-- Insert sample tenant
INSERT INTO tenants (name, subdomain, contact_email, contact_phone, address) VALUES 
('Sample Sari-Sari Store', 'sample', 'admin@sample.com', '09123456789', '123 Sample Street, Sample City');

-- Insert sample admin user (password: admin123)
INSERT INTO users (tenant_id, username, email, password, first_name, last_name, role) VALUES 
(1, 'admin', 'admin@sample.com', '$2y$10$7BrQOgQQFt9QRYzL.JXz7eH1NwKGJO.2O.BQ6o0cF6eL6pWLDNHWi', 'Admin', 'User', 'admin');

-- Insert sample categories
INSERT INTO categories (tenant_id, name, description) VALUES 
(1, 'Beverages', 'Soft drinks, juices, water'),
(1, 'Snacks', 'Chips, crackers, cookies'),
(1, 'Household', 'Cleaning supplies, toiletries'),
(1, 'Personal Care', 'Soap, shampoo, toothpaste');

-- Insert sample products
INSERT INTO products (tenant_id, category_id, name, barcode, unit, cost_price, selling_price, stock_quantity, reorder_level) VALUES 
(1, 1, 'Coca-Cola 330ml', '1234567890', 'bottle', 25.00, 35.00, 50, 10),
(1, 1, 'Sprite 330ml', '1234567891', 'bottle', 25.00, 35.00, 30, 10),
(1, 2, 'Piattos Cheese', '1234567892', 'pack', 15.00, 25.00, 40, 5),
(1, 3, 'Joy Dishwashing Liquid', '1234567893', 'bottle', 45.00, 65.00, 20, 3),
(1, 4, 'Safeguard Soap', '1234567894', 'bar', 20.00, 30.00, 25, 5);