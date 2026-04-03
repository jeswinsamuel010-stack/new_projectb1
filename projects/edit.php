<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'projects', 'edit');

$page_title = 'Edit Project';
$error = '';
$project = null;

// Get project ID
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $project = fetchOne("SELECT * FROM projects WHERE id = ?", "i", [$id]);
}

if (!$project) {
    header("Location: list.php?error=Project not found");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_name = sanitize($_POST['project_name'] ?? '');
    $client_name = sanitize($_POST['client_name'] ?? '');
    $project_value = floatval($_POST['project_value'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');

    if (empty($project_name) || empty($client_name) || empty($project_value) || empty($start_date)) {
        $error = 'Please fill in all required fields';
    } else {
        $result = runQuery(
            "UPDATE projects SET project_name = ?, client_name = ?, project_value = ?, start_date = ?, description = ?, status = ? WHERE id = ?",
            "ssssssi",
            [$project_name, $client_name, $project_value, $start_date, $description, $status, $id]
        );

        if ($result['success']) {
            header("Location: list.php?success=Project updated successfully");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Edit Project</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="project_name">Project Name *</label>
                        <input type="text" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="client_name">Client Name *</label>
                        <input type="text" id="client_name" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="project_value">Project Value (INR) *</label>
                        <input type="number" id="project_value" name="project_value" step="0.01" min="0" value="<?php echo $project['project_value']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $project['start_date']; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo $project['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $project['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>

                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Project
                    </button>
                    <a href="list.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>