<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'approval', 'list');

$page_title = 'Approvals';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'];
    $remarks = sanitize($_POST['remarks'] ?? '');

    if ($request_id > 0 && in_array($action, ['approve', 'reject'])) {
        $status = $action == 'approve' ? 'approved' : 'rejected';

        $result = runQuery(
            "UPDATE material_requests SET status = ?, approved_by = ?, approval_remarks = ? WHERE id = ?",
            "sisi",
            [$status, $_SESSION['user_id'], $remarks, $request_id]
        );

        if ($result['success']) {
            header("Location: index.php?success=Request " . $status);
            exit;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Material Request Approvals</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- Pending Requests -->
    <div class="card">
        <div class="card-header">
            <h3>Pending Requests</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table id="pendingTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Processed Requests -->
    <div class="card">
        <div class="card-header">
            <h3>Recently Processed</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table id="processedTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Approved By</th>
                            <th>Remarks</th>
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

<!-- Action Modal -->
<div id="actionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Approve Request</h3>
            <button class="modal-close" onclick="closeModal('actionModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="request_id" id="modalRequestId">
                <input type="hidden" name="action" id="modalAction">
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" placeholder="Optional remarks"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('actionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modalSubmit">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAction(id, action) {
    document.getElementById('modalRequestId').value = id;
    document.getElementById('modalAction').value = action;
    document.getElementById('modalTitle').textContent = action == 'approve' ? 'Approve Request' : 'Reject Request';

    const submitBtn = document.getElementById('modalSubmit');
    if (action == 'approve') {
        submitBtn.className = 'btn btn-success';
        submitBtn.textContent = 'Approve';
    } else {
        submitBtn.className = 'btn btn-danger';
        submitBtn.textContent = 'Reject';
    }

    openModal('actionModal');
}

$(document).ready(function() {
    // Pending table
    initDataTable('#pendingTable', '/new_projectb/api/approvals.php', [
        { title: 'ID' },
        { title: 'Project' },
        { title: 'Item' },
        { title: 'Quantity' },
        { title: 'Reason' },
        { title: 'Requested By' },
        { title: 'Date' },
        { title: 'Actions', orderable: false }
    ], [[0, 'asc']], { type: 'pending' });

    // Processed table
    initDataTable('#processedTable', '/new_projectb/api/approvals.php', [
        { title: 'ID' },
        { title: 'Project' },
        { title: 'Item' },
        { title: 'Quantity' },
        { title: 'Status' },
        { title: 'Approved By' },
        { title: 'Remarks' },
        { title: 'Date' }
    ], [[7, 'desc']], { type: 'processed' });
});
</script>

<?php require_once '../includes/footer.php'; ?>