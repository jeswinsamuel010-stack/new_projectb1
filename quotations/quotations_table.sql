-- Quotations Module Table
-- Add this table to the construction_erp database

-- Main Quotations Table
CREATE TABLE IF NOT EXISTS project_quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_number VARCHAR(50) NOT NULL UNIQUE,
    enquiry_id INT,
    client_name VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(255),
    project_title VARCHAR(255) NOT NULL,
    scope_description TEXT,
    estimated_amount DECIMAL(15,2) NOT NULL,
    valid_until DATE,
    status ENUM('Draft', 'Sent', 'Approved', 'Rejected', 'Converted') DEFAULT 'Draft',

    -- Approval tracking
    approved_by INT NULL,
    approved_date DATETIME NULL,
    approval_notes TEXT NULL,

    -- Public sharing
    public_token VARCHAR(64) NULL,
    sent_to_client_at DATETIME NULL,

    -- Metadata
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE SET NULL,
    INDEX idx_enquiry_id (enquiry_id),
    INDEX idx_status (status),
    INDEX idx_quotation_number (quotation_number),
    INDEX idx_public_token (public_token)
);

-- If table already exists, run ALTER to add new columns
-- ALTER TABLE project_quotations ADD COLUMN approved_by INT NULL AFTER status;
-- ALTER TABLE project_quotations ADD COLUMN approved_date DATETIME NULL AFTER approved_by;
-- ALTER TABLE project_quotations ADD COLUMN approval_notes TEXT NULL AFTER approved_date;
-- ALTER TABLE project_quotations ADD COLUMN public_token VARCHAR(64) NULL AFTER approval_notes;
-- ALTER TABLE project_quotations ADD COLUMN sent_to_client_at DATETIME NULL AFTER public_token;
-- ALTER TABLE project_quotations MODIFY COLUMN status ENUM('Draft', 'Sent', 'Approved', 'Rejected', 'Converted') DEFAULT 'Draft';