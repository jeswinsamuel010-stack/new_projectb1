<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'issues', 'list');

$page_title = 'Issue Material';

// Check for auto-redirect from approval
$prefilled_request_id = $_GET['request_id'] ?? 0;

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-truck-loading"></i> Issue Material</h2>
    </div>

    <div class="row">
        <!-- Issue Form -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane"></i> Issue to Site</h3>
                </div>
                <div class="card-body">
                    <form id="issueForm">
                        <div class="form-group">
                            <label>Select Approved Request *</label>
                            <select name="request_id" id="requestSelect" class="form-control" onchange="loadRequestDetails()" required>
                                <option value="">Select Request</option>
                                <?php
                                $approved_requests = fetchAll("
                                    SELECT r.id, r.request_number, r.item_name, r.quantity, r.unit, p.project_name
                                    FROM user_material_requests r
                                    LEFT JOIN projects p ON r.project_id = p.id
                                    WHERE r.status = 'approved'
                                    ORDER BY r.created_at DESC
                                ");
                                foreach ($approved_requests as $req) {
                                    $selected = ($prefilled_request_id == $req['id']) ? 'selected' : '';
                                    echo '<option value="' . $req['id'] . '" ' . $selected . '>' .
                                         htmlspecialchars($req['request_number']) . ' - ' .
                                         htmlspecialchars($req['item_name']) . ' (' . $req['quantity'] . ' ' . $req['unit'] . ')' .
                                         '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div id="requestDetails" style="display: none;">
                            <div class="request-info">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Project</small>
                                        <p id="detailProject"></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Requested Qty</small>
                                        <p id="detailQty"></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Location</small>
                                        <p id="detailLocation"></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Available Stock</small>
                                        <p id="detailStock"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="stock-alert" id="stockAlert" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span id="stockAlertMsg"></span>
                            </div>

                            <div class="form-group">
                                <label>Issue Quantity *</label>
                                <input type="number" name="issue_quantity" id="issueQuantity" class="form-control" step="0.01" min="0.01" required>
                            </div>

                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check"></i> Issue Material
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Issues List -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3>Recent Issues</h3>
                </div>
                <div class="card-body">
                    <table id="issueTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Issue No.</th>
                                <th>Request No.</th>
                                <th>Project</th>
                                <th>Item</th>
                                <th>Qty Issued</th>
                                <th>Date</th>
                                <th>Issued By</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.request-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.request-info p {
    margin: 5px 0;
    font-weight: 500;
}
.stock-alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.stock-alert.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
.stock-alert.danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.stock-alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.form-actions {
    margin-top: 20px;
}
</style>

<script>
let issueTable;
let currentRequest = null;

$(document).ready(function() {
    issueTable = $('#issueTable').DataTable({
        ajax: {
            url: '../api/user_side.php?module=issue',
            type: 'POST',
            data: function(d) { d.action = 'list'; }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }
        ]
    });

    $('#issueForm').on('submit', function(e) {
        e.preventDefault();

        const issueQty = parseFloat($('#issueQuantity').val());
        const available = currentRequest ? currentRequest.available_stock : 0;

        if (issueQty > available) {
            alert('Insufficient stock! Available: ' + available);
            return;
        }

        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('request_id', $('#requestSelect').val());
        formData.append('issue_quantity', issueQty);
        formData.append('notes', $('textarea[name="notes"]').val());

        $.ajax({
            url: '../api/user_side.php?module=issue',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Material issued successfully! Stock updated.');
                    $('#issueForm')[0].reset();
                    $('#requestDetails').css('display', 'none');
                    issueTable.ajax.reload();
                } else {
                    alert(response.message);
                }
            },
            dataType: 'json'
        });
    });

    // Auto-load if request_id provided
    <?php if ($prefilled_request_id): ?>
    loadRequestDetails();
    <?php endif; ?>
});

function loadRequestDetails() {
    const requestId = $('#requestSelect').val();
    if (!requestId) {
        $('#requestDetails').css('display', 'none');
        currentRequest = null;
        return;
    }

    $.post('../api/user_side.php?module=issue', { action: 'get_for_issue', request_id: requestId }, function(response) {
        if (response.success) {
            currentRequest = response.data;
            const r = response.data;

            $('#detailProject').text(r.project_name);
            $('#detailQty').text(r.quantity + ' ' + r.unit);
            $('#detailLocation').text(r.location || 'N/A');
            $('#detailStock').text(r.available_stock + ' ' + r.unit);

            // Stock validation
            const stockAlert = $('#stockAlert');
            if (r.available_stock <= 0) {
                stockAlert.attr('class', 'stock-alert danger');
                stockAlert.html('<i class="fas fa-times-circle"></i> OUT OF STOCK! Cannot issue.');
                stockAlert.css('display', 'flex');
                $('button[type="submit"]').prop('disabled', true);
            } else if (r.available_stock < r.quantity) {
                stockAlert.attr('class', 'stock-alert warning');
                stockAlert.html('<i class="fas fa-exclamation-triangle"></i> Low stock! Request may be partially fulfilled.');
                stockAlert.css('display', 'flex');
                $('button[type="submit"]').prop('disabled', false);
            } else {
                stockAlert.attr('class', 'stock-alert success');
                stockAlert.html('<i class="fas fa-check-circle"></i> Stock available');
                stockAlert.css('display', 'flex');
                $('button[type="submit"]').prop('disabled', false);
            }

            // Set max quantity
            $('#issueQuantity').attr('max', r.available_stock);
            $('#issueQuantity').val(r.quantity);

            $('#requestDetails').css('display', 'block');
        }
    }, 'json');
}
</script>

<?php require_once '../includes/footer.php'; ?>