<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'inventory', 'list');

$page_title = 'Inventory - Stock Status';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-warehouse"></i> Inventory</h2>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Stock is updated through GRN (Buy Side) and Issue (User Side) only.
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <?php
        $stats = fetchOne("SELECT
            COUNT(*) as total_items,
            SUM(current_stock) as total_stock,
            SUM(total_received) as total_in,
            SUM(total_issued) as total_out
            FROM buy_inventory");

        $low_stock = fetchOne("SELECT COUNT(*) as c FROM buy_inventory WHERE current_stock <= min_stock_level")['c'];
        $out_stock = fetchOne("SELECT COUNT(*) as c FROM buy_inventory WHERE current_stock <= 0")['c'];
        ?>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="fas fa-boxes"></i></div>
                <div class="stat-info">
                    <h4>Total Items</h4>
                    <p class="stat-value"><?php echo $stats['total_items']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success"><i class="fas fa-arrow-down"></i></div>
                <div class="stat-info">
                    <h4>Total Received</h4>
                    <p class="stat-value"><?php echo number_format($stats['total_in'] ?? 0, 0); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-info">
                    <h4>Total Issued</h4>
                    <p class="stat-value"><?php echo number_format($stats['total_issued'] ?? 0, 0); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h4>Low Stock Items</h4>
                    <p class="stat-value"><?php echo $low_stock; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="inventoryTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Unit</th>
                        <th>Total Received</th>
                        <th>Total Issued</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<style>
.alert-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
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
.bg-danger { background: #dc3545; }
.stat-info h4 { margin: 0; font-size: 14px; color: #6c757d; }
.stat-value { margin: 5px 0 0; font-size: 24px; font-weight: bold; }
</style>

<script>
let inventoryTable;

$(document).ready(function() {
    inventoryTable = $('#inventoryTable').DataTable({
        ajax: {
            url: '../api/user_side.php?module=user_inventory',
            type: 'POST',
            data: function(d) { d.action = 'list'; }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 5 },
            { data: 4 }
        ]
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>