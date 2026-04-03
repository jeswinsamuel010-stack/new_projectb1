<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'milestones', 'edit');

$page_title = 'Edit Milestone';

$milestone_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Get milestone data
$milestone = fetchOne("SELECT * FROM project_milestones WHERE id = ?", "i", [$milestone_id]);

if (!$milestone) {
    header("Location: list.php?error=Milestone not found");
    exit;
}

// Get all active projects
$projects = fetchAll("SELECT id, project_name FROM projects WHERE status = 'active' ORDER BY project_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $project_id = intval($_POST['project_id']);
        $phase_name = sanitize($_POST['phase_name']);
        $description = sanitize($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];

        if (empty($project_id) || empty($phase_name) || empty($start_date) || empty($end_date)) {
            $error = 'Please fill all required fields';
        } else {
            $result = runQuery(
                "UPDATE project_milestones SET project_id = ?, phase_name = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?",
                "isssssi",
                [$project_id, $phase_name, $description, $start_date, $end_date, $status, $milestone_id]
            );

            if ($result['success']) {
                $success = 'Milestone updated successfully';
                // Refresh milestone data
                $milestone = fetchOne("SELECT * FROM project_milestones WHERE id = ?", "i", [$milestone_id]);
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
        <h2>Edit Milestone</h2>
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
                    <select name="project_id" id="project_id" class="form-control" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $project['id'] == $milestone['project_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phase_name">Phase Name <span class="text-danger">*</span></label>
                    <input type="text" name="phase_name" id="phase_name" class="form-control"
                           value="<?php echo htmlspecialchars($milestone['phase_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($milestone['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                               value="<?php echo $milestone['start_date']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                               value="<?php echo $milestone['end_date']; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="pending" <?php echo $milestone['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $milestone['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $milestone['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Milestone
                    </button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>