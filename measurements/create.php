<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'measurements', 'create');

$page_title = 'Add Measurement';

$enquiry_id = $_GET['enquiry_id'] ?? 0;
$site_visit_id = $_GET['site_visit_id'] ?? 0;

if (!$enquiry_id) {
    header("Location: list.php?error=Invalid enquiry");
    exit;
}

$enquiry = fetchOne("SELECT * FROM enquiries WHERE id = ?", "i", [$enquiry_id]);

if (!$enquiry) {
    header("Location: list.php?error=Enquiry not found");
    exit;
}

// ✅ Fetch site visits for dropdown
$site_visits = fetchAll(
    "SELECT id, scheduled_date, engineer_name 
     FROM site_visits 
     WHERE enquiry_id = ? 
     ORDER BY scheduled_date DESC",
    "i",
    [$enquiry_id]
);


require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Add Measurement</h2>
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
            <form id="measurementForm">
                <input type="hidden" name="enquiry_id" value="<?php echo $enquiry_id; ?>">
                 <!-- ✅ SITE VISIT DROPDOWN -->
                <div class="mb-3">
                    <label class="form-label">Select Site Visit *</label>
                    <select id="site_visit_id" class="form-control" required>
                        <option value="">-- Select Visit --</option>
                        <?php foreach ($site_visits as $visit): ?>
                            <option value="<?php echo $visit['id']; ?>" <?php echo ($site_visit_id == $visit['id']) ? 'selected' : ''; ?>>
                                Visit #<?php echo $visit['id']; ?> -
                                <?php echo $visit['scheduled_date']; ?>
                                (<?php echo htmlspecialchars($visit['engineer_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="area_sqft" class="form-label">Area (sq.ft) *</label>
                            <input type="number" id="area_sqft" name="area_sqft" class="form-control" step="0.01" placeholder="Enter area in square feet" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="length" class="form-label">Length</label>
                            <input type="number" id="length" name="length" class="form-control" step="0.01" placeholder="Length">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="width" class="form-label">Width</label>
                            <input type="number" id="width" name="width" class="form-control" step="0.01" placeholder="Width">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="height" class="form-label">Height</label>
                            <input type="number" id="height" name="height" class="form-control" step="0.01" placeholder="Height">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="measurement_notes" class="form-label">Notes</label>
                    <textarea id="measurement_notes" name="measurement_notes" class="form-control" rows="4" placeholder="Enter measurement notes"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Save Measurement
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
    $('#measurementForm').on('submit', function(e) {
        e.preventDefault();

        var siteVisitId = $('#site_visit_id').val();

        // ✅ validation
        if (!siteVisitId) {
            $('#message-container').html('<div class="alert alert-danger">Please select a site visit</div>');
            return;
        }

        var formData = {
            action: 'add_measurement',
            enquiry_id: $('input[name="enquiry_id"]').val(),
            site_visit_id: siteVisitId,
            area_sqft: $('#area_sqft').val(),
            length: $('#length').val(),
            width: $('#width').val(),
            height: $('#height').val(),
            measurement_notes: $('#measurement_notes').val()
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
                        // Auto redirect to create quotation
                        window.location.href = '../quotations/add_quotation.php?enquiry_id=' + response.enquiry_id + '&measurement_id=' + response.measurement_id;
                    }, 1000);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Save Measurement');
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>