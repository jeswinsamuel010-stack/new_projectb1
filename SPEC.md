# Construction ERP Web Application Specification

## 1. Project Overview

**Project Name:** Mini Construction ERP
**Type:** Web Application
**Core Functionality:** A role-based construction management system handling material requests, purchase orders, inventory, and billing
**Target Users:** Construction company staff (Admin, Site Engineer, Project Manager, Purchase Team, Store Keeper, Accounts)

## 2. Technology Stack

- **Backend:** Core PHP (no frameworks)
- **Database:** MySQL (XAMPP)
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** XAMPP (Apache + MySQL)
- **Authentication:** PHP Sessions with secure password hashing

## 3. Database Schema

### 3.1 Users Table
```sql
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
```

### 3.2 Projects Table
```sql
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(200) NOT NULL,
    client_name VARCHAR(200) NOT NULL,
    project_value DECIMAL(15,2) NOT NULL,
    start_date DATE NOT NULL,
    description TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### 3.3 Material Requests Table
```sql
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);
```

### 3.4 Purchase Orders Table
```sql
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
```

### 3.5 Goods Receipts Table
```sql
CREATE TABLE goods_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    received_date DATE NOT NULL,
    received_quantity DECIMAL(10,2) NOT NULL,
    accepted_quantity DECIMAL(10,2) NOT NULL,
    rejected_quantity DECIMAL(10,2) DEFAULT 0,
    remarks TEXT,
    received_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);
```

### 3.6 Inventory Table
```sql
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
```

### 3.7 Material Issues Table
```sql
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
```

### 3.8 Bills Table
```sql
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
```

## 4. UI/UX Specification

### 4.1 Layout Structure

**Main Layout:**
- Fixed sidebar (250px width) on left
- Main content area with header and content
- Responsive design for different screen sizes

**Sidebar:**
- Logo/Brand area at top
- Navigation menu by role
- User profile section at bottom
- Logout button

**Content Area:**
- Page header with title
- Breadcrumb navigation
- Main content container
- Action buttons where applicable

### 4.2 Color Palette
- Primary: #2c3e50 (Dark blue-gray)
- Secondary: #3498db (Blue)
- Success: #27ae60 (Green)
- Warning: #f39c12 (Orange)
- Danger: #e74c3c (Red)
- Background: #ecf0f1 (Light gray)
- White: #ffffff
- Text: #2c3e50 (Dark)
- Text Muted: #7f8c8d (Gray)

### 4.3 Typography
- Font Family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif
- Headings: 24px (h1), 20px (h2), 18px (h3)
- Body: 14px
- Small: 12px

### 4.4 Components
- Cards with white background and subtle shadow
- Tables with striped rows
- Forms with floating labels
- Buttons: Primary (blue), Success (green), Danger (red), Warning (orange)
- Status badges with appropriate colors
- Modal dialogs for confirmations
- Alert messages for feedback

### 4.5 Status Badge Colors
- Pending: #f39c12 (Orange)
- Approved: #27ae60 (Green)
- Rejected: #e74c3c (Red)
- Ordered: #3498db (Blue)
- Received: #9b59b6 (Purple)
- Issued: #1abc9c (Teal)
- Paid: #27ae60 (Green)
- Completed: #27ae60 (Green)

## 5. Functionality Specification

### 5.1 Authentication System

**Login:**
- Username/email (case-insensitive)
- Password validation
- Session creation with user ID, username, role
- Redirect to role-based dashboard

**Logout:**
- Destroy all session variables
- Redirect to login page

**Security:**
- Password hashing using password_hash() with PASSWORD_DEFAULT
- Session regeneration on login
- Prepared statements for all queries
- Input sanitization

### 5.2 Role-Based Access Control

**Admin:**
- All modules access
- User management (CRUD)
- View all data
- Dashboard with full summary

**Site Engineer:**
- Create material requests
- View own requests
- Dashboard

**Project Manager:**
- View pending requests
- Approve/Reject requests
- Dashboard

**Purchase Team:**
- View approved requests
- Create purchase orders
- Dashboard

**Store Keeper:**
- View purchase orders
- Record goods receipts
- Manage inventory
- Issue materials
- Dashboard

**Accounts:**
- View completed work
- Create bills
- Dashboard

### 5.3 Workflow Modules

**Step 1: Project Creation (Admin)**
- Create new project with all fields
- Edit existing projects
- View all projects
- Delete projects (soft delete)

**Step 2: Material Request (Site Engineer)**
- Select project from dropdown
- Enter item name, quantity, unit, reason
- Status automatically set to PENDING

**Step 3: Approval (Project Manager)**
- View all PENDING requests
- Approve or Reject with remarks
- Status updated accordingly

**Step 4: Purchase Order (Purchase Team)**
- View APPROVED requests
- Create PO with supplier details
- Generate PO number automatically

**Step 5: Goods Receipt (Store Keeper)**
- View ORDERED items
- Record received quantity
- Update inventory stock

**Step 6: Material Issue (Store Keeper)**
- View RECEIVED items
- Issue materials to site
- Reduce stock accordingly

**Step 7: Billing (Accounts)**
- View completed projects
- Create bills with amount
- Mark as PAID when received

## 6. Sample Data

### 6.1 Users
| Username | Email | Password | Full Name | Role |
|----------|-------|----------|-----------|------|
| admin | admin@erp.com | password123 | System Admin | admin |
| site_eng | engineer@erp.com | password123 | John Smith | site_engineer |
| proj_mgr | manager@erp.com | password123 | Sarah Johnson | project_manager |
| purchase | purchase@erp.com | password123 | Mike Wilson | purchase_team |
| store_kpr | store@erp.com | password123 | David Brown | store_keeper |
| accounts | accounts@erp.com | password123 | Lisa Davis | accounts |

### 6.2 Projects
- Project 1: "Skyline Tower" - ABC Corp - $5,000,000
- Project 2: "Riverside Mall" - XYZ Ltd - $3,500,000
- Project 3: "Highway Bridge" - Govt Department - $8,000,000

## 7. File Structure

```
/new_projectb/
├── config/
│   └── db.php
├── includes/
│   ├── header.php
│   ├── sidebar.php
│   ├── footer.php
│   └── auth.php
├── css/
│   └── style.css
├── js/
│   └── main.js
├── login.php
├── logout.php
├── index.php (Dashboard)
├── projects/
│   ├── list.php
│   ├── create.php
│   └── edit.php
├── requests/
│   ├── list.php
│   └── create.php
├── approval/
│   └── index.php
├── purchase/
│   ├── list.php
│   └── create.php
├── inventory/
│   ├── list.php
│   └── update.php
├── issues/
│   └── index.php
├── bills/
│   ├── list.php
│   └── create.php
├── users/
│   ├── list.php
│   ├── create.php
│   └── edit.php
└── database/
    └── schema.sql
```

## 8. Acceptance Criteria

### 8.1 Authentication
- [ ] All 6 users can login successfully with correct credentials
- [ ] Invalid credentials show appropriate error message
- [ ] Sessions are properly maintained
- [ ] Logout destroys session completely
- [ ] Users are redirected to correct dashboard by role

### 8.2 Workflow
- [ ] Admin can create projects
- [ ] Site Engineer can create material requests
- [ ] Project Manager can approve/reject requests
- [ ] Purchase Team can create POs
- [ ] Store Keeper can record receipts
- [ ] Store Keeper can issue materials
- [ ] Accounts can create bills

### 8.3 UI/UX
- [ ] Clean sidebar navigation
- [ ] Role-based menu items
- [ ] Status badges with correct colors
- [ ] Responsive layout
- [ ] Form validation

### 8.4 Security
- [ ] Passwords stored as hashes
- [ ] Prepared statements used everywhere
- [ ] Session protection
- [ ] Role-based access restrictions