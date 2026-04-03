<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$page_title = 'Project Details';

// Get project ID
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $project = fetchOne("SELECT * FROM projects WHERE id = ?", "i", [$id]);
}

if (!$project) {
    header("Location: list.php?error=Project not found");
    exit;
}

// Get related material requests
$requests = fetchAll(
    "SELECT mr.*, u.full_name as requested_by_name
     FROM material_requests mr
     LEFT JOIN users u ON mr.requested_by = u.id
     WHERE mr.project_id = ?
     ORDER BY mr.created_at DESC",
    "i",
    [$id]
);

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><?php echo htmlspecialchars($project['project_name']); ?></h2>
        <a href="list.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Projects
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Project Information</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Client Name</label>
                    <p><?php echo htmlspecialchars($project['client_name']); ?></p>
                </div>
                <div class="form-group">
                    <label>Project Value</label>
                    <p><?php echo number_format($project['project_value'], 2); ?></p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date</label>
                    <p><?php echo date('d M Y', strtotime($project['start_date'])); ?></p>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <p>
                        <span class="badge badge-<?php echo $project['status']; ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <p><?php echo nl2br(htmlspecialchars($project['description'] ?? 'No description')); ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Material Requests</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No material requests</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                <td><?php echo $req['quantity'] . ' ' . $req['unit']; ?></td>
                                <td><?php echo htmlspecialchars($req['reason']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $req['status']; ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($req['requested_by_name'] ?? '-'); ?></td>
                                <td><?php echo date('d M Y', strtotime($req['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>