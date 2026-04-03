<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'requests', 'list');

$page_title = 'Material Requests';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Material Requests</h2>
        <?php if (in_array($_SESSION['role'], ['admin', 'site_engineer'])): ?>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Request
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
                <table id="requestsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Date</th>
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
    initDataTable('#requestsTable', '/new_projectb/api/requests.php', [
        { title: 'ID' },
        { title: 'Project' },
        { title: 'Item Name' },
        { title: 'Quantity' },
        { title: 'Reason' },
        { title: 'Status' },
        { title: 'Date' }
    ]);
});
</script>

<?php require_once '../includes/footer.php'; ?>