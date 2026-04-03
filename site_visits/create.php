<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'site_visits', 'create');

$page_title = 'Schedule Site Visit';

$enquiry_id = $_GET['enquiry_id'] ?? 0;

if (!$enquiry_id) {
    header("Location: list.php?error=Invalid enquiry");
    exit;
}

$enquiry = fetchOne("SELECT * FROM enquiries WHERE id = ?", "i", [$enquiry_id]);

if (!$enquiry) {
    header("Location: list.php?error=Enquiry not found");
    exit;
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Schedule Site Visit</h2>
        <a href="../enquiry/view.php?id=<?php echo $enquiry_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Enquiry
        </a>
    </div>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-header">
            <h5>Enquiry: <?php echo htmlspecialchars($enquiry['client_name']); ?></h5>
        </div>
        <div class="card-body">
            <form id="siteVisitForm">
                <input type="hidden" name="enquiry_id" value="<?php echo $enquiry_id; ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="scheduled_date" class="form-label">Scheduled Date *</label>
                            <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="scheduled_time" class="form-label">Scheduled Time</label>
                            <input type="time" id="scheduled_time" name="scheduled_time" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="engineer_name" class="form-label">Engineer Name</label>
                    <input type="text" id="engineer_name" name="engineer_name" class="form-control" placeholder="Enter engineer name">
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Enter visit notes"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Schedule Visit
                    </button>
                    <a href="../enquiry/view.php?id=<?php echo $enquiry_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#siteVisitForm').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'add_site_visit',
            enquiry_id: $('input[name="enquiry_id"]').val(),
            scheduled_date: $('#scheduled_date').val(),
            scheduled_time: $('#scheduled_time').val(),
            engineer_name: $('#engineer_name').val(),
            notes: $('#notes').val()
        };

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    $('#message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                    setTimeout(function() {
                        // Auto redirect to add measurement
                        window.location.href = '../measurements/create.php?enquiry_id=' + response.enquiry_id + '&site_visit_id=' + response.site_visit_id;
                    }, 1000);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Schedule Visit');
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>