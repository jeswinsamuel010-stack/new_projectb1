<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'reports', 'view');

$page_title = 'Micro Report - Project Details';

// Get all active projects
$projects = fetchAll("SELECT id, project_name FROM projects WHERE status = 'active' ORDER BY project_name");

$selected_project = intval($_GET['project_id'] ?? 0);
$project_data = null;
$milestones = [];
$payments = [];

if ($selected_project) {
    $project_data = fetchOne("SELECT * FROM projects WHERE id = ?", "i", [$selected_project]);
    $milestones = fetchAll("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY start_date", "i", [$selected_project]);
    $payments = fetchAll("SELECT * FROM project_payments WHERE project_id = ? ORDER BY payment_date", "i", [$selected_project]);
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Micro Report - Project Details</h2>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="form form-inline">
                <div class="form-group" style="flex: 1;">
                    <label for="project_id">Select Project: </label>
                    <select name="project_id" id="project_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $selected_project == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($project_data): ?>
    <!-- Project Info -->
    <div class="card">
        <div class="card-header">
            <h3>Project Information</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Project Name:</label>
                    <span><?php echo htmlspecialchars($project_data['project_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Client:</label>
                    <span><?php echo htmlspecialchars($project_data['client_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Project Value:</label>
                    <span>Rs. <?php echo number_format($project_data['project_value'], 2); ?></span>
                </div>
                <div class="info-item">
                    <label>Start Date:</label>
                    <span><?php echo date('d M Y', strtotime($project_data['start_date'])); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Milestones -->
    <div class="card">
        <div class="card-header">
            <h3>Milestones List</h3>
        </div>
        <div class="card-body">
            <?php if (empty($milestones)): ?>
                <p class="text-muted">No milestones defined for this project.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Phase Name</th>
                                <th>Description</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($milestones as $m): ?>
                                <?php
                                $statusClass = '';
                                switch ($m['status']) {
                                    case 'pending': $statusClass = 'warning'; break;
                                    case 'in_progress': $statusClass = 'info'; break;
                                    case 'completed': $statusClass = 'success'; break;
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['phase_name']); ?></td>
                                    <td><?php echo htmlspecialchars($m['description'] ?? '-'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($m['start_date'])); ?></td>
                                    <td><?php echo date('d M Y', strtotime($m['end_date'])); ?></td>
                                    <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $m['status'])); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payments -->
    <div class="card">
        <div class="card-header">
            <h3>Payments Made</h3>
        </div>
        <div class="card-body">
            <?php if (empty($payments)): ?>
                <p class="text-muted">No payments recorded for this project.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Stage Name</th>
                                <th>Payment Type</th>
                                <th>Amount (INR)</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $typeLabels = ['advance' => 'Advance', 'mid_work' => 'Mid-Work', 'completion' => 'Completion'];
                            foreach ($payments as $p):
                                $statusClass = $p['status'] == 'paid' ? 'success' : 'warning';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['stage_name']); ?></td>
                                    <td><?php echo $typeLabels[$p['payment_type']] ?? $p['payment_type']; ?></td>
                                    <td><?php echo number_format($p['amount'], 2); ?></td>
                                    <td><?php echo $p['payment_date'] ? date('d M Y', strtotime($p['payment_date'])) : '-'; ?></td>
                                    <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total Paid</th>
                                <th colspan="3">
                                    Rs. <?php echo number_format(array_sum(array_column(array_filter($payments, function($p) { return $p['status'] == 'paid'; }), 'amount')), 2); ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}
.info-item {
    display: flex;
    flex-direction: column;
}
.info-item label {
    font-weight: 600;
    color: #666;
    margin-bottom: 5px;
}
.info-item span {
    font-size: 1.1em;
}
</style>

<?php require_once '../includes/footer.php'; ?>