<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'enquiry', 'list');

$page_title = 'Enquiries';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Enquiries</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Enquiry
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table table-striped table-bordered" id="enquiriesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Phone</th>
                            <th>Project Type</th>
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
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#enquiriesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'api.php',
            type: 'POST',
            data: function(d) {
                d.action = 'get_enquiries';
            },
            dataSrc: function(json) {
                return json.enquiries || [];
            }
        },
        columns: [
            { data: 'id' },
            { data: 'client_name' },
            { data: 'phone' },
            { data: 'project_type' },
            { data: 'status' },
            { data: 'created_at' },
            { data: 'id' }
        ],
        columnDefs: [
            {
                targets: 0,
                render: function(data) {
                    return data;
                }
            },
            {
                targets: 1,
                render: function(data) {
                    return escapeHtml(data);
                }
            },
            {
                targets: 2,
                render: function(data) {
                    return escapeHtml(data);
                }
            },
            {
                targets: 3,
                render: function(data) {
                    if (!data) return '-';
                    return data.charAt(0).toUpperCase() + data.slice(1);
                }
            },
            {
                targets: 4,
                render: function(data) {
                    var statusColors = {
                        'new': 'badge bg-primary',
                        'site_visit_scheduled': 'badge bg-info',
                        'measurement_added': 'badge bg-info',
                        'quotation_prepared': 'badge bg-warning',
                        'advance_paid': 'badge bg-success',
                        'project_started': 'badge bg-success',
                        'in_progress': 'badge bg-warning',
                        'completed': 'badge bg-secondary',
                        'rejected': 'badge bg-danger',
                        'cancelled': 'badge bg-danger'
                    };
                    var statusText = data.replace(/_/g, ' ').replace(/\b\w/g, function(l) {
                        return l.toUpperCase();
                    });
                    return '<span class="' + (statusColors[data] || 'badge bg-secondary') + '">' + statusText + '</span>';
                }
            },
            {
                targets: 5,
                render: function(data) {
                    if (!data) return '-';
                    var date = new Date(data);
                    return date.toLocaleDateString('en-GB');
                }
            },
            {
                targets: 6,
                render: function(data) {
                    return '<a href="view.php?id=' + data + '" class="btn btn-sm btn-link text-primary me-1">' +
                        '<i class="fas fa-eye"></i>' +
                        '</a>' +
                        '<a href="edit.php?id=' + data + '" class="btn btn-sm btn-link text-warning me-1">' +
                        '<i class="fas fa-edit"></i>' +
                        '</a>' +
                        '<button class="btn btn-sm btn-link text-danger btn-delete" data-id="' + data + '">' +
                        '<i class="fas fa-trash"></i>' +
                        '</button>';
                },
                orderable: false,
                searchable: false
            }
        ],
        language: {
            emptyTable: "No enquiries found",
            loadingRecords: "Loading...",
            processing: "Processing..."
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Delete enquiry
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to delete this enquiry?')) {
            $.ajax({
                url: 'api.php',
                type: 'POST',
                data: {
                    action: 'delete_enquiry',
                    id: id
                },
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
                    $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                }
            });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>