<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'inventory', 'list');

$page_title = 'Inventory';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-warehouse"></i> Inventory</h2>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Stock is updated only through GRN. No manual editing allowed.
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
                        <th>Current Stock</th>
                        <th>Min Level</th>
                        <th>Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<style>
.alert-info { display: flex; align-items: center; gap: 10px; }
</style>

<script>
let inventoryTable;

$(document).ready(function() {
    inventoryTable = $('#inventoryTable').DataTable({
        ajax: {
            url: '../api/buy_side.php?module=inventory',
            type: 'POST',
            data: function(d) { d.action = 'list'; }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 }
        ]
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>