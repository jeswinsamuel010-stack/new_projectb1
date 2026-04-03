<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'quotation', 'view');

$page_title = 'Quotation Details';

$id = $_GET['id'] ?? 0;

if (!$id) {
    header("Location: list.php?error=Invalid quotation");
    exit;
}

$quotation = fetchOne("
    SELECT q.*, e.client_name as enquiry_client, e.phone as enquiry_phone, e.email as enquiry_email, e.project_type, e.site_location,
           u.full_name as approver_name
    FROM project_quotations q
    LEFT JOIN enquiries e ON q.enquiry_id = e.id
    LEFT JOIN users u ON q.approved_by = u.id
    WHERE q.id = ?
", "i", [$id]);

if (!$quotation) {
    header("Location: list.php?error=Quotation not found");
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Quotation Details</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div id="message-container"></div>

    <!-- Status Timeline -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>Quotation Status</h5>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <?php
                $statuses = ['Draft', 'Sent', 'Approved', 'Converted'];
                $current_status = $quotation['status'];

                // For rejected, show different flow
                if ($current_status == 'Rejected') {
                    $statuses = ['Draft', 'Sent', 'Rejected'];
                }

                $current_index = array_search($current_status, $statuses);
                if ($current_status == 'Rejected') {
                    $current_index = 2;
                }
                ?>

                <?php foreach ($statuses as $index => $status): ?>
                    <div class="text-center">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                             style="width: 40px; height: 40px; background: <?php echo ($index <= $current_index) ? '#28a745' : '#6c757d'; ?>; color: white;">
                            <?php if ($index < $current_index): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php echo $index + 1; ?>
                            <?php endif; ?>
                        </div>
                        <p class="mb-0 mt-2 small <?php echo ($index == $current_index) ? 'fw-bold' : 'text-muted'; ?>"><?php echo $status; ?></p>
                    </div>
                    <?php if ($index < count($statuses) - 1): ?>
                        <div class="flex-grow-1 mx-3" style="height: 2px; background: <?php echo ($index < $current_index) ? '#28a745' : '#6c757d'; ?>;"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Status Badge -->
            <?php
            $statusClass = [
                'Draft' => 'bg-secondary',
                'Sent' => 'bg-info',
                'Approved' => 'bg-success',
                'Rejected' => 'bg-danger',
                'Converted' => 'bg-primary'
            ];
            ?>
            <div class="text-center mt-3">
                <span class="badge <?php echo $statusClass[$current_status] ?? 'bg-secondary'; ?> p-2">
                    <?php echo $current_status; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Quotation Info Card -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>Quotation Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Quotation Number:</strong> <?php echo htmlspecialchars($quotation['quotation_number']); ?></p>
                    <p><strong>Client Name:</strong> <?php echo htmlspecialchars($quotation['client_name']); ?></p>
                    <p><strong>Contact Phone:</strong> <?php echo htmlspecialchars($quotation['contact_phone']); ?></p>
                    <p><strong>Contact Email:</strong> <?php echo htmlspecialchars($quotation['contact_email'] ?? '-'); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Project Title:</strong> <?php echo htmlspecialchars($quotation['project_title']); ?></p>
                    <p><strong>Estimated Amount:</strong> <span class="text-success fw-bold">INR <?php echo number_format($quotation['estimated_amount'], 2); ?></span></p>
                    <p><strong>Valid Until:</strong> <?php echo $quotation['valid_until'] ? date('d-m-Y', strtotime($quotation['valid_until'])) : '-'; ?></p>
                    <p><strong>Created Date:</strong> <?php echo date('d-m-Y', strtotime($quotation['created_at'])); ?></p>
                </div>
            </div>
            <?php if ($quotation['scope_description']): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <p><strong>Scope Description:</strong></p>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($quotation['scope_description'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sent Info Card (if sent) -->
    <?php if ($quotation['status'] == 'Sent' || $quotation['sent_to_client_at']): ?>
    <div class="card mb-3 border-info">
        <div class="card-header bg-info text-black">
            <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Sent to Client</h5>
        </div>
        <div class="card-body">
            <p>This quotation has been sent to the client.</p>
            <?php if ($quotation['sent_to_client_at']): ?>
            <p><strong>Sent on:</strong> <?php echo date('d-m-Y h:i A', strtotime($quotation['sent_to_client_at'])); ?></p>
            <?php endif; ?>
            <p class="mb-0"><strong>Next Step:</strong> Wait for client response or generate a new PDF if needed.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Approval Info -->
    <?php if ($quotation['status'] == 'Approved' || $quotation['status'] == 'Rejected'): ?>
    <div class="card mb-3 <?php echo $quotation['status'] == 'Approved' ? 'border-success' : 'border-danger'; ?>">
        <div class="card-header <?php echo $quotation['status'] == 'Approved' ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
            <h5 class="mb-0">
                <i class="fas <?php echo $quotation['status'] == 'Approved' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                Quotation <?php echo $quotation['status']; ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Date:</strong> <?php echo $quotation['approved_date'] ? date('d-m-Y h:i A', strtotime($quotation['approved_date'])) : '-'; ?></p>
                    <p><strong>By:</strong> <?php echo htmlspecialchars($quotation['approver_name'] ?? 'Admin'); ?></p>
                </div>
                <?php if ($quotation['approval_notes']): ?>
                <div class="col-md-6">
                    <p><strong>Notes:</strong></p>
                    <p><?php echo htmlspecialchars($quotation['approval_notes']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enquiry Reference Card -->
    <?php if ($quotation['enquiry_id']): ?>
    <div class="card mb-3">
        <div class="card-header">
            <h5>Enquiry Reference</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Enquiry ID:</strong> #<?php echo $quotation['enquiry_id']; ?></p>
                    <p><strong>Client:</strong> <?php echo htmlspecialchars($quotation['enquiry_client'] ?? '-'); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Project Type:</strong> <?php echo htmlspecialchars($quotation['project_type'] ?? '-'); ?></p>
                    <p><strong>Site Location:</strong> <?php echo htmlspecialchars($quotation['site_location'] ?? '-'); ?></p>
                </div>
            </div>
            <div class="mt-2">
                <a href="../enquiry/view.php?id=<?php echo $quotation['enquiry_id']; ?>" class="btn btn-sm btn-info">
                    <i class="fas fa-external-link-alt"></i> View Enquiry
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions Card - Show based on permissions -->
    <?php if (canAccess('quotation', 'edit') || canAccess('quotation', 'approve')): ?>
    <div class="card mb-3">
        <div class="card-header">
            <h5>Actions</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <!-- Generate PDF -->
                <?php if ($_SESSION['role'] == 'admin' && in_array($quotation['status'], ['Draft', 'Sent'])): ?>
                <div class="col-md-4">
                    <div class="border p-3 rounded">
                        <h6><i class="fas fa-file-pdf"></i> Generate PDF</h6>
                        <p class="small text-muted">Download quotation as PDF</p>
                        <a href="generate_pdf.php?id=<?php echo $id; ?>" class="btn btn-sm btn-danger" target="_blank">
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                        <!-- <button class="btn btn-sm btn-secondary" onclick="previewPDF(<?php echo $id; ?>)">
                            <i class="fas fa-eye"></i> Preview
                        </button> -->
                    </div>
                </div>
                <?php endif; ?>

                <!-- Send to Client -->
                <?php if ($_SESSION['role'] == 'admin' && $quotation['status'] == 'Draft'): ?>
                <div class="col-md-4">
                    <div class="border p-3 rounded">
                        <h6><i class="fas fa-paper-plane"></i> Send to Client</h6>
                        <p class="small text-muted">Mark as sent to client</p>
                        <button class="btn btn-sm btn-primary" onclick="sendToClient(<?php echo $id; ?>)">
                            <i class="fas fa-check"></i> Mark as Sent
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Approve Quotation -->
                <?php if ($quotation['status'] == 'Sent'): ?>
                <div class="col-md-4">
                    <div class="border border-success p-3 rounded">
                        <h6><i class="fas fa-check-circle text-success"></i> Approve Quotation</h6>
                        <p class="small text-muted">Approve and create project</p>
                        <button class="btn btn-sm btn-success" onclick="showApprovalModal('approve')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reject Quotation -->
                <?php if ($quotation['status'] == 'Sent'): ?>
                <div class="col-md-4">
                    <div class="border border-danger p-3 rounded">
                        <h6><i class="fas fa-times-circle text-danger"></i> Reject Quotation</h6>
                        <p class="small text-muted">Reject this quotation</p>
                        <button class="btn btn-sm btn-danger" onclick="showApprovalModal('reject')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Edit Quotation -->
                <?php if (in_array($quotation['status'], ['Draft', 'Sent'])): ?>
                <div class="col-md-4">
                    <div class="border p-3 rounded">
                        <h6><i class="fas fa-edit"></i> Edit Quotation</h6>
                        <p class="small text-muted">Modify quotation details</p>
                        <a href="edit_quotation.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Convert to Project -->
                <?php if ($quotation['status'] == 'Approved'): ?>
                <?php
                // Check if project already exists for this quotation
                $existing_project = null;
                if ($quotation['enquiry_id']) {
                    $existing_project = fetchOne("SELECT id FROM projects WHERE enquiry_id = ?", "i", [$quotation['enquiry_id']]);
                }
                if (!$existing_project):
                ?>
                <div class="col-md-4">
                    <div class="border border-success p-3 rounded">
                        <h6><i class="fas fa-rocket text-success"></i> Create Project</h6>
                        <p class="small text-muted">Convert approved quotation to project</p>
                        <button class="btn btn-sm btn-success" onclick="convertToProject(<?php echo $id; ?>)">
                            <i class="fas fa-plus"></i> Create Project
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-md-4">
                    <div class="border p-3 rounded">
                        <h6><i class="fas fa-building"></i> Project Exists</h6>
                        <p class="small text-muted">A project already exists</p>
                        <a href="../projects/view.php?id=<?php echo $existing_project['id']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View Project
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Delete Quotation -->
                <?php if (in_array($quotation['status'], ['Draft', 'Sent', 'Rejected'])): ?>
                <div class="col-md-4">
                    <div class="border p-3 rounded">
                        <h6><i class="fas fa-trash text-danger"></i> Delete Quotation</h6>
                        <p class="small text-muted">Remove permanently</p>
                        <button class="btn btn-sm btn-danger" onclick="deleteQuotation(<?php echo $id; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>


<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle">Approve Quotation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approvalAction">
                <div class="mb-3">
                    <label for="approvalNotes" class="form-label">Notes (Optional)</label>
                    <textarea class="form-control" id="approvalNotes" rows="3" placeholder="Add any notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitApproval">Submit</button>
            </div>
        </div>
    </div>
</div>

<script>
var approvalModal;

$(document).ready(function() {
    approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
});

function showApprovalModal(action) {
    $('#approvalAction').val(action);
    if (action === 'approve') {
        $('#approvalModalTitle').text('Approve Quotation');
        $('#submitApproval').removeClass('btn-danger').addClass('btn-success').text('Approve');
    } else {
        $('#approvalModalTitle').text('Reject Quotation');
        $('#submitApproval').removeClass('btn-success').addClass('btn-danger').text('Reject');
    }
    $('#approvalNotes').val('');
    approvalModal.show();
}

$('#submitApproval').click(function() {
    var action = $('#approvalAction').val();
    var notes = $('#approvalNotes').val();

    $.ajax({
        url: 'api.php',
        type: 'POST',
        data: {
            action: action === 'approve' ? 'approve_quotation' : 'reject_quotation',
            id: <?php echo $id; ?>,
            approval_notes: notes
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                approvalModal.hide();
                $('#message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
        }
    });
});

function sendToClient(id) {
    if (confirm('Generate a public link to share this quotation with the client?')) {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: { action: 'send_to_client', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred.</div>');
            }
        });
    }
}

function convertToProject(quotationId) {
    if (confirm('Create a project from this approved quotation?')) {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: { action: 'convert_to_project', quotation_id: quotationId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                    setTimeout(function() {
                        window.location.href = '../milestones/list.php?project_id=' + response.project_id;
                    }, 1500);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred.</div>');
            }
        });
    }
}

function deleteQuotation(quotationId) {
    if (confirm('Are you sure you want to delete this quotation? This cannot be undone.')) {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: { action: 'delete_quotation', id: quotationId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = 'list.php?success=' + encodeURIComponent(response.message);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred.</div>');
            }
        });
    }
}

function previewPDF(id) {
    // Open PDF preview in new window
    window.open('preview_pdf.php?id=' + id, '_blank', 'width=800,height=600');
}

function copyLink() {
    // No longer needed - kept for backward compatibility
    alert('Please use the PDF download button to share with clients.');
}
</script>

<?php require_once '../includes/footer.php'; ?>