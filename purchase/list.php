<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'purchase', 'list');

$page_title = 'Purchase Orders';

require_once '../includes/header.php';
?>

<style>
.badge-ordered { background-color: #ffc107; color: #000; }
.badge-received { background-color: #28a745; color: #fff; }
.badge-cancelled { background-color: #dc3545; color: #fff; }
</style>

<div class="content">
    <div class="page-header">
        <h2>Purchase Orders</h2>
    </div>

    <!-- Create PO Section -->
    <?php
    $approved_requests = fetchAll("
        SELECT mr.*, p.project_name
        FROM material_requests mr
        LEFT JOIN projects p ON mr.project_id = p.id
        WHERE mr.status = 'approved'
        ORDER BY mr.created_at ASC
    ");
    ?>

    <?php if (!empty($approved_requests)): ?>
    <div class="card">
        <div class="card-header">
            <h3>Create Purchase Order</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Project</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_requests as $req): ?>
                        <tr>
                            <td><?php echo $req['id']; ?></td>
                            <td><?php echo htmlspecialchars($req['project_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                            <td><?php echo $req['quantity'] . ' ' . $req['unit']; ?></td>
                            <td><span class="badge badge-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="openCreatePO(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['item_name']); ?>', '<?php echo $req['quantity']; ?>', '<?php echo $req['unit']; ?>')">
                                    <i class="fas fa-plus"></i> Create PO
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PO List -->
    <div class="card">
        <div class="card-header">
            <h3>All Purchase Orders</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table id="purchaseTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Project</th>
                            <th>Item</th>
                            <th>Supplier</th>
                            <th>Amount (INR)</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create PO Modal -->
<div id="createPOModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Purchase Order</h3>
            <button class="modal-close" onclick="closeModal('createPOModal')">&times;</button>
        </div>
        <form id="createPOForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="request_id" id="poRequestId">
                <div class="form-group">
                    <label>Item</label>
                    <p id="poItem" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label for="supplier_name">Supplier Name *</label>
                    <input type="text" id="supplier_name" name="supplier_name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expected_date">Expected Date</label>
                        <input type="date" id="expected_date" name="expected_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="total_amount">Total Amount (INR) *</label>
                        <input type="number" id="total_amount" name="total_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('createPOModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create PO</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit PO Modal -->
<div id="editPOModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Purchase Order</h3>
            <button class="modal-close" onclick="closeModal('editPOModal')">&times;</button>
        </div>
        <form id="editPOForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="po_id" id="editPoId">
                <div class="form-group">
                    <label>PO Number</label>
                    <p id="editPoNumber" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label for="editSupplierName">Supplier Name *</label>
                    <input type="text" id="editSupplierName" name="supplier_name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editExpectedDate">Expected Date</label>
                        <input type="date" id="editExpectedDate" name="expected_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="editTotalAmount">Total Amount (INR) *</label>
                        <input type="number" id="editTotalAmount" name="total_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editStatus">Status *</label>
                    <select id="editStatus" name="status" class="form-control" required>
                        <option value="ordered">Ordered</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('editPOModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update PO</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreatePO(id, item, qty, unit) {
    document.getElementById('poRequestId').value = id;
    document.getElementById('poItem').textContent = item + ' - ' + qty + ' ' + unit;
    document.getElementById('createPOForm').reset();
    openModal('createPOModal');
}

function editPO(poId) {
    $.ajax({
        url: '/new_projectb/api/purchase.php',
        type: 'POST',
        data: { action: 'get', po_id: poId },
        success: function(response) {
            if (response.success) {
                var po = response.data;
                document.getElementById('editPoId').value = po.id;
                document.getElementById('editPoNumber').textContent = po.po_number;
                document.getElementById('editSupplierName').value = po.supplier_name;
                document.getElementById('editExpectedDate').value = po.expected_date || '';
                document.getElementById('editTotalAmount').value = po.total_amount;
                document.getElementById('editStatus').value = po.status;
                openModal('editPOModal');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to load purchase order data');
        }
    });
}

// Create PO Form Submit
$('#createPOForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();

    $.ajax({
        url: '/new_projectb/api/purchase.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Purchase Order created successfully!\nPO Number: ' + response.po_number);
                closeModal('createPOModal');
                $('#purchaseTable').DataTable().ajax.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to create purchase order');
        }
    });
});

// Edit PO Form Submit
$('#editPOForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();

    $.ajax({
        url: '/new_projectb/api/purchase.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Purchase Order updated successfully!');
                closeModal('editPOModal');
                $('#purchaseTable').DataTable().ajax.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Failed to update purchase order');
        }
    });
});

// Initialize DataTable
$(document).ready(function() {
    initDataTable('#purchaseTable', '/new_projectb/api/purchase.php', [
        { title: 'PO Number' },
        { title: 'Project' },
        { title: 'Item' },
        { title: 'Supplier' },
        { title: 'Amount (INR)' },
        { title: 'Order Date' },
        { title: 'Status' },
        { title: 'Action' }
    ]);
});
</script>

<?php require_once '../includes/footer.php'; ?>