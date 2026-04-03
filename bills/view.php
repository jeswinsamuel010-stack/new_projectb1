<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'bills', 'view');

$page_title = 'Bill Details';

// Get bill ID
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $bill = fetchOne("
        SELECT b.*, p.project_name, p.client_name, u.full_name as created_by_name
        FROM bills b
        LEFT JOIN projects p ON b.project_id = p.id
        LEFT JOIN users u ON b.created_by = u.id
        WHERE b.id = ?
    ", "i", [$id]);
}

if (!$bill) {
    header("Location: list.php?error=Bill not found");
    exit;
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Bill Details</h2>
        <a href="list.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Bills
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Bill Information</h3>
            <span class="badge badge-<?php echo $bill['status']; ?>"><?php echo ucfirst($bill['status']); ?></span>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Bill Number</label>
                    <p><strong><?php echo htmlspecialchars($bill['bill_number']); ?></strong></p>
                </div>
                <div class="form-group">
                    <label>Project</label>
                    <p><?php echo htmlspecialchars($bill['project_name']); ?></p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Client</label>
                    <p><?php echo htmlspecialchars($bill['client_name']); ?></p>
                </div>
                <div class="form-group">
                    <label>Bill Date</label>
                    <p><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Amount</label>
                    <p><strong style="font-size: 18px;"><?php echo number_format($bill['amount'], 2); ?></strong></p>
                </div>
                <div class="form-group">
                    <label>Created By</label>
                    <p><?php echo htmlspecialchars($bill['created_by_name']); ?></p>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <p><?php echo nl2br(htmlspecialchars($bill['description'] ?? 'No description')); ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>