<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'measurements', 'edit');

$page_title = 'Edit Measurement';

$id = $_GET['id'] ?? 0;
$enquiry_id = $_GET['enquiry_id'] ?? 0;

if (!$id) {
    header("Location: list.php?error=Invalid measurement");
    exit;
}

$measurement = fetchOne("SELECT * FROM measurements WHERE id = ?", "i", [$id]);

if (!$measurement) {
    header("Location: list.php?error=Measurement not found");
    exit;
}

$enquiry_id = $enquiry_id ?: $measurement['enquiry_id'];

$enquiry = fetchOne("SELECT * FROM enquiries WHERE id = ?", "i", [$enquiry_id]);

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Edit Measurement</h2>
        <a href="list.php?enquiry_id=<?php echo $enquiry_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-header">
            <h5>Enquiry: <?php echo htmlspecialchars($enquiry['client_name'] ?? ''); ?></h5>
        </div>
        <div class="card-body">
            <form id="measurementForm">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="enquiry_id" value="<?php echo $enquiry_id; ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="area_sqft" class="form-label">Area (sq.ft) *</label>
                            <input type="number" id="area_sqft" name="area_sqft" class="form-control" step="0.01" value="<?php echo $measurement['area_sqft']; ?>" placeholder="Enter area in square feet" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="length" class="form-label">Length</label>
                            <input type="number" id="length" name="length" class="form-control" step="0.01" value="<?php echo $measurement['length']; ?>" placeholder="Length">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="width" class="form-label">Width</label>
                            <input type="number" id="width" name="width" class="form-control" step="0.01" value="<?php echo $measurement['width']; ?>" placeholder="Width">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="height" class="form-label">Height</label>
                            <input type="number" id="height" name="height" class="form-control" step="0.01" value="<?php echo $measurement['height']; ?>" placeholder="Height">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="measurement_notes" class="form-label">Notes</label>
                    <textarea id="measurement_notes" name="measurement_notes" class="form-control" rows="4" placeholder="Enter measurement notes"><?php echo htmlspecialchars($measurement['measurement_notes']); ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Update Measurement
                    </button>
                    <a href="list.php?enquiry_id=<?php echo $enquiry_id; ?>" class="btn btn-secondary">
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

        var formData = {
            action: 'update_measurement',
            id: $('input[name="id"]').val(),
            enquiry_id: $('input[name="enquiry_id"]').val(),
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
                        window.location.href = 'list.php?enquiry_id=' + formData.enquiry_id + '&success=' + encodeURIComponent(response.message);
                    }, 1500);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Update Measurement');
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>