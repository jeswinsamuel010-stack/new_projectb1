-- Construction ERP Database Schema
-- Run this file in phpMyAdmin or via command line

-- Create database
CREATE DATABASE IF NOT EXISTS construction_erp;
USE construction_erp;

-- Drop existing tables (in reverse order)
DROP TABLE IF EXISTS bills;
DROP TABLE IF EXISTS material_issues;
DROP TABLE IF EXISTS goods_receipts;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS material_requests;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'site_engineer', 'project_manager', 'purchase_team', 'store_keeper', 'accounts') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Centralized Approvals Table
CREATE TABLE approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_type ENUM('quotation', 'material_request', 'purchase_order', 'billing', 'payment') NOT NULL,
    module_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approval_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_approval (module_type, module_id)
);

-- Projects Table
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(200) NOT NULL,
    client_name VARCHAR(200) NOT NULL,
    project_value DECIMAL(15,2) NOT NULL,
    start_date DATE NOT NULL,
    description TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    enquiry_id INT,
    quotation_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Material Requests Table
CREATE TABLE material_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'ordered', 'received', 'issued') DEFAULT 'pending',
    requested_by INT,
    approved_by INT,
    approval_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Purchase Orders Table
CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_request_id INT NOT NULL,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_name VARCHAR(200) NOT NULL,
    order_date DATE NOT NULL,
    expected_date DATE,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('ordered', 'partial', 'completed', 'cancelled') DEFAULT 'ordered',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (material_request_id) REFERENCES material_requests(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Goods Receipts Table
CREATE TABLE goods_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    material_request_id INT NOT NULL,
    received_date DATE NOT NULL,
    received_quantity DECIMAL(10,2) NOT NULL,
    accepted_quantity DECIMAL(10,2) NOT NULL,
    rejected_quantity DECIMAL(10,2) DEFAULT 0,
    remarks TEXT,
    received_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (material_request_id) REFERENCES material_requests(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);

-- Inventory Table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(200) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    min_stock_level DECIMAL(10,2) DEFAULT 0,
    rate DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Material Issues Table
CREATE TABLE material_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_request_id INT NOT NULL,
    issue_date DATE NOT NULL,
    issue_quantity DECIMAL(10,2) NOT NULL,
    issued_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_request_id) REFERENCES material_requests(id),
    FOREIGN KEY (issued_by) REFERENCES users(id)
);

-- Bills Table
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    bill_number VARCHAR(50) UNIQUE NOT NULL,
    bill_date DATE NOT NULL,
    description TEXT,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert sample users (password is 'password123' for all)
INSERT INTO users (username, email, password, full_name, role, status) VALUES
('admin', 'admin@erp.com', '$2y$10$9vPHZHM3ngeyvllreUsByud8NbHgrBwpmDD7Y8VdNWzz6YDUIUBni', 'System Admin', 'admin', 'active'),
('site_eng', 'engineer@erp.com', '$2y$10$9vPHZHM3ngeyvllreUsByud8NbHgrBwpmDD7Y8VdNWzz6YDUIUBni', 'John Smith', 'site_engineer', 'active'),
('proj_mgr', 'manager@erp.com', '$2y$10$9vPHZHM3ngeyvllreUsByud8NbHgrBwpmDD7Y8VdNWzz6YDUIUBni', 'Sarah Johnson', 'project_manager', 'active'),
('purchase', 'purchase@erp.com', '$2y$10$9vPHZHM3ngeyvllreUsByud8NbHgrBwpmDD7Y8VdNWzz6YDUIUBni', 'Mike Wilson', 'purchase_team', 'active'),
('store_kpr', 'store@erp.com', '$2y$10$9vPHZHM3ngeyvllreUsByud8NbHgrBwpmDD7Y8VdNWzz6YDUIUBni', 'David Brown', 'store_keeper', 'active'),
('accounts', 'accounts@erp.com', '$2y$10$9vPHZHM3ngeyvllreUsByud8NbHgrBwpmDD7Y8VdNWzz6YDUIUBni', 'Lisa Davis', 'accounts', 'active');

-- Insert sample projects
INSERT INTO projects (project_name, client_name, project_value, start_date, description, status, created_by) VALUES
('Skyline Tower', 'ABC Corp', 5000000.00, '2025-01-15', 'Commercial building with 20 floors including retail and office spaces', 'active', 1),
('Riverside Mall', 'XYZ Ltd', 3500000.00, '2025-03-01', 'Shopping complex with 100 retail units and parking facility', 'active', 1),
('Highway Bridge', 'Govt Department', 8000000.00, '2024-11-01', 'Major bridge construction over river connecting two cities', 'active', 1);

-- Insert sample material requests
INSERT INTO material_requests (project_id, item_name, quantity, unit, reason, status, requested_by) VALUES
(1, 'Cement (PPC)', 500, 'bags', 'Foundation work and column casting', 'pending', 2),
(1, 'Steel Bars (12mm)', 200, 'pieces', 'Column reinforcement', 'pending', 2),
(2, 'Bricks', 10000, 'pieces', 'Wall construction', 'approved', 2),
(2, 'Sand', 50, 'cu.mt', 'Mortar preparation', 'approved', 2),
(3, 'Aggregates', 100, 'cu.mt', 'Concrete mixing', 'approved', 2);

-- Insert sample inventory items
INSERT INTO inventory (item_name, unit, current_stock, min_stock_level, rate) VALUES
('Cement (PPC)', 'bags', 1000, 100, 350),
('Steel Bars (12mm)', 'pieces', 500, 50, 450),
('Steel Bars (16mm)', 'pieces', 300, 50, 650),
('Bricks', 'pieces', 50000, 5000, 8),
('Sand', 'cu.mt', 200, 20, 1200),
('Aggregates', 'cu.mt', 150, 20, 900),
('Paint (Interior)', 'liter', 500, 50, 250),
('Tiles', 'sq.ft', 2000, 200, 45);

-- Insert sample purchase orders
INSERT INTO purchase_orders (material_request_id, po_number, supplier_name, order_date, expected_date, total_amount, status, created_by) VALUES
(3, 'PO-001', 'ABC Supplies Ltd', '2025-02-15', '2025-02-25', 80000.00, 'ordered', 4),
(4, 'PO-002', 'River Sand Co', '2025-02-16', '2025-02-22', 60000.00, 'completed', 4);

-- Insert sample goods receipts
INSERT INTO goods_receipts (purchase_order_id, material_request_id, received_date, received_quantity, accepted_quantity, rejected_quantity, remarks, received_by) VALUES
(2, 4, '2025-02-20', 50, 48, 2, 'Minor quality issues in 2 units', 5);

-- Insert sample material issues
INSERT INTO material_issues (material_request_id, issue_date, issue_quantity, issued_by) VALUES
(4, '2025-02-21', 45, 5);

-- Insert sample bills
INSERT INTO bills (project_id, bill_number, bill_date, description, amount, status, created_by) VALUES
(1, 'BILL-001', '2025-02-28', 'Phase 1 - Foundation completion', 500000.00, 'pending', 6),
(2, 'BILL-002', '2025-02-25', 'Ground floor completion', 350000.00, 'paid', 6);