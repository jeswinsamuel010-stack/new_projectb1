<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'requests', 'create');

$page_title = 'New Material Request';
$error = '';

// Get active projects
$projects = fetchAll("SELECT id, project_name FROM projects WHERE status = 'active' ORDER BY project_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_id = intval($_POST['project_id'] ?? 0);
    $item_name = sanitize($_POST['item_name'] ?? '');
    $quantity = floatval($_POST['quantity'] ?? 0);
    $unit = sanitize($_POST['unit'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');

    if (empty($project_id) || empty($item_name) || empty($quantity) || empty($unit)) {
        $error = 'Please fill in all required fields';
    } else {
        $result = insertAndGetId(
            "INSERT INTO material_requests (project_id, item_name, quantity, unit, reason, requested_by, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')",
            "isdssi",
            [$project_id, $item_name, $quantity, $unit, $reason, $_SESSION['user_id']]
        );

        if ($result['success']) {
            header("Location: list.php?success=Material request submitted successfully");
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
        <h2>New Material Request</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="project_id">Project *</label>
                        <select id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="item_name">Item Name *</label>
                        <input type="text" id="item_name" name="item_name" placeholder="e.g., Cement, Steel Bars" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit *</label>
                        <select id="unit" name="unit" required>
                            <option value="">Select Unit</option>
                            <option value="pieces">Pieces</option>
                            <option value="bags">Bags</option>
                            <option value="kg">KG</option>
                            <option value="tons">Tons</option>
                            <option value="cu.mt">Cubic Meter</option>
                            <option value="sq.ft">Square Feet</option>
                            <option value="liter">Liter</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Reason</label>
                    <textarea id="reason" name="reason" rows="3" placeholder="Reason for requesting this material"></textarea>
                </div>

                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
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