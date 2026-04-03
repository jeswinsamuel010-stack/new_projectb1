<?php
// Run this file once to create the required tables
require_once '../config/db.php';

$sql = "
-- Project Milestones Table
CREATE TABLE IF NOT EXISTS project_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    phase_name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    plan_type ENUM('month', 'week') DEFAULT 'month',
    plan_number INT DEFAULT 1,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id),
    INDEX idx_status (status),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Milestone Plans Table (Execution Plan)
CREATE TABLE IF NOT EXISTS milestone_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    milestone_id INT NOT NULL,
    plan_type ENUM('month', 'week') DEFAULT 'month',
    plan_number INT NOT NULL,
    work_description TEXT NOT NULL,
    expected_completion_date DATE,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_milestone_id (milestone_id),
    INDEX idx_plan_type (plan_type),
    FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE CASCADE
);

-- Milestone Updates Table (Site Engineer Work Updates)
CREATE TABLE IF NOT EXISTS milestone_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    milestone_id INT NOT NULL,
    updated_by INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    remarks TEXT,
    completion_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_milestone_id (milestone_id),
    INDEX idx_updated_by (updated_by),
    FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Project Payments Table
CREATE TABLE IF NOT EXISTS project_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    milestone_id INT,
    stage_name VARCHAR(255) NOT NULL,
    payment_type ENUM('advance', 'mid_work', 'completion') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_date DATE,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_project_id (project_id),
    INDEX idx_status (status),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE SET NULL
);
";

if ($conn->multi_query($sql)) {
    echo "Tables created successfully!";
} else {
    echo "Error: " . $conn->error;
}