<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'quotation', 'create');

$page_title = 'New Quotation';
$error = '';

// Get enquiry ID from URL if provided
$enquiry_id = $_GET['enquiry_id'] ?? 0;
$prefill_enquiry = null;

if ($enquiry_id) {
    $prefill_enquiry = fetchOne("SELECT * FROM enquiries WHERE id = ?", "i", [$enquiry_id]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
    $client_name = sanitize($_POST['client_name'] ?? '');
    $contact_phone = sanitize($_POST['contact_phone'] ?? '');
    $contact_email = sanitize($_POST['contact_email'] ?? '');
    $project_title = sanitize($_POST['project_title'] ?? '');
    $scope_description = sanitize($_POST['scope_description'] ?? '');
    $estimated_amount = floatval($_POST['estimated_amount'] ?? 0);
    $valid_until = $_POST['valid_until'] ?? '';

    // Validate required fields
    $errors = [];
    if (empty($client_name)) $errors[] = 'Client name is required';
    if (empty($contact_phone)) $errors[] = 'Contact phone is required';
    if (empty($project_title)) $errors[] = 'Project title is required';
    if (empty($estimated_amount) || $estimated_amount <= 0) $errors[] = 'Valid estimated amount is required';

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    } else {
        // Generate quotation number
        $year = date('Y');
        $result = fetchOne("SELECT COUNT(*) as count FROM project_quotations WHERE YEAR(created_at) = ?", "i", [$year]);
        $count = ($result['count'] ?? 0) + 1;
        $quotation_number = 'QTN-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Build query based on whether enquiry_id is provided
        $conn = getDB();

        if ($enquiry_id > 0) {
            $stmt = $conn->prepare("
                INSERT INTO project_quotations
                (quotation_number, enquiry_id, client_name, contact_phone, contact_email, project_title, scope_description, estimated_amount, valid_until, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?)
            ");
            $stmt->bind_param("sisssssdsi",
                $quotation_number,
                $enquiry_id,
                $client_name,
                $contact_phone,
                $contact_email,
                $project_title,
                $scope_description,
                $estimated_amount,
                $valid_until,
                $_SESSION['user_id']
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO project_quotations
                (quotation_number, enquiry_id, client_name, contact_phone, contact_email, project_title, scope_description, estimated_amount, valid_until, status, created_by)
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?)
            ");
            $stmt->bind_param("ssssssdsi",
                $quotation_number,
                $client_name,
                $contact_phone,
                $contact_email,
                $project_title,
                $scope_description,
                $estimated_amount,
                $valid_until,
                $_SESSION['user_id']
            );
        }

        if ($stmt->execute()) {
            $quotation_id = $conn->insert_id;
            $stmt->close();

            // Update enquiry status if linked
            if ($enquiry_id > 0) {
                runQuery("UPDATE enquiries SET status = 'quotation_prepared' WHERE id = ?", "i", [$enquiry_id]);
            }

            header("Location: view_quotation.php?id=" . $quotation_id . "&success=Quotation created successfully");
            exit;
        } else {
            $error = $stmt->error;
            $stmt->close();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>New Quotation</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="" id="quotationForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="enquiry_id">Link to Enquiry (Optional)</label>
                        <select id="enquiry_id" name="enquiry_id">
                            <option value="0">-- Create without Enquiry --</option>
                            <?php
                            $enquiries = fetchAll("
                                SELECT id, client_name, phone, email, project_type, site_location
                                FROM enquiries
                                WHERE status IN ('new', 'site_visit_scheduled')
                                ORDER BY created_at DESC
                            ");
                            foreach ($enquiries as $eq):
                            ?>
                            <option value="<?php echo $eq['id']; ?>"
                                data-client="<?php echo htmlspecialchars($eq['client_name']); ?>"
                                data-phone="<?php echo htmlspecialchars($eq['phone']); ?>"
                                data-email="<?php echo htmlspecialchars($eq['email'] ?? ''); ?>"
                                data-project-type="<?php echo htmlspecialchars($eq['project_type']); ?>"
                                data-site="<?php echo htmlspecialchars($eq['site_location']); ?>"
                                <?php echo ($enquiry_id == $eq['id']) ? 'selected' : ''; ?>>
                                #<?php echo $eq['id']; ?> - <?php echo htmlspecialchars($eq['client_name']); ?> (<?php echo htmlspecialchars($eq['project_type']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select an enquiry to auto-fill client details, or create manually</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Client Name *</label>
                        <input type="text" id="client_name" name="client_name" required
                            value="<?php echo $prefill_enquiry ? htmlspecialchars($prefill_enquiry['client_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone *</label>
                        <input type="text" id="contact_phone" name="contact_phone" required
                            value="<?php echo $prefill_enquiry ? htmlspecialchars($prefill_enquiry['phone']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email"
                            value="<?php echo $prefill_enquiry ? htmlspecialchars($prefill_enquiry['email'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="estimated_amount">Estimated Amount (INR) *</label>
                        <input type="number" id="estimated_amount" name="estimated_amount" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="project_title">Project Title *</label>
                        <input type="text" id="project_title" name="project_title" required
                            value="<?php echo $prefill_enquiry ? htmlspecialchars($prefill_enquiry['project_type']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="valid_until">Valid Until</label>
                        <input type="date" id="valid_until" name="valid_until">
                    </div>
                </div>

                <div class="form-group">
                    <label for="scope_description">Scope Description</label>
                    <textarea id="scope_description" name="scope_description" rows="4" placeholder="Describe the scope of work..."></textarea>
                </div>

                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Quotation
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
    // Auto-fill from enquiry selection
    $('#enquiry_id').change(function() {
        var selected = $(this).find('option:selected');
        if (selected.val() && selected.val() !== '0') {
            $('#client_name').val(selected.data('client') || '');
            $('#contact_phone').val(selected.data('phone') || '');
            $('#contact_email').val(selected.data('email') || '');
            $('#project_title').val(selected.data('project-type') || '');
        } else if (selected.val() === '0') {
            // Clear fields when "Create without Enquiry" is selected
            $('#client_name').val('');
            $('#contact_phone').val('');
            $('#contact_email').val('');
            $('#project_title').val('');
        }
    });

    // Trigger change if enquiry_id is pre-selected
    if ($('#enquiry_id').val() && $('#enquiry_id').val() !== '0') {
        $('#enquiry_id').trigger('change');
    }

    // Set default valid date to 30 days from today
    var defaultValidDate = new Date();
    defaultValidDate.setDate(defaultValidDate.getDate() + 30);
    $('#valid_until').val(defaultValidDate.toISOString().split('T')[0]);
});
</script>

<?php require_once '../includes/footer.php'; ?>