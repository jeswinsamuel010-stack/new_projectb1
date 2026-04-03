<nav class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-hard-hat"></i> Construction ERP</h2>
    </div>
    <ul class="nav-menu">
        <?php
$current_page = $_SERVER['REQUEST_URI'];
$base_path = '/new_projectb/';

function isActive($path) {
    $current = $_SERVER['REQUEST_URI'];
    return (strpos($current, $path) !== false) ? 'active' : '';
}

// Dashboard - accessible to all
echo '<li><a href="' . $base_path . 'index.php" class="' . ($_SERVER['REQUEST_URI'] == '/new_projectb/index.php' ? 'active' : '') . '">
    <i class="fas fa-tachometer-alt"></i> Dashboard</a></li>';

// Users - RBAC
if (canAccess('users', 'list')) {
    echo '<li><a href="' . $base_path . 'users/list.php" class="' . isActive('users/list.php') . '">
        <i class="fas fa-users"></i> Users</a></li>';
}

// Enquiries - RBAC
if (canAccess('enquiry', 'list')) {
    echo '<li><a href="' . $base_path . 'enquiry/list.php" class="' . isActive('enquiry/list.php') . '">
        <i class="fas fa-clipboard"></i> Enquiries</a></li>';
}

// Quotations - RBAC
if (canAccess('quotation', 'list')) {
    echo '<li><a href="' . $base_path . 'quotations/list.php" class="' . isActive('quotations/list.php') . '">
        <i class="fas fa-file-invoice"></i> Quotations</a></li>';
}

// Projects - RBAC
if (canAccess('projects', 'list')) {
    echo '<li><a href="' . $base_path . 'projects/list.php" class="' . isActive('projects/list.php') . '">
        <i class="fas fa-building"></i> Projects</a></li>';
}

// Project Management - RBAC
if (canAccess('projects', 'manage')) {
    echo '<li><a href="' . $base_path . 'projects/manage.php" class="' .isActive('projects/manage.php') . '">
        <i class="fas fa-tasks"></i> Project Management</a></li>';
}

// Milestones - RBAC
if (canAccess('milestones', 'list')) {
    echo '<li><a href="' . $base_path . 'milestones/list.php" class="' . isActive('milestones/list.php') . '">
        <i class="fas fa-flag-checkered"></i> Milestones</a></li>';
}

// Payments - RBAC
if (canAccess('payments', 'list')) {
    echo '<li><a href="' . $base_path . 'payments/list.php" class="' . isActive('payments/list.php') . '">
        <i class="fas fa-rupee-sign"></i> Stage Payments</a></li>';
}

// Reports - RBAC
if (canAccess('reports', 'list')) {
    echo '<li><a href="' . $base_path . 'reports/macro_report.php" class="' . isActive('reports/macro_report.php') . '">
        <i class="fas fa-chart-bar"></i> Reports</a></li>';
}

// Material Requests (Original) - RBAC
if (canAccess('requests', 'list')) {
    echo '<li><a href="' . $base_path . 'requests/list.php" class="' . isActive('requests/list.php'). '">
        <i class="fas fa-clipboard-list"></i> Material Requests</a></li>';
}

// Approvals (Original) - RBAC
if (canAccess('approval', 'list')) {
    echo '<li><a href="' . $base_path . 'approval/index.php" class="' . isActive('approval/index.php') . '">
        <i class="fas fa-check-circle"></i> Approvals</a></li>';
}

// Purchase Orders (Original) - RBAC
if (canAccess('purchase', 'list')) {
    echo '<li><a href="' . $base_path . 'purchase/list.php" class="' . isActive('purchase/list.php') . '">
        <i class="fas fa-shopping-cart"></i> Purchase Orders</a></li>';
}

// Inventory (Original) - RBAC
if (canAccess('inventory', 'list')) {
    echo '<li><a href="' . $base_path . 'inventory/list.php" class="' . isActive('inventory/list.php'). '">
        <i class="fas fa-warehouse"></i> Inventory</a></li>';
}

// Material Issues (Original) - RBAC
if (canAccess('issues', 'list')) {
    echo '<li><a href="' . $base_path . 'issues/index.php" class="' . isActive('issues/index.php'). '">
        <i class="fas fa-truck-loading"></i> Material Issues</a></li>';
}

// PDF Import for GRN (Original)
if (canAccess('issues', 'list')) {
    echo '<li><a href="' . $base_path . 'grn/import_pdf.php" class="' . isActive('grn/import_pdf.php'). '">
        <i class="fas fa-file-pdf"></i> PDF Import</a></li>';
}

// ===== BUY SIDE MODULE =====
echo '<li class="nav-section"><i class="fas fa-shopping-cart"></i> BUY SIDE</li>';

// Purchase Orders
if (canAccess('purchase', 'list')) {
    echo '<li><a href="' . $base_path . 'buy_side/purchase_orders.php" class="' . isActive('buy_side/purchase_orders.php') . '">
        <i class="fas fa-file-signature"></i> Purchase Orders</a></li>';
}

// GRN
if (canAccess('issues', 'list')) {
    echo '<li><a href="' . $base_path . 'buy_side/grn.php" class="' . isActive('buy_side/grn.php') . '">
        <i class="fas fa-truck-loading"></i> GRN (Goods Receipt)</a></li>';
}

// Inventory (Buy Side)
if (canAccess('inventory', 'list')) {
    echo '<li><a href="' . $base_path . 'buy_side/inventory.php" class="' . isActive('buy_side/inventory.php') . '">
        <i class="fas fa-warehouse"></i> Inventory</a></li>';
}

// Bills
if (canAccess('bills', 'list')) {
    echo '<li><a href="' . $base_path . 'buy_side/bills.php" class="' . isActive('buy_side/bills.php') . '">
        <i class="fas fa-file-invoice-dollar"></i> Bills</a></li>';
}

// Payments
if (canAccess('payments', 'list')) {
    echo '<li><a href="' . $base_path . 'buy_side/payments.php" class="' . isActive('buy_side/payments.php') . '">
        <i class="fas fa-rupee-sign"></i> Payments</a></li>';
}

// ===== USER SIDE MODULE =====
echo '<li class="nav-section"><i class="fas fa-truck"></i> USER SIDE</li>';

// Material Requests
if (canAccess('requests', 'list')) {
    echo '<li><a href="' . $base_path . 'user_side/requests.php" class="' . isActive('user_side/requests.php') . '">
        <i class="fas fa-clipboard-list"></i> Material Requests</a></li>';
}

// Approvals
if (canAccess('approval', 'list')) {
    echo '<li><a href="' . $base_path . 'user_side/approvals.php" class="' . isActive('user_side/approvals.php') . '">
        <i class="fas fa-check-circle"></i> Approvals</a></li>';
}

// Issue Material
if (canAccess('issues', 'list')) {
    echo '<li><a href="' . $base_path . 'user_side/issues.php" class="' . isActive('user_side/issues.php') . '">
        <i class="fas fa-paper-plane"></i> Issue Material</a></li>';
}

// Inventory (User Side)
if (canAccess('inventory', 'list')) {
    echo '<li><a href="' . $base_path . 'user_side/inventory.php" class="' . isActive('user_side/inventory.php') . '">
        <i class="fas fa-warehouse"></i> Inventory</a></li>';
}

// Bills (Original) - RBAC
if (canAccess('bills', 'list')) {
    echo '<li><a href="' . $base_path . 'bills/list.php" class="' . isActive('bills/list.php'). '">
        <i class="fas fa-file-invoice-dollar"></i> Bills</a></li>';
}
?>
    </ul>
</nav>