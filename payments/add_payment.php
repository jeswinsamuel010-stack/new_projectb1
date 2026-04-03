<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'payments', 'create');

$page_title = 'Add Payment';

// Get all active projects
$projects = fetchAll("SELECT id, project_name, project_value FROM projects WHERE status = 'active' ORDER BY project_name");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $project_id = intval($_POST['project_id']);
        $milestone_id = !empty($_POST['milestone_id']) ? intval($_POST['milestone_id']) : null;
        $stage_name = sanitize($_POST['stage_name']);
        $payment_type = $_POST['payment_type'];
        $amount = floatval($_POST['amount']);
        $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
        $status = $_POST['status'];
        $notes = sanitize($_POST['notes']);

        if (empty($project_id) || empty($stage_name) || empty($payment_type) || empty($amount)) {
            $error = 'Please fill all required fields';
        } else {
            $result = insertAndGetId(
                "INSERT INTO project_payments (project_id, milestone_id, stage_name, payment_type, amount, payment_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                "iissdsss",
                [$project_id, $milestone_id, $stage_name, $payment_type, $amount, $payment_date, $status, $notes]
            );

            if ($result['success']) {
                $success = 'Payment recorded successfully';
                header("Location: list.php?success=" . urlencode($success));
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Add Payment</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label for="project_id">Project <span class="text-danger">*</span></label>
                    <select name="project_id" id="project_id" class="form-control" required onchange="loadMilestones(this.value)">
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['project_name']); ?> (<?php echo number_format($project['project_value']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="milestone_id">Related Milestone (Optional)</label>
                    <select name="milestone_id" id="milestone_id" class="form-control">
                        <option value="">Select Milestone</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="stage_name">Stage Name <span class="text-danger">*</span></label>
                    <input type="text" name="stage_name" id="stage_name" class="form-control"
                           placeholder="e.g., Cupboard Work - Phase 1" required>
                </div>

                <div class="form-group">
                    <label for="payment_type">Payment Type <span class="text-danger">*</span></label>
                    <select name="payment_type" id="payment_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="advance">Advance Payment</option>
                        <option value="mid_work">Mid-Work Payment</option>
                        <option value="completion">Completion Payment</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount">Amount (INR) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="amount" class="form-control"
                           step="0.01" min="0" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="payment_date">Payment Date</label>
                    <input type="date" name="payment_date" id="payment_date" class="form-control">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2"
                              placeholder="Any additional notes"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Record Payment
                    </button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadMilestones(projectId) {
    const milestoneSelect = document.getElementById('milestone_id');
    milestoneSelect.innerHTML = '<option value="">Select Milestone</option>';

    if (!projectId) return;

    fetch('../api/milestones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_by_project&project_id=' + projectId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data) {
            data.data.forEach(m => {
                const option = document.createElement('option');
                option.value = m.id;
                option.textContent = m.phase_name;
                milestoneSelect.appendChild(option);
            });
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>