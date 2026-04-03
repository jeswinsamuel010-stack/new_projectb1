<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'enquiry', 'create');

$page_title = 'New Enquiry';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>New Enquiry</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-body">
            <form id="enquiryForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Client Name *</label>
                        <input type="text" id="client_name" name="client_name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="project_type">Project Type *</label>
                        <select id="project_type" name="project_type" required>
                            <option value="">Select Type</option>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="industrial">Industrial</option>
                            <option value="institutional">Institutional</option>
                            <option value="renovation">Renovation</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="site_location">Site Location *</label>
                    <textarea id="site_location" name="site_location" rows="3" required></textarea>
                </div>

                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Create Enquiry
                    </button>
                    <a href="list.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#enquiryForm').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'create_enquiry',
            client_name: $('#client_name').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            project_type: $('#project_type').val(),
            site_location: $('#site_location').val()
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
                    $('#enquiryForm')[0].reset();
                    setTimeout(function() {
                        // Auto redirect to schedule site visit
                        window.location.href = '../site_visits/create.php?enquiry_id=' + response.enquiry_id;
                    }, 1000);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Create Enquiry');
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>