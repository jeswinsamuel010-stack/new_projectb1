<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'requests', 'list');

$page_title = 'Material Requests';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-clipboard-list"></i> Material Requests</h2>
        <button class="btn btn-primary" onclick="openModal('requestModal')">
            <i class="fas fa-plus"></i> New Request
        </button>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <select id="statusFilter" class="form-control" onchange="reloadTable()">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="issued">Issued</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="requestTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Request No.</th>
                        <th>Project</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Request Modal -->
<div id="requestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="requestModalTitle">New Material Request</h3>
            <button class="modal-close" onclick="closeModal('requestModal')">&times;</button>
        </div>
        <form id="requestForm">
            <div class="modal-body">
                <input type="hidden" name="request_id" id="requestId">
                <div class="form-group">
                    <label>Project *</label>
                    <select name="project_id" id="projectId" class="form-control" required>
                        <option value="">Select Project</option>
                        <?php
                        $projects = fetchAll("SELECT id, project_name FROM projects WHERE status = 'active'");
                        foreach ($projects as $p) {
                            echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['project_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Item Name *</label>
                    <input type="text" name="item_name" id="itemName" class="form-control" placeholder="e.g., Cement, Steel Bars, Bricks" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Unit *</label>
                            <select name="unit" id="unit" class="form-control" required>
                                <option value="pieces">Pieces</option>
                                <option value="bags">Bags</option>
                                <option value="kg">Kg</option>
                                <option value="ton">Ton</option>
                                <option value="cu.mt">Cu.mt</option>
                                <option value="liter">Liter</option>
                                <option value="sq.ft">Sq.ft</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" id="location" class="form-control" placeholder="Site location">
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" id="reason" class="form-control" rows="2" placeholder="Why is this needed?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('requestModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<!-- View Request Modal -->
<div id="viewRequestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Request Details</h3>
            <button class="modal-close" onclick="closeModal('viewRequestModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewRequestBody">
        </div>
    </div>
</div>

<script>
let requestTable;

$(document).ready(function() {
    requestTable = $('#requestTable').DataTable({
        ajax: {
            url: '../api/user_side.php?module=request',
            type: 'POST',
            data: function(d) {
                d.action = 'list';
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }
        ]
    });

    $('#requestForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            action: $('#requestId').val() ? 'update' : 'create',
            project_id: $('#projectId').val(),
            item_name: $('#itemName').val(),
            quantity: $('#quantity').val(),
            unit: $('#unit').val(),
            location: $('#location').val(),
            reason: $('#reason').val()
        };

        if ($('#requestId').val()) {
            formData.request_id = $('#requestId').val();
        }

        $.post('../api/user_side.php?module=request', formData, function(response) {
            if (response.success) {
                closeModal('requestModal');
                requestTable.ajax.reload();
                resetForm();
                alert(response.message);
            } else {
                alert(response.message);
            }
        }, 'json');
    });
});

function reloadTable() {
    requestTable.ajax.reload();
}

function viewRequest(id) {
    $.post('../api/user_side.php?module=request', { action: 'get', request_id: id }, function(response) {
        if (response.success) {
            const r = response.data;
            const statusClass = {
                'pending': 'warning',
                'approved': 'success',
                'rejected': 'danger',
                'issued': 'info'
            };

            $('#viewRequestBody').html(`
                <table class="table table-bordered">
                    <tr><th>Request No.</th><td>${r.request_number}</td></tr>
                    <tr><th>Project</th><td>${r.project_name}</td></tr>
                    <tr><th>Item</th><td>${r.item_name}</td></tr>
                    <tr><th>Quantity</th><td>${r.quantity} ${r.unit}</td></tr>
                    <tr><th>Location</th><td>${r.location || 'N/A'}</td></tr>
                    <tr><th>Reason</th><td>${r.reason || 'N/A'}</td></tr>
                    <tr><th>Status</th><td><span class="badge bg-${statusClass[r.status]}">${r.status.toUpperCase()}</span></td></tr>
                    <tr><th>Requested By</th><td>${r.requested_by_name}</td></tr>
                    <tr><th>Date</th><td>${r.created_at}</td></tr>
                    ${r.approval_remarks ? `<tr><th>Remarks</th><td>${r.approval_remarks}</td></tr>` : ''}
                </table>
            `);
            openModal('viewRequestModal');
        }
    }, 'json');
}

function deleteRequest(id) {
    if (confirm('Are you sure you want to delete this request?')) {
        $.post('../api/user_side.php?module=request', { action: 'delete', request_id: id }, function(response) {
            requestTable.ajax.reload();
            alert(response.message);
        }, 'json');
    }
}

function resetForm() {
    $('#requestForm')[0].reset();
    $('#requestId').val('');
    $('#requestModalTitle').text('New Material Request');
}
</script>

<?php require_once '../includes/footer.php'; ?>