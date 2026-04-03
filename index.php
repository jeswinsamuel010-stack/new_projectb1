<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$page_title = 'Dashboard';
$role = $_SESSION['role'];

// Get statistics based on role
$stats = [];

// ============ ROLE-BASED STATS ============

// Sales & Estimator & Admin - Enquiries
if (in_array($role, ['admin', 'sales', 'estimator'])) {
    $stats['new_enquiries'] = fetchOne("SELECT COUNT(*) as c FROM enquiries WHERE status = 'new'")['c'] ?? 0;
    $stats['total_enquiries'] = fetchOne("SELECT COUNT(*) as c FROM enquiries")['c'] ?? 0;
    $stats['this_month_enquiries'] = fetchOne("SELECT COUNT(*) as c FROM enquiries WHERE MONTH(created_at) = MONTH(NOW())")['c'] ?? 0;
}

// Estimator & Admin - Quotations
if (in_array($role, ['admin', 'estimator'])) {
    $stats['draft_quotations'] = fetchOne("SELECT COUNT(*) as c FROM project_quotations WHERE status = 'Draft'")['c'] ?? 0;
    $stats['pending_quotations'] = fetchOne("SELECT COUNT(*) as c FROM project_quotations WHERE status = 'Sent'")['c'] ?? 0;
    $stats['approved_quotations'] = fetchOne("SELECT COUNT(*) as c FROM project_quotations WHERE status = 'Approved'")['c'] ?? 0;
    $stats['total_quotation_value'] = fetchOne("SELECT COALESCE(SUM(estimated_amount), 0) as t FROM project_quotations WHERE status IN ('Approved', 'Converted')")['t'] ?? 0;
}

// Admin & Project Manager - Projects
if (in_array($role, ['admin', 'project_manager'])) {
    $stats['active_projects'] = fetchOne("SELECT COUNT(*) as c FROM projects WHERE status = 'active'")['c'] ?? 0;
    $stats['completed_projects'] = fetchOne("SELECT COUNT(*) as c FROM projects WHERE status = 'completed'")['c'] ?? 0;
    $stats['total_project_value'] = fetchOne("SELECT COALESCE(SUM(project_value), 0) as t FROM projects WHERE status = 'active'")['t'] ?? 0;
}

// Admin - Approvals (using centralized approvals table)
if ($role == 'admin') {
    $stats['pending_approvals'] = fetchOne("SELECT COUNT(*) as c FROM approvals WHERE status = 'pending'")['c'] ?? 0;
}

// Admin & Project Manager - Material Requests
if (in_array($role, ['admin', 'project_manager'])) {
    $stats['pending_requests'] = fetchOne("SELECT COUNT(*) as c FROM material_requests WHERE status = 'pending'")['c'] ?? 0;
    $stats['approved_requests'] = fetchOne("SELECT COUNT(*) as c FROM material_requests WHERE status = 'approved'")['c'] ?? 0;
}

// Admin & Accounts - Billing
if (in_array($role, ['admin', 'accounts'])) {
    $stats['pending_bills'] = fetchOne("SELECT COUNT(*) as c FROM bills WHERE status IN ('draft', 'sent')")['c'] ?? 0;
    $stats['paid_bills'] = fetchOne("SELECT COUNT(*) as c FROM bills WHERE status = 'paid'")['c'] ?? 0;
    $stats['total_billed'] = fetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM bills")['t'] ?? 0;
    $stats['total_received'] = fetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM bills WHERE status = 'paid'")['t'] ?? 0;
}

// Admin - Inventory
if ($role == 'admin') {
    $stats['inventory_items'] = fetchOne("SELECT COUNT(*) as c FROM inventory WHERE current_stock > 0")['c'] ?? 0;
    $stats['low_stock'] = fetchOne("SELECT COUNT(*) as c FROM inventory WHERE current_stock <= min_stock_level AND min_stock_level > 0")['c'] ?? 0;
}

// Admin - Users
if ($role == 'admin') {
    $stats['total_users'] = fetchOne("SELECT COUNT(*) as c FROM users WHERE status = 'active'")['c'] ?? 0;
}

// ============ RECENT ACTIVITY LISTS ============

$recent_enquiries = fetchAll("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 5");
$recent_quotations = fetchAll("SELECT * FROM project_quotations ORDER BY created_at DESC LIMIT 5");
$recent_projects = fetchAll("SELECT * FROM projects ORDER BY created_at DESC LIMIT 5");
$recent_bills = fetchAll("SELECT * FROM bills ORDER BY created_at DESC LIMIT 5");
$pending_approvals = fetchAll("SELECT a.*, u.full_name as approver_name FROM approvals a LEFT JOIN users u ON a.approved_by = u.id WHERE a.status = 'pending' ORDER BY a.created_at DESC LIMIT 5");

require_once 'includes/header.php';
?>

<div class="content">
    <div class="stats-grid">
        <?php if (in_array($role, ['admin', 'site_engineer', 'project_manager'])): ?>
        <div class="stat-card border-blue">
            <div class="stat-icon blue">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-info">
                <h4>Active Projects</h4>
                <div class="value"><?php echo $stats['active_projects'] ?? 0; ?></div>
            </div>
        </div>

        <div class="stat-card border-orange">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h4>Pending Requests</h4>
                <div class="value"><?php echo $stats['pending_requests'] ?? 0; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'project_manager'])): ?>
        <div class="stat-card border-green">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h4>Approved Requests</h4>
                <div class="value"><?php echo $stats['approved_requests'] ?? 0; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'purchase_team'])): ?>
        <div class="stat-card border-purple">
            <div class="stat-icon purple">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h4>Ordered Items</h4>
                <div class="value"><?php echo $stats['ordered_requests'] ?? 0; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'store_keeper'])): ?>
        <!-- <div class="stat-card border-teal">
            <div class="stat-icon teal">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="stat-info">
                <h4>Inventory Items</h4>
                <div class="value"><?php echo $stats['inventory_items'] ?? 0; ?></div>
            </div>
        </div> -->

        <div class="stat-card border-red">
            <div class="stat-icon red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h4>Low Stock Items</h4>
                <div class="value"><?php echo $stats['low_stock'] ?? 0; ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'accounts'])): ?>
        <div class="stat-card border-blue">
            <div class="stat-icon blue">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-info">
                <h4>Pending Bills</h4>
                <div class="value"><?php echo $stats['pending_bills'] ?? 0; ?></div>
            </div>
        </div>

        <div class="stat-card border-green">
            <div class="stat-icon green">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-info">
                <h4>Total Billed</h4>
                <div class="value"><?php echo number_format($stats['total_billed'] ?? 0, 0, '.', ','); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role == 'admin'): ?>
        <!-- <div class="stat-card border-blue">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h4>Active Users</h4>
                <div class="value"><?php echo $stats['users'] ?? 0; ?></div>
            </div>
        </div> -->

        <div class="stat-card border-green">
            <div class="stat-icon green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h4>Total Project Value</h4>
                <div class="value"><?php echo number_format(($stats['total_value'] ?? 0) / 100000, 1, '.', ','); ?>L</div>
            </div>
        </div>

        <!-- <div class="stat-card border-teal">
            <div class="stat-icon teal">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-info">
                <h4>Project Progress</h4>
                <div class="value"><?php echo $stats['completion_percentage'] ?? 0; ?>%</div>
                <small class="text-muted"><?php echo $stats['completed_milestones'] ?? 0; ?>/<?php echo $stats['total_milestones'] ?? 0; ?> milestones</small>
            </div>
        </div> -->

        <!-- <div class="stat-card border-purple">
            <div class="stat-icon purple">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-info">
                <h4>Stage Payments</h4>
                <div class="value"><?php echo number_format(($stats['total_payments'] ?? 0) / 100000, 1, '.', ','); ?>L</div>
            </div>
        </div> -->
        <?php endif; ?>
    </div>

    <!-- Recent Projects -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Projects</h3>
            <?php if (in_array($role, ['admin'])): ?>
            <a href="projects/list.php" class="btn btn-primary btn-sm">
                <i class="fas fa-list"></i> View All
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Client</th>
                            <th>Value</th>
                            <th>Start Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_projects = fetchAll("SELECT * FROM projects ORDER BY created_at DESC LIMIT 5");
                        if (empty($recent_projects)):
                        ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No projects found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recent_projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                <td><?php echo htmlspecialchars($project['client_name']); ?></td>
                                <td><?php echo number_format($project['project_value'], 2); ?></td>
                                <td><?php echo date('d M Y', strtotime($project['start_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $project['status']; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Material Requests -->
    <?php if (in_array($role, ['admin', 'site_engineer', 'project_manager'])): ?>
    <div class="card">
        <div class="card-header">
            <h3>Recent Material Requests</h3>
            <a href="requests/list.php" class="btn btn-primary btn-sm">
                <i class="fas fa-list"></i> View All
            </a>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Project</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_requests = fetchAll("
                            SELECT mr.*, p.project_name
                            FROM material_requests mr
                            LEFT JOIN projects p ON mr.project_id = p.id
                            ORDER BY mr.created_at DESC
                            LIMIT 5
                        ");
                        if (empty($recent_requests)):
                        ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No requests found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($req['project_name'] ?? '-'); ?></td>
                                <td><?php echo $req['quantity'] . ' ' . $req['unit']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $req['status']; ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($req['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Role-Specific Dashboard Sections -->
<div class="row g-3 mt-3">

    <?php if (in_array($role, ['admin', 'sales'])): ?>
    <!-- SALES DASHBOARD: Recent Enquiries -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-black">
                <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Recent Enquiries</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_enquiries)): ?>
                    <p class="text-muted">No enquiries yet</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_enquiries as $eq): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo htmlspecialchars($eq['client_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($eq['project_type']); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $eq['status'] == 'new' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($eq['status']); ?>
                                </span>
                                <br><small class="text-muted"><?php echo date('d M', strtotime($eq['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="enquiry/list.php" class="btn btn-sm btn-outline-primary mt-2">View All Enquiries</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'estimator'])): ?>
    <!-- ESTIMATOR DASHBOARD: Recent Quotations -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Recent Quotations</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_quotations)): ?>
                    <p class="text-muted">No quotations yet</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_quotations as $qt): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo htmlspecialchars($qt['client_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($qt['project_title']); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $qt['status'] == 'Approved' ? 'success' : ($qt['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo $qt['status']; ?>
                                </span>
                                <br><strong class="text-success">₹<?php echo number_format($qt['estimated_amount'], 0); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="quotations/list.php" class="btn btn-sm btn-outline-warning mt-2">View All Quotations</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($role == 'admin'): ?>
    <!-- ADMIN DASHBOARD: Pending Approvals -->
    <div class="col-md-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-black">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Approvals</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pending_approvals)): ?>
                    <p class="text-muted">No pending approvals</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pending_approvals as $appr): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo strtoupper($appr['module_type']); ?></strong>
                                <br><small class="text-muted">ID: #<?php echo $appr['module_id']; ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning">Pending</span>
                                <br><small class="text-muted"><?php echo date('d M', strtotime($appr['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="approval/list.php" class="btn btn-sm btn-outline-danger mt-2">View All Approvals</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'project_manager'])): ?>
    <!-- PROJECT MANAGER DASHBOARD: Active Projects -->
    <div class="col-md-6">
        <div class="card border-success">
            <div class="card-header bg-success text-black">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Active Projects</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_projects)): ?>
                    <p class="text-muted">No projects yet</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_projects as $proj): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo htmlspecialchars($proj['project_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($proj['client_name']); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $proj['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($proj['status']); ?>
                                </span>
                                <br><strong class="text-success">₹<?php echo number_format($proj['project_value'], 0); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="projects/list.php" class="btn btn-sm btn-outline-success mt-2">View All Projects</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'accounts'])): ?>
    <!-- ACCOUNTS DASHBOARD: Bills & Payments -->
    <div class="col-md-6">
        <div class="card border-info">
            <div class="card-header bg-info text-dark">
                <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Recent Bills</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_bills)): ?>
                    <p class="text-muted">No bills yet</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_bills as $bill): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo htmlspecialchars($bill['bill_number'] ?? '#' . $bill['id']); ?></strong>
                                <br><small class="text-muted"><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $bill['status'] == 'paid' ? 'success' : ($bill['status'] == 'overdue' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($bill['status']); ?>
                                </span>
                                <br><strong class="text-success">₹<?php echo number_format($bill['amount'], 0); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="bills/list.php" class="btn btn-sm btn-outline-info mt-2">View All Bills</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>