<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'site_visits', 'edit');

$page_title = 'Edit Site Visit';

$id = $_GET['id'] ?? 0;
$enquiry_id = $_GET['enquiry_id'] ?? 0;

if (!$id) {
    header("Location: list.php?error=Invalid site visit");
    exit;
}

$visit = fetchOne("SELECT * FROM site_visits WHERE id = ?", "i", [$id]);

if (!$visit) {
    header("Location: list.php?error=Site visit not found");
    exit;
}

$enquiry_id = $enquiry_id ?: $visit['enquiry_id'];

$enquiry = fetchOne("SELECT * FROM enquiries WHERE id = ?", "i", [$enquiry_id]);

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Edit Site Visit</h2>
        <a href="list.php?enquiry_id=<?php echo $enquiry_id; ?>" class="btn btn-secondary">
            Back
        </a>
    </div>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-header">
            <h5>Enquiry: <?php echo htmlspecialchars($enquiry['client_name'] ?? ''); ?></h5>
        </div>

        <div class="card-body">
            <form id="siteVisitForm">

                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="enquiry_id" value="<?php echo $enquiry_id; ?>">

                <div class="row">
                    <div class="col-md-6">
                        <label>Scheduled Date *</label>
                        <input type="date" id="scheduled_date" class="form-control"
                            value="<?php echo $visit['scheduled_date']; ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label>Scheduled Time</label>
                        <input type="time" id="scheduled_time" class="form-control"
                            value="<?php echo $visit['scheduled_time']; ?>">
                    </div>
                </div>

                <div class="mt-3">
                    <label>Engineer Name</label>
                    <input type="text" id="engineer_name" class="form-control"
                        value="<?php echo htmlspecialchars($visit['engineer_name']); ?>">
                </div>

                <div class="mt-3">
                    <label>Notes</label>
                    <textarea id="visit_notes" class="form-control" rows="4"><?php echo htmlspecialchars($visit['visit_notes'] ?? ''); ?></textarea>
                </div>

                <div class="mt-3 form-check">
                    <input type="checkbox" id="visit_completed" class="form-check-input"
                        <?php echo ($visit['visit_completed'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label">Completed</label>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Update</button>
                    <a href="list.php?enquiry_id=<?php echo $enquiry_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    $('#siteVisitForm').submit(function(e) {
        e.preventDefault();

        var formData = {
            action: 'update_site_visit',
            id: $('input[name="id"]').val(),
            enquiry_id: $('input[name="enquiry_id"]').val(),
            scheduled_date: $('#scheduled_date').val(),
            scheduled_time: $('#scheduled_time').val(),
            engineer_name: $('#engineer_name').val(),
            visit_notes: $('#visit_notes').val(),
            visit_completed: $('#visit_completed').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',

            beforeSend: function() {
                $('#submitBtn').prop('disabled', true).text('Saving...');
            },

            success: function(res) {
                if (res.success) {
                    $('#message-container').html('<div class="alert alert-success">'+res.message+'</div>');
                    setTimeout(() => {
                        window.location.href = 'list.php?enquiry_id=' + formData.enquiry_id;
                    }, 1000);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">'+res.message+'</div>');
                }
            },

            error: function() {
                $('#message-container').html('<div class="alert alert-danger">Error occurred</div>');
            },

            complete: function() {
                $('#submitBtn').prop('disabled', false).text('Update');
            }
        });

    });

});
</script>

<?php require_once '../includes/footer.php'; ?>