<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'payments', 'list');

$page_title = 'Payments';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-rupee-sign"></i> Payments</h2>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php
        $totals = fetchOne("SELECT
            SUM(b.total_amount) as total_billed,
            SUM(b.paid_amount) as total_paid,
            SUM(b.total_amount - b.paid_amount) as total_pending
            FROM buy_bills b WHERE b.status != 'cancelled'");
        ?>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="fas fa-file-invoice"></i></div>
                <div class="stat-info">
                    <h4>Total Billed</h4>
                    <p class="stat-value"><?php echo number_format($totals['total_billed'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h4>Total Paid</h4>
                    <p class="stat-value"><?php echo number_format($totals['total_paid'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h4>Pending</h4>
                    <p class="stat-value"><?php echo number_format($totals['total_pending'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="paymentTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Payment No.</th>
                        <th>Bill Number</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<style>
.stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}
.bg-primary { background: #007bff; }
.bg-success { background: #28a745; }
.bg-warning { background: #ffc107; color: #000; }
.stat-info h4 { margin: 0; font-size: 14px; color: #6c757d; }
.stat-value { margin: 5px 0 0; font-size: 24px; font-weight: bold; }
</style>

<script>
let paymentTable;

$(document).ready(function() {
    paymentTable = $('#paymentTable').DataTable({
        ajax: {
            url: '../api/buy_side.php?module=payment',
            type: 'POST',
            data: function(d) { d.action = 'list'; }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }
        ]
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>