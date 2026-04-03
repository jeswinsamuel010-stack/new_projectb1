-- User Side Module Database Schema
-- Material Request, Approvals, Issue Material

USE construction_erp1;

-- =====================================================
-- USER MATERIAL REQUESTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS user_material_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) UNIQUE NOT NULL,
    project_id INT NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    location VARCHAR(200),
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'issued') DEFAULT 'pending',
    requested_by INT,
    approved_by INT,
    approved_date DATETIME,
    approval_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- =====================================================
-- USER MATERIAL ISSUES TABLE (STOCK OUT)
-- =====================================================
CREATE TABLE IF NOT EXISTS user_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_number VARCHAR(50) UNIQUE NOT NULL,
    request_id INT NOT NULL,
    issue_date DATE NOT NULL,
    issue_quantity DECIMAL(10,2) NOT NULL,
    issued_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES user_material_requests(id),
    FOREIGN KEY (issued_by) REFERENCES users(id)
);

-- =====================================================
-- Add issue_quantity to inventory tracking
-- Note: buy_inventory already has total_issued field
-- No need to create new table, we update buy_inventory.total_issued