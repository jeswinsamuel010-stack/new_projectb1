-- Purchase Orders Status Update
-- Run this SQL to add 'received' status to purchase_orders table

ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('ordered', 'received', 'completed', 'cancelled') DEFAULT 'ordered';

-- Verify the table structure
DESCRIBE purchase_orders;