<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'quotation', 'edit');

$page_title = 'Edit Quotation';
$error = '';

$id = $_GET['id'] ?? 0;

if (!$id) {
    header("Location: list.php?error=Invalid quotation");
    exit;
}

$quotation = fetchOne("SELECT * FROM project_quotations WHERE id = ?", "i", [$id]);

if (!$quotation) {
    header("Location: list.php?error=Quotation not found");
    exit;
}

// Check if quotation can be edited (only Draft or Sent)
if (in_array($quotation['status'], ['Approved', 'Converted'])) {
    header("Location: view_quotation.php?id=" . $id . "&error=Cannot modify an approved or converted quotation");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_name = sanitize($_POST['client_name'] ?? '');
    $contact_phone = sanitize($_POST['contact_phone'] ?? '');
    $contact_email = sanitize($_POST['contact_email'] ?? '');
    $project_title = sanitize($_POST['project_title'] ?? '');
    $scope_description = sanitize($_POST['scope_description'] ?? '');
    $estimated_amount = floatval($_POST['estimated_amount'] ?? 0);
    $valid_until = $_POST['valid_until'] ?? '';

    if (empty($client_name) || empty($contact_phone) || empty($project_title) || empty($estimated_amount)) {
        $error = 'Please fill in all required fields';
    } else {
        $result = runQuery(
            "UPDATE project_quotations SET client_name = ?, contact_phone = ?, contact_email = ?, project_title = ?, scope_description = ?, estimated_amount = ?, valid_until = ? WHERE id = ?",
            "sssssdsi",
            [$client_name, $contact_phone, $contact_email, $project_title, $scope_description, $estimated_amount, $valid_until, $id]
        );

        if ($result['success']) {
            header("Location: view_quotation.php?id=" . $id . "&success=Quotation updated successfully");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Edit Quotation</h2>
        <a href="view_quotation.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Details
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Quotation Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($quotation['quotation_number']); ?>" readonly class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" value="<?php echo $quotation['status']; ?>" readonly class="form-control">
                        <small class="text-muted">Status is managed through the workflow actions</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Client Name *</label>
                        <input type="text" id="client_name" name="client_name" required value="<?php echo htmlspecialchars($quotation['client_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone *</label>
                        <input type="text" id="contact_phone" name="contact_phone" required value="<?php echo htmlspecialchars($quotation['contact_phone']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($quotation['contact_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="estimated_amount">Estimated Amount (INR) *</label>
                        <input type="number" id="estimated_amount" name="estimated_amount" step="0.01" min="0" required value="<?php echo $quotation['estimated_amount']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="project_title">Project Title *</label>
                        <input type="text" id="project_title" name="project_title" required value="<?php echo htmlspecialchars($quotation['project_title']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="valid_until">Valid Until</label>
                        <input type="date" id="valid_until" name="valid_until" value="<?php echo $quotation['valid_until']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="scope_description">Scope Description</label>
                    <textarea id="scope_description" name="scope_description" rows="4" placeholder="Describe the scope of work..."><?php echo htmlspecialchars($quotation['scope_description'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Quotation
                    </button>
                    <a href="view_quotation.php?id=<?php echo $id; ?>" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>