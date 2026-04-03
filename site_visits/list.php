<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'site_visits', 'list');

$page_title = 'Site Visits';

$filter_enquiry_id = $_GET['enquiry_id'] ?? 0;

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Site Visits</h2>
        <div>
            <?php if ($filter_enquiry_id): ?>
                <a href="../enquiry/view.php?id=<?php echo $filter_enquiry_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Enquiry
                </a>
            <?php else: ?>
                <a href="../enquiry/list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Enquiries
                </a>
            <?php endif; ?>
            <a href="create.php<?php echo $filter_enquiry_id ? '?enquiry_id=' . $filter_enquiry_id : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Site Visit
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-body">
            <?php if ($filter_enquiry_id): ?>
                <p class="text-muted">Showing visits for Enquiry ID: <strong><?php echo $filter_enquiry_id; ?></strong></p>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="siteVisitsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if (!$filter_enquiry_id): ?>
                                <th>Enquiry ID</th>
                                <th>Client Name</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Engineer</th>
                            <th>Notes</th>
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
        var columns = [{
                data: 'id'
            },

          

            {
                data: 'scheduled_date'
            },
            {
                data: 'scheduled_time'
            },
            {
                data: 'engineer_name'
            },
            {
                data: 'visit_notes'
            },
            {
                data: 'visit_completed'
            },
            {
                data: 'id'
            }
        ];

        var columnDefs = [{
                targets: <?php echo $filter_enquiry_id ? 1 : 3; ?>,
                render: function(data) {
                    return formatDate(data);
                }
            },
            {
                targets: <?php echo $filter_enquiry_id ? 2 : 4; ?>,
                render: function(data) {
                    return data || '-';
                }
            },
            {
                targets: <?php echo $filter_enquiry_id ? 3 : 5; ?>,
                render: function(data) {
                    return escapeHtml(data || '-');
                }
            },
            {
                targets: <?php echo $filter_enquiry_id ? 4 : 6; ?>,
                render: function(data) {
                    return escapeHtml(data || '-');
                }
            },
            {
                targets: <?php echo $filter_enquiry_id ? 5 : 7; ?>,
                render: function(data) {
                    return data == 1 ?
                        '<span class="badge bg-success">Completed</span>' :
                        '<span class="badge bg-warning">Pending</span>';
                }
            },
            {
                targets: <?php echo $filter_enquiry_id ? 6 : 8; ?>,
                render: function(data, type, row) {
                    return '<a href="edit.php?id=' + data + '&enquiry_id=' + row.enquiry_id + '" class="me-2 text-warning">' +
                        '<i class="fas fa-edit fa-lg"></i>' +
                        '</a>' +
                        '<button class="btn-delete text-danger border-0 bg-transparent" data-id="' + data + '">' +
                        '<i class="fas fa-trash fa-lg"></i>' +
                        '</button>';
                }
            }
        ];
        var table = $('#siteVisitsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'api.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'get_site_visits';
                    <?php if ($filter_enquiry_id): ?>
                        d.enquiry_id = <?php echo $filter_enquiry_id; ?>;
                    <?php endif; ?>
                },
                dataSrc: function(json) {
                    if (!json.success) {
                        console.error(json.message);
                        return [];
                    }
                    return json.site_visits || [];
                }
            },
            columns: columns,
            columnDefs: columnDefs,
            language: {
                emptyTable: "No site visits found",
                loadingRecords: "Loading...",
                processing: "Processing..."
            }
        });

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            var date = new Date(dateStr);
            var day = String(date.getDate()).padStart(2, '0');
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var year = date.getFullYear();
            return day + '-' + month + '-' + year;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Delete site visit
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            if (confirm('Are you sure you want to delete this site visit?')) {
                $.ajax({
                    url: 'api.php',
                    type: 'POST',
                    data: {
                        action: 'delete_site_visit',
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
                        $('#message-container').html('<div class="alert alert-danger">An error occurred.</div>');
                    }
                });
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>