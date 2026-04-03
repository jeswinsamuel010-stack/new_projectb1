<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'approval', 'list');

$page_title = 'Approvals';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-check-circle"></i> Approvals</h2>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php
        $counts = [
            'pending' => fetchOne("SELECT COUNT(*) as c FROM user_material_requests WHERE status = 'pending'")['c'],
            'approved' => fetchOne("SELECT COUNT(*) as c FROM user_material_requests WHERE status = 'approved'")['c'],
            'rejected' => fetchOne("SELECT COUNT(*) as c FROM user_material_requests WHERE status = 'rejected'")['c']
        ];
        ?>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h4>Pending</h4>
                    <p class="stat-value"><?php echo $counts['pending']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success"><i class="fas fa-check"></i></div>
                <div class="stat-info">
                    <h4>Approved</h4>
                    <p class="stat-value"><?php echo $counts['approved']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-danger"><i class="fas fa-times"></i></div>
                <div class="stat-info">
                    <h4>Rejected</h4>
                    <p class="stat-value"><?php echo $counts['rejected']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="approvalTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Request No.</th>
                        <th>Project</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Requested By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div id="approvalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Review Request</h3>
            <button class="modal-close" onclick="closeModal('approvalModal')">&times;</button>
        </div>
        <div class="modal-body" id="approvalBody">
        </div>
        <form id="approvalForm">
            <div class="modal-body">
                <input type="hidden" name="request_id" id="approvalRequestId">
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" id="approvalRemarks" class="form-control" rows="3" placeholder="Add remarks (optional)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="submitApproval('rejected')">
                    <i class="fas fa-times"></i> Reject
                </button>
                <button type="button" class="btn btn-success" onclick="submitApproval('approved')">
                    <i class="fas fa-check"></i> Approve
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}
.bg-warning { background: #ffc107; color: #000; }
.bg-success { background: #28a745; }
.bg-danger { background: #dc3545; }
.stat-info h4 { margin: 0; font-size: 14px; color: #6c757d; }
.stat-value { margin: 5px 0 0; font-size: 24px; font-weight: bold; }
</style>

<script>
let approvalTable;

$(document).ready(function() {
    approvalTable = $('#approvalTable').DataTable({
        ajax: {
            url: '../api/user_side.php?module=request',
            type: 'POST',
            data: function(d) {
                d.action = 'list';
                d.status = 'pending';
            }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }, { data: 7 }
        ]
    });
});

function openApproval(id) {
    $.post('../api/user_side.php?module=request', { action: 'get', request_id: id }, function(response) {
        if (response.success) {
            const r = response.data;
            $('#approvalRequestId').val(id);
            $('#approvalBody').html(`
                <table class="table table-bordered">
                    <tr><th>Request No.</th><td>${r.request_number}</td></tr>
                    <tr><th>Project</th><td>${r.project_name}</td></tr>
                    <tr><th>Item</th><td>${r.item_name}</td></tr>
                    <tr><th>Quantity</th><td>${r.quantity} ${r.unit}</td></tr>
                    <tr><th>Location</th><td>${r.location || 'N/A'}</td></tr>
                    <tr><th>Reason</th><td>${r.reason || 'N/A'}</td></tr>
                    <tr><th>Requested By</th><td>${r.requested_by_name}</td></tr>
                    <tr><th>Date</th><td>${r.created_at}</td></tr>
                </table>
            `);
            openModal('approvalModal');
        }
    }, 'json');
}

function submitApproval(status) {
    const requestId = $('#approvalRequestId').val();
    const remarks = $('#approvalRemarks').val();

    if (!confirm('Are you sure you want to ' + status + ' this request?')) {
        return;
    }

    $.post('../api/user_side.php?module=approval', {
        action: 'approve',
        request_id: requestId,
        status: status,
        remarks: remarks
    }, function(response) {
        if (response.success) {
            closeModal('approvalModal');
            approvalTable.ajax.reload();
            alert(response.message);

            // Auto-redirect to issue page if approved
            if (response.redirect) {
                window.location.href = response.redirect;
            }
        } else {
            alert(response.message);
        }
    }, 'json');
}

// Auto refresh every 30 seconds
setInterval(function() {
    approvalTable.ajax.reload();
}, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>