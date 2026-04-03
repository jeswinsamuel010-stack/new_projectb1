<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'measurements', 'list');

$page_title = 'Measurements';

$filter_enquiry_id = $_GET['enquiry_id'] ?? 0;

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Measurements</h2>
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
                <i class="fas fa-plus"></i> Add Measurement
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
                <p class="text-muted">Showing measurements for Enquiry ID: <strong><?php echo $filter_enquiry_id; ?></strong></p>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="measurementsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if (!$filter_enquiry_id): ?>
                                <th>Enquiry ID</th>
                            <?php endif; ?>
                            <th>Area (sq.ft)</th>
                            <th>Dimensions (L × W × H)</th>
                            <th>Notes</th>
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
        var columns = [
    { data: 'id' },

    <?php if (!$filter_enquiry_id): ?>
    { data: 'enquiry_id' },
    <?php endif; ?>

    { data: 'area_sqft' },
    { data: null }, // 👈 Dimensions column
    { data: 'measurement_notes' },
    { data: 'created_at' },
    { data: 'id' }
];

       var columnDefs = [

    // ✅ AREA
    {
        targets: <?php echo ($filter_enquiry_id ? 1 : 2); ?>,
        render: function(data) {
            return parseFloat(data).toFixed(2);
        }
    },

    // ✅ DIMENSIONS (FIXED)
    {
        targets: <?php echo ($filter_enquiry_id ? 2 : 3); ?>,
        render: function(data, type, row) {

            let length = parseFloat(row.length) || 0;
            let width  = parseFloat(row.width) || 0;
            let height = parseFloat(row.height) || 0;

            if (!length && !width && !height) return '-';

            return `${length} × ${width} × ${height}`;
        }
    },

    // ✅ NOTES
    {
        targets: <?php echo ($filter_enquiry_id ? 3 : 4); ?>,
        render: function(data) {
            return escapeHtml(data || '-');
        }
    },

    // ✅ DATE
    {
        targets: <?php echo ($filter_enquiry_id ? 4 : 5); ?>,
        render: function(data) {
            return formatDate(data);
        }
    },

    // ✅ ACTIONS
    {
        targets: <?php echo ($filter_enquiry_id ? 5 : 6); ?>,
        render: function(data, type, row) {
            return '<a href="edit.php?id=' + data + '&enquiry_id=' + row.enquiry_id + '" class="btn btn-sm btn-warning me-1">' +
                '<i class="fas fa-edit"></i>' +
                '</a>' +
                '<button class="btn btn-sm btn-danger btn-delete" data-id="' + data + '">' +
                '<i class="fas fa-trash"></i>' +
                '</button>';
        },
        orderable: false,
        searchable: false
    }
];

        var table = $('#measurementsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'api.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'get_measurements';
                    <?php if ($filter_enquiry_id): ?>
                        d.enquiry_id = <?php echo $filter_enquiry_id; ?>;
                    <?php endif; ?>
                },
                dataSrc: function(json) {
                    console.log(json);                    
                    return json.measurements || [];
                }
            },
            columns: columns,
            columnDefs: columnDefs,
            language: {
                emptyTable: "No measurements found",
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

        // Delete measurement
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            if (confirm('Are you sure you want to delete this measurement?')) {
                $.ajax({
                    url: 'api.php',
                    type: 'POST',
                    data: {
                        action: 'delete_measurement',
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