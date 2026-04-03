-- Buy Side Module Database Schema
-- Run this file in phpMyAdmin or via command line

USE construction_erp1;

-- =====================================================
-- PURCHASE ORDERS TABLE (BUY SIDE)
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    project_id INT NOT NULL,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_date DATE,
    status ENUM('draft', 'sent', 'acknowledged', 'partial', 'completed', 'cancelled') DEFAULT 'draft',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- =====================================================
-- PURCHASE ORDER ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_po_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    received_qty DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (po_id) REFERENCES buy_purchase_orders(id) ON DELETE CASCADE
);

-- =====================================================
-- GOODS RECEIPT NOTES (GRN) TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_grn (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grn_number VARCHAR(50) UNIQUE NOT NULL,
    po_id INT,
    supplier_id INT NOT NULL,
    invoice_number VARCHAR(100),
    invoice_date DATE,
    received_date DATE NOT NULL,
    notes TEXT,
    status ENUM('draft', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES buy_purchase_orders(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- =====================================================
-- GRN ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_grn_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grn_id INT NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    accepted_qty DECIMAL(10,2) NOT NULL,
    rejected_qty DECIMAL(10,2) DEFAULT 0,
    rate DECIMAL(10,2) DEFAULT 0,
    amount DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (grn_id) REFERENCES buy_grn(id) ON DELETE CASCADE
);

-- =====================================================
-- BILLS TABLE (FROM GRN)
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(50) UNIQUE NOT NULL,
    grn_id INT,
    supplier_id INT NOT NULL,
    bill_date DATE NOT NULL,
    due_date DATE,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grn_id) REFERENCES buy_grn(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- =====================================================
-- BILL ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES buy_bills(id) ON DELETE CASCADE
);

-- =====================================================
-- PAYMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(50) UNIQUE NOT NULL,
    bill_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'cheque', 'upi', 'other') DEFAULT 'bank_transfer',
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES buy_bills(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- =====================================================
-- INVENTORY TABLE (BUY SIDE - Only GRN adds stock)
-- =====================================================
CREATE TABLE IF NOT EXISTS buy_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(200) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    total_received DECIMAL(10,2) DEFAULT 0,
    total_issued DECIMAL(10,2) DEFAULT 0,
    min_stock_level DECIMAL(10,2) DEFAULT 0,
    rate DECIMAL(10,2) DEFAULT 0,
    last_grn_id INT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- SUPPLIERS (ADD EXTRA FIELDS IF NEEDED)
-- =====================================================
-- Note: suppliers table already exists from previous implementation