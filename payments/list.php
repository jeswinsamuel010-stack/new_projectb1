<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'payments', 'list');

$page_title = 'Stage Payments';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Stage Payments</h2>
        <?php if (in_array($_SESSION['role'], ['admin', 'accounts'])): ?>
        <a href="add_payment.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Payment
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table id="paymentsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Stage Name</th>
                            <th>Payment Type</th>
                            <th>Amount (INR)</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#paymentsTable', '/new_projectb/api/payments.php', [
        { title: 'ID' },
        { title: 'Project' },
        { title: 'Stage Name' },
        { title: 'Payment Type' },
        { title: 'Amount (INR)' },
        { title: 'Payment Date' },
        { title: 'Status' },
        { title: 'Actions', orderable: false }
    ]);
});
</script>

<?php require_once '../includes/footer.php'; ?>