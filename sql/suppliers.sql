-- Suppliers table for PDF Import feature
-- Run this file in phpMyAdmin or via command line

USE construction_erp1;

-- Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) UNIQUE NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add columns to goods_receipts for tracking invoice info
ALTER TABLE goods_receipts ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(50) AFTER purchase_order_id;
ALTER TABLE goods_receipts ADD COLUMN IF NOT EXISTS invoice_date DATE AFTER invoice_number;

-- Insert sample suppliers
INSERT INTO suppliers (name, contact_person, phone, email, address, status) VALUES
('ABC Supplies Ltd', 'Rajesh Kumar', '9876543210', 'rajesh@abcsupplies.com', '123 Industrial Area, Mumbai', 'active'),
('River Sand Co', 'Amit Patel', '9876543211', 'amit@riversand.com', '45 River Bank Road, Thane', 'active'),
('Steel Masters', 'Sanjay Sharma', '9876543212', 'sanjay@steelmasters.com', '78 Steel Colony, Navi Mumbai', 'active'),
('Cement India Ltd', 'Vijay Gupta', '9876543213', 'vijay@cementindia.com', '92 Construction Park, Mumbai', 'active'),
('Bricks & Tiles Co', 'Rahul Singh', '9876543214', 'rahul@bricksandtiles.com', '15 Quarry Road, Panvel', 'active'),
('Paint World', 'Mohammad Khan', '9876543215', 'mohammad@paintworld.com', '67 Decor Lane, Andheri', 'active'),
('Hardware Hub', 'Devendra Yadav', '9876543216', 'devendra@hardwarehub.com', '33 Metal Market, Dadar', 'active'),
('Aggregate Solutions', 'Pravin Joshi', '9876543217', 'pravin@aggregatesol.com', '51 Quarry Lane, Kalyan', 'active');