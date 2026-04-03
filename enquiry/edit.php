<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'enquiry', 'edit');

$page_title = 'Edit Enquiry';

$enquiry_id = $_GET['id'] ?? 0;

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
        <h2>Edit Enquiry</h2>
        <div>
            <?php if ($_SESSION['role'] == 'admin' && in_array($enquiry['status'], ['new', 'site_visit_scheduled'])): ?>
            <a href="../quotations/add_quotation.php?enquiry_id=<?php echo $enquiry_id; ?>" class="btn btn-primary">
                <i class="fas fa-file-invoice"></i> Create Quotation
            </a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-body">
            <form id="enquiryForm">
                <input type="hidden" name="id" value="<?php echo $enquiry_id; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Client Name *</label>
                        <input type="text" id="client_name" name="client_name" value="<?php echo htmlspecialchars($enquiry['client_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($enquiry['phone']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($enquiry['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project_type">Project Type *</label>
                        <select id="project_type" name="project_type" required>
                            <option value="">Select Type</option>
                            <option value="residential" <?php echo $enquiry['project_type'] == 'residential' ? 'selected' : ''; ?>>Residential</option>
                            <option value="commercial" <?php echo $enquiry['project_type'] == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                            <option value="industrial" <?php echo $enquiry['project_type'] == 'industrial' ? 'selected' : ''; ?>>Industrial</option>
                            <option value="institutional" <?php echo $enquiry['project_type'] == 'institutional' ? 'selected' : ''; ?>>Institutional</option>
                            <option value="renovation" <?php echo $enquiry['project_type'] == 'renovation' ? 'selected' : ''; ?>>Renovation</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="site_location">Site Location *</label>
                    <textarea id="site_location" name="site_location" rows="3" required><?php echo htmlspecialchars($enquiry['site_location']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="new" <?php echo $enquiry['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="site_visit_scheduled" <?php echo $enquiry['status'] == 'site_visit_scheduled' ? 'selected' : ''; ?>>Site Visit Scheduled</option>
                        <option value="quotation_prepared" <?php echo $enquiry['status'] == 'quotation_prepared' ? 'selected' : ''; ?>>Quotation Prepared</option>
                        <option value="advance_paid" <?php echo $enquiry['status'] == 'advance_paid' ? 'selected' : ''; ?>>Advance Paid</option>
                        <option value="project_started" <?php echo $enquiry['status'] == 'project_started' ? 'selected' : ''; ?>>Project Started</option>
                        <option value="in_progress" <?php echo $enquiry['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $enquiry['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Update Enquiry
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
            action: 'update_enquiry',
            id: $('#enquiryForm input[name="id"]').val(),
            client_name: $('#client_name').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            project_type: $('#project_type').val(),
            site_location: $('#site_location').val(),
            status: $('#status').val()
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
                        window.location.href = 'list.php?success=' + encodeURIComponent(response.message);
                    }, 1500);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Update Enquiry');
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>