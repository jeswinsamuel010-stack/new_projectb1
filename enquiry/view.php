<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'enquiry', 'view');

$page_title = 'Enquiry Details';

// Get enquiry ID
$enquiry_id = $_GET['id'] ?? 0;

if (!$enquiry_id) {
    header("Location: list.php?error=Invalid enquiry");
    exit;
}

// Get enquiry details
$enquiry = fetchOne("SELECT * FROM enquiries WHERE id = ?", "i", [$enquiry_id]);

if (!$enquiry) {
    header("Location: list.php?error=Enquiry not found");
    exit;
}

// Get all related data (read-only view)
$site_visits = fetchAll("SELECT * FROM site_visits WHERE enquiry_id = ? ORDER BY scheduled_date DESC", "i", [$enquiry_id]);
$quotations = fetchAll("SELECT * FROM project_quotations WHERE enquiry_id = ? ORDER BY created_at DESC", "i", [$enquiry_id]);
$measurements = fetchAll("SELECT m.*, sv.scheduled_date as visit_date, sv.engineer_name
    FROM measurements m
    LEFT JOIN site_visits sv ON m.site_visit_id = sv.id
    WHERE m.enquiry_id = ?
    ORDER BY m.created_at DESC", "i", [$enquiry_id]);
$payments = fetchAll("SELECT * FROM payments WHERE enquiry_id = ? ORDER BY payment_date DESC", "i", [$enquiry_id]);

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Enquiry Details</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Enquiry Info Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-user text-primary me-2"></i>Client Information</h5>
            <span class="badge bg-<?php echo $enquiry['status'] == 'new' ? 'primary' : ($enquiry['status'] == 'site_visit_scheduled' ? 'info' : ($enquiry['status'] == 'quotation_prepared' ? 'warning' : ($enquiry['status'] == 'advance_paid' ? 'success' : 'secondary'))); ?>"><?php echo str_replace('_', ' ', ucfirst($enquiry['status'])); ?></span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <i class="fas fa-user text-primary me-2"></i>
                        <strong>Client Name:</strong>
                        <span class="text-muted"><?php echo htmlspecialchars($enquiry['client_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone text-primary me-2"></i>
                        <strong>Phone:</strong>
                        <span class="text-muted"><?php echo htmlspecialchars($enquiry['phone']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope text-primary me-2"></i>
                        <strong>Email:</strong>
                        <span class="text-muted"><?php echo htmlspecialchars($enquiry['email'] ?? '-'); ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <i class="fas fa-building text-primary me-2"></i>
                        <strong>Project Type:</strong>
                        <span class="text-muted"><?php echo ucfirst(htmlspecialchars($enquiry['project_type'])); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <strong>Site Location:</strong>
                        <span class="text-muted"><?php echo htmlspecialchars($enquiry['site_location']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        <strong>Created:</strong>
                        <span class="text-muted"><?php echo date('d-m-Y', strtotime($enquiry['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Read-Only Data Sections -->
    <div class="row g-4">
        <!-- Site Visits Timeline -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-calendar-check text-primary me-2"></i>Site Visits</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($site_visits)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-calendar-times fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">No site visits scheduled</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($site_visits as $index => $visit): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?php echo $visit['visit_completed'] ? 'completed' : 'pending'; ?>">
                                        <i class="fas <?php echo $visit['visit_completed'] ? 'fa-check' : 'fa-clock'; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo date('d M Y', strtotime($visit['scheduled_date'])); ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    <i class="fas fa-clock me-1"></i><?php echo $visit['scheduled_time'] ?? 'Time not set'; ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($visit['engineer_name'] ?? 'Not assigned'); ?>
                                                </p>
                                                <?php if ($visit['visit_notes']): ?>
                                                    <p class="mb-0 small text-secondary"><?php echo htmlspecialchars($visit['visit_notes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-<?php echo $visit['visit_completed'] ? 'success' : 'warning'; ?>">
                                                <?php echo $visit['visit_completed'] ? 'Completed' : 'Pending'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Measurements -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-ruler-combined text-info me-2"></i>Measurements</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($measurements)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-ruler fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">No measurements recorded</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Room/Area</th>
                                        <th>Dimensions</th>
                                        <th>Area (sq.ft)</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($measurements as $m): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars($m['room_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <small>
                                                    <?php
                                                    $dims = [];
                                                    if ($m['length']) $dims[] = 'L: ' . $m['length'];
                                                    if ($m['width']) $dims[] = 'W: ' . $m['width'];
                                                    if ($m['height']) $dims[] = 'H: ' . $m['height'];
                                                    echo implode(', ', $dims) ?: '-';
                                                    ?>
                                                </small>
                                            </td>
                                            <td><span class="badge bg-info text-white"><?php echo number_format($m['area_sqft'], 2); ?></span></td>
                                            <td><small class="text-muted"><?php echo $m['visit_date'] ? date('d M Y', strtotime($m['visit_date'])) : '-'; ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quotations -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="fas fa-file-invoice-dollar text-warning me-2"></i>Quotations</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($quotations)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-file-invoice fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">No quotations prepared</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Quote #</th>
                                        <th>Amount</th>
                                        <th>Valid Until</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quotations as $quote): ?>
                                        <tr>
                                            <td class="fw-medium">QT-<?php echo str_pad($quote['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><span class="badge bg-success">INR <?php echo number_format($quote['estimated_amount'], 2); ?></span></td>
                                            <td><small><?php echo $quote['valid_until'] ? date('d M Y', strtotime($quote['valid_until'])) : '-'; ?></small></td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                $status_text = 'Draft';
                                                if (strtotime($quote['valid_until']) < time()) {
                                                    $status_class = 'danger';
                                                    $status_text = 'Expired';
                                                } elseif ($quote['status'] == 'sent') {
                                                    $status_class = 'info';
                                                    $status_text = 'Sent';
                                                } elseif ($quote['status'] == 'approved') {
                                                    $status_class = 'success';
                                                    $status_text = 'Approved';
                                                } elseif ($quote['status'] == 'rejected') {
                                                    $status_class = 'danger';
                                                    $status_text = 'Rejected';
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<style>
.timeline {
    padding: 1rem;
}
.timeline-item {
    display: flex;
    gap: 1rem;
    padding-bottom: 1.5rem;
    position: relative;
}
.timeline-item:last-child {
    padding-bottom: 0;
}
.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 32px;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}
.timeline-marker {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 12px;
}
.timeline-marker.pending {
    background: #fff3cd;
    color: #856404;
    border: 2px solid #ffc107;
}
.timeline-marker.completed {
    background: #d1e7dd;
    color: #0f5132;
    border: 2px solid #198754;
}
.timeline-content {
    flex: 1;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px;
}
</style>

<?php require_once '../includes/footer.php'; ?>