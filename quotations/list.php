<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'quotation', 'list');

$page_title = 'Quotations';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Quotations</h2>
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <!-- <a href="add_quotation.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Quotation
        </a> -->
        <div>
                <a href="add_quotation.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus"></i> New Quotation
                </a>

                <!-- ✅ NEW IMPORT BUTTON -->
                <a href="import_quotation.php" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Import Excel
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div id="message-container"></div>

    <!-- Status Filter Tabs -->
    <ul class="nav nav-tabs mb-3" id="statusTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-status="all">All</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-status="Draft">Draft</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-status="Sent">Sent</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-status="Approved">Approved</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-status="Rejected">Rejected</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-status="Converted">Converted</button>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table id="quotationsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Quotation #</th>
                            <th>Client Name</th>
                            <th>Project Title</th>
                            <th>Amount</th>
                            <th>Valid Until</th>
                            <th>Status</th>
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
</div>

<script>
var table;
var currentStatus = 'all';

$(document).ready(function() {
    table = $('#quotationsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'api.php',
            type: 'POST',
            data: function(d) {
                d.action = 'get_quotations';
            },
            dataSrc: function(json) {
                var quotations = json.quotations || [];
                if (currentStatus !== 'all') {
                    quotations = quotations.filter(function(q) {
                        return q.status === currentStatus;
                    });
                }
                return quotations;
            }
        },
        columns: [
            { data: 'id' },
            { data: 'quotation_number' },
            { data: 'client_name' },
            { data: 'project_title' },
            { data: 'estimated_amount' },
            { data: 'valid_until' },
            { data: 'status' },
            { data: 'created_at' },
            { data: 'id' }
        ],
        columnDefs: [
            {
                targets: 0,
                render: function(data) { return data; }
            },
            {
                targets: 1,
                render: function(data) { return escapeHtml(data); }
            },
            {
                targets: 2,
                render: function(data) { return escapeHtml(data); }
            },
            {
                targets: 3,
                render: function(data) { return escapeHtml(data); }
            },
            {
                targets: 4,
                render: function(data) {
                    if (!data) return '-';
                    return 'INR ' + parseFloat(data).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            },
            {
                targets: 5,
                render: function(data) {
                    if (!data) return '-';
                    return new Date(data).toLocaleDateString('en-GB');
                }
            },
            {
                targets: 6,
                render: function(data) {
                    var statusColors = {
                        'Draft': 'badge bg-secondary',
                        'Sent': 'badge bg-info',
                        'Approved': 'badge bg-success',
                        'Rejected': 'badge bg-danger',
                        'Converted': 'badge bg-primary'
                    };
                    return '<span class="' + (statusColors[data] || 'badge bg-secondary') + '">' + data + '</span>';
                }
            },
            {
                targets: 7,
                render: function(data) {
                    if (!data) return '-';
                    return new Date(data).toLocaleDateString('en-GB');
                }
            },
            {
                targets: 8,
                render: function(data, type, row) {
                    var buttons = '<a href="view_quotation.php?id=' + data + '" class="btn btn-sm btn-link text-primary me-1" title="View">' +
                        '<i class="fas fa-eye"></i></a>';

                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    // Show edit/delete only for non-approved, non-converted quotations
                    if (row.status !== 'Approved' && row.status !== 'Converted') {
                        buttons += '<a href="edit_quotation.php?id=' + data + '" class="btn btn-sm btn-link text-warning me-1" title="Edit">' +
                            '<i class="fas fa-edit"></i></a>' +
                            '<button class="btn btn-sm btn-link text-danger btn-delete" data-id="' + data + '" title="Delete">' +
                            '<i class="fas fa-trash"></i></button>';
                    }
                    <?php endif; ?>

                    return buttons;
                },
                orderable: false,
                searchable: false
            }
        ],
        language: {
            emptyTable: "No quotations found",
            loadingRecords: "Loading...",
            processing: "Processing..."
        }
    });

    // Status filter tabs
    $('#statusTabs button').on('click', function() {
        $('#statusTabs button').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('bs-status');
        table.ajax.reload();
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Delete quotation
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to delete this quotation?')) {
            $.ajax({
                url: 'api.php',
                type: 'POST',
                data: { action: 'delete_quotation', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                        table.ajax.reload();
                    } else {
                        $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#message-container').html('<div class="alert alert-danger">An error occurred.</div>');
                }
            });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>