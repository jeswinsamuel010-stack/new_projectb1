<?php
require_once '../config/db.php';

// Get token from URL
$token = $_GET['q'] ?? '';

if (empty($token)) {
    die('<html><head><title>Error</title></head><body><h1>Invalid Link</h1><p>No quotation reference provided.</p></body></html>');
}

// Get quotation by token
$quotation = fetchOne("SELECT * FROM project_quotations WHERE public_token = ?", "s", [$token]);

if (!$quotation) {
    die('<html><head><title>Error</title></head><body><h1>Not Found</h1><p>This quotation link is invalid or has expired.</p></body></html>');
}

$page_title = 'Quotation - ' . htmlspecialchars($quotation['quotation_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .quotation-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .quotation-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .amount-box {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .amount-box .amount {
            font-size: 32px;
            font-weight: 700;
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-info h2 {
            color: #1e3c72;
            margin-bottom: 5px;
        }
        .company-info p {
            color: #6c757d;
            margin: 0;
        }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        @media print {
            .print-btn { display: none; }
            body { background: white; }
            .quotation-card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="quotation-header">
        <div class="container">
            <div class="company-info">
                <h2><i class="fas fa-hard-hat"></i> Construction ERP</h2>
                <p>Professional Construction Solutions</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="quotation-card">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h4 class="text-muted mb-1">QUOTATION</h4>
                    <h2><?php echo htmlspecialchars($quotation['quotation_number']); ?></h2>
                </div>
                <div>
                    <?php
                    $status_config = [
                        'Draft' => ['bg-secondary', 'Draft'],
                        'Sent' => ['bg-info', 'Sent to Client'],
                        'Approved' => ['bg-success', 'Approved'],
                        'Rejected' => ['bg-danger', 'Rejected'],
                        'Converted' => ['bg-primary', 'Project Created']
                    ];
                    $status = $status_config[$quotation['status']] ?? ['bg-secondary', $quotation['status']];
                    ?>
                    <span class="badge <?php echo $status[0]; ?> status-badge"><?php echo $status[1]; ?></span>
                </div>
            </div>

            <!-- Dates -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($quotation['created_at'])); ?></p>
                    <p class="mb-0"><strong>Valid Until:</strong> <?php echo $quotation['valid_until'] ? date('d-m-Y', strtotime($quotation['valid_until'])) : 'Not specified'; ?></p>
                </div>
                <?php if ($quotation['sent_to_client_at']): ?>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0"><strong>Shared:</strong> <?php echo date('d-m-Y', strtotime($quotation['sent_to_client_at'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <hr>

            <!-- Client Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="text-muted mb-3">Client Details</h5>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($quotation['client_name']); ?></p>
                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($quotation['contact_phone']); ?></p>
                    <p class="mb-0"><strong>Email:</strong> <?php echo htmlspecialchars($quotation['contact_email'] ?? '-'); ?></p>
                </div>
                <div class="col-md-6">
                    <h5 class="text-muted mb-3">Project</h5>
                    <p class="mb-0"><strong>Title:</strong> <?php echo htmlspecialchars($quotation['project_title']); ?></p>
                </div>
            </div>

            <!-- Amount -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="amount-box">
                        <div class="small">Estimated Amount</div>
                        <div class="amount">INR <?php echo number_format($quotation['estimated_amount'], 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Scope -->
            <?php if ($quotation['scope_description']): ?>
            <div class="mb-4">
                <h5 class="text-muted mb-3">Scope of Work</h5>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($quotation['scope_description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Approval Info -->
            <?php if ($quotation['status'] == 'Approved' || $quotation['status'] == 'Rejected'): ?>
            <div class="alert <?php echo $quotation['status'] == 'Approved' ? 'alert-success' : 'alert-danger'; ?>">
                <h5 class="alert-heading">
                    <i class="fas <?php echo $quotation['status'] == 'Approved' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    Quotation <?php echo $quotation['status']; ?>
                </h5>
                <?php if ($quotation['approved_date']): ?>
                <p class="mb-0"><small>Date: <?php echo date('d-m-Y h:i A', strtotime($quotation['approved_date'])); ?></small></p>
                <?php endif; ?>
                <?php if ($quotation['approval_notes']): ?>
                <p class="mb-0 mt-2"><strong>Notes:</strong> <?php echo htmlspecialchars($quotation['approval_notes']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="text-center text-muted mt-5 pt-3 border-top">
                <p class="mb-0">Thank you for your interest in our services!</p>
                <p class="mb-0 small">Powered by Construction ERP</p>
            </div>
        </div>
    </div>

    <!-- Print Button -->
    <button class="btn btn-primary print-btn" onclick="window.print()" title="Print Quotation">
        <i class="fas fa-print fa-lg"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>