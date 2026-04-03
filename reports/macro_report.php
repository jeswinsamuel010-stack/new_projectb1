<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'reports', 'view');

$page_title = 'Macro Report - Summary';

// Get project statistics
$total_projects = fetchOne("SELECT COUNT(*) as total FROM projects WHERE status = 'active'");
$projects = fetchAll("SELECT id, project_name, project_value FROM projects WHERE status = 'active'");

$project_summary = [];

foreach ($projects as $project) {
    // Calculate progress
    $total_milestones = fetchOne("SELECT COUNT(*) as total FROM project_milestones WHERE project_id = ?", "i", [$project['id']]);
    $completed_milestones = fetchOne("SELECT COUNT(*) as total FROM project_milestones WHERE project_id = ? AND status = 'completed'", "i", [$project['id']]);

    $progress = 0;
    if ($total_milestones['total'] > 0) {
        $progress = round(($completed_milestones['total'] / $total_milestones['total']) * 100);
    }

    // Calculate payments
    $total_paid = fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM project_payments WHERE project_id = ? AND status = 'paid'", "i", [$project['id']]);
    $pending_payments = fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM project_payments WHERE project_id = ? AND status = 'pending'", "i", [$project['id']]);

    $remaining = $project['project_value'] - $total_paid['total'];

    $project_summary[] = [
        'id' => $project['id'],
        'name' => $project['project_name'],
        'value' => $project['project_value'],
        'progress' => $progress,
        'total_milestones' => $total_milestones['total'],
        'completed_milestones' => $completed_milestones['total'],
        'total_paid' => $total_paid['total'],
        'pending_payments' => $pending_payments['total'],
        'remaining' => $remaining
    ];
}

// Calculate totals
$total_project_value = array_sum(array_column($project_summary, 'value'));
$total_paid = array_sum(array_column($project_summary, 'total_paid'));
$total_pending = array_sum(array_column($project_summary, 'pending_payments'));
$total_remaining = array_sum(array_column($project_summary, 'remaining'));
$avg_progress = count($project_summary) > 0 ? round(array_sum(array_column($project_summary, 'progress')) / count($project_summary)) : 0;

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Macro Report - Project Summary</h2>
        <a href="micro_report.php" class="btn btn-secondary">
            <i class="fas fa-file-alt"></i> Detailed Report
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-info">
                <h4>Total Projects</h4>
                <div class="value"><?php echo count($project_summary); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-info">
                <h4>Average Completion</h4>
                <div class="value"><?php echo $avg_progress; ?>%</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h4>Total Project Value</h4>
                <div class="value">Rs. <?php echo number_format($total_project_value / 100000, 1, '.', ','); ?>L</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h4>Total Payments Made</h4>
                <div class="value">Rs. <?php echo number_format($total_paid / 100000, 1, '.', ','); ?>L</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h4>Pending Payments</h4>
                <div class="value">Rs. <?php echo number_format($total_pending / 100000, 1, '.', ','); ?>L</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-info">
                <h4>Remaining Balance</h4>
                <div class="value">Rs. <?php echo number_format($total_remaining / 100000, 1, '.', ','); ?>L</div>
            </div>
        </div>
    </div>

    <!-- Project Summary Table -->
    <div class="card">
        <div class="card-header">
            <h3>Project Summary</h3>
        </div>
        <div class="card-body">
            <?php if (empty($project_summary)): ?>
                <p class="text-muted text-center">No active projects found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Value (INR)</th>
                                <th>Progress</th>
                                <th>Milestones</th>
                                <th>Paid (INR)</th>
                                <th>Pending (INR)</th>
                                <th>Remaining (INR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project_summary as $ps): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ps['name']); ?></td>
                                    <td><?php echo number_format($ps['value'], 0, '.', ','); ?></td>
                                    <td>
                                        <div class="progress-cell">
                                            <div class="progress-bar-container">
                                                <div class="progress-bar" style="width: <?php echo $ps['progress']; ?>%;"></div>
                                            </div>
                                            <span><?php echo $ps['progress']; ?>%</span>
                                        </div>
                                    </td>
                                    <td><?php echo $ps['completed_milestones']; ?>/<?php echo $ps['total_milestones']; ?></td>
                                    <td class="text-success"><?php echo number_format($ps['total_paid'], 0, '.', ','); ?></td>
                                    <td class="text-warning"><?php echo number_format($ps['pending_payments'], 0, '.', ','); ?></td>
                                    <td class="text-danger"><?php echo number_format($ps['remaining'], 0, '.', ','); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>TOTAL</th>
                                <th>Rs. <?php echo number_format($total_project_value, 0, '.', ','); ?></th>
                                <th><?php echo $avg_progress; ?>%</th>
                                <th>-</th>
                                <th class="text-success">Rs. <?php echo number_format($total_paid, 0, '.', ','); ?></th>
                                <th class="text-warning">Rs. <?php echo number_format($total_pending, 0, '.', ','); ?></th>
                                <th class="text-danger">Rs. <?php echo number_format($total_remaining, 0, '.', ','); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.progress-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.progress-bar-container {
    width: 100px;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}
.progress-bar {
    height: 100%;
    background: #28a745;
    border-radius: 4px;
}
</style>

<?php require_once '../includes/footer.php'; ?>