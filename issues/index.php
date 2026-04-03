<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'issues', 'list');

$page_title = 'Material Issues';

// Handle goods receipt
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receive_goods'])) {
    $po_id = intval($_POST['po_id'] ?? 0);
    $received_qty = floatval($_POST['received_qty'] ?? 0);
    $accepted_qty = floatval($_POST['accepted_qty'] ?? 0);
    $rejected_qty = floatval($_POST['rejected_qty'] ?? 0);
    $remarks = sanitize($_POST['remarks'] ?? '');

    if ($po_id > 0 && $received_qty > 0 && $accepted_qty >= 0) {
        // Get material request ID
        $po = fetchOne("SELECT material_request_id FROM purchase_orders WHERE id = ?", "i", [$po_id]);

        if ($po) {
            // Insert goods receipt
            insertAndGetId(
                "INSERT INTO goods_receipts (purchase_order_id, material_request_id, received_date, received_quantity, accepted_quantity, rejected_quantity, remarks, received_by) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)",
                "iidddsi",
                [$po_id, $po['material_request_id'], $received_qty, $accepted_qty, $rejected_qty, $remarks, $_SESSION['user_id']]
            );

            // Update material request status
            runQuery("UPDATE material_requests SET status = 'received' WHERE id = ?", "i", [$po['material_request_id']]);

            // Update PO status
            $po_data = fetchOne("SELECT mr.quantity FROM material_requests mr WHERE mr.id = ?", "i", [$po['material_request_id']]);
            if ($accepted_qty >= $po_data['quantity']) {
                runQuery("UPDATE purchase_orders SET status = 'completed' WHERE id = ?", "i", [$po_id]);
            } else {
                runQuery("UPDATE purchase_orders SET status = 'partial' WHERE id = ?", "i", [$po_id]);
            }

            // Update inventory
            $item = fetchOne("SELECT item_name FROM material_requests WHERE id = ?", "i", [$po['material_request_id']]);
            if ($item) {
                $inv = fetchOne("SELECT id, current_stock FROM inventory WHERE item_name LIKE ?", "s", ["%" . $item['item_name'] . "%"]);

                if ($inv) {
                    $new_stock = $inv['current_stock'] + $accepted_qty;
                    runQuery("UPDATE inventory SET current_stock = ? WHERE id = ?", "di", [$new_stock, $inv['id']]);
                }
            }

            header("Location: index.php?success=Goods received successfully");
            exit;
        }
    }
}

// Handle material issue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['issue_material'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $issue_qty = floatval($_POST['issue_qty'] ?? 0);

    if ($request_id > 0 && $issue_qty > 0) {
        // Insert material issue
        insertAndGetId(
            "INSERT INTO material_issues (material_request_id, issue_date, issue_quantity, issued_by) VALUES (?, CURDATE(), ?, ?)",
            "idi",
            [$request_id, $issue_qty, $_SESSION['user_id']]
        );

        // Update request status
        runQuery("UPDATE material_requests SET status = 'issued' WHERE id = ?", "i", [$request_id]);

        // Get item name and reduce inventory
        $req = fetchOne("SELECT item_name, quantity FROM material_requests WHERE id = ?", "i", [$request_id]);
        if ($req) {
            $inv = fetchOne("SELECT id, current_stock FROM inventory WHERE item_name LIKE ?", "s", ["%" . $req['item_name'] . "%"]);
            if ($inv) {
                $new_stock = max(0, $inv['current_stock'] - $issue_qty);
                runQuery("UPDATE inventory SET current_stock = ? WHERE id = ?", "di", [$new_stock, $inv['id']]);
            }
        }

        header("Location: index.php?success=Material issued successfully");
        exit;
    }
}

// Get ordered POs for goods receipt
$ordered_pos = fetchAll("
    SELECT po.*, mr.item_name, mr.quantity as req_qty, mr.unit, p.project_name
    FROM purchase_orders po
    LEFT JOIN material_requests mr ON po.material_request_id = mr.id
    LEFT JOIN projects p ON mr.project_id = p.id
    WHERE po.status IN ('ordered', 'partial') AND mr.status = 'ordered'
");

// Get received items for issuing
$received_items = fetchAll("
    SELECT mr.*, p.project_name
    FROM material_requests mr
    LEFT JOIN projects p ON mr.project_id = p.id
    WHERE mr.status = 'received'
");

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Material Issues & Receipts</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <!-- Goods Receipt Section -->
    <div class="card">
        <div class="card-header">
            <h3>Goods Receipt (Receive Ordered Items)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($ordered_pos)): ?>
                <div class="empty-state">
                    <i class="fas fa-truck-loading"></i>
                    <p>No pending orders to receive</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Project</th>
                                <th>Item</th>
                                <th>Ordered Qty</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordered_pos as $po): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($po['po_number']); ?></td>
                                <td><?php echo htmlspecialchars($po['project_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($po['item_name']); ?></td>
                                <td><?php echo $po['req_qty'] . ' ' . $po['unit']; ?></td>
                                <td><span class="badge badge-<?php echo $po['status']; ?>"><?php echo ucfirst($po['status']); ?></span></td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="openReceiveGoods(<?php echo $po['id']; ?>, '<?php echo htmlspecialchars($po['item_name']); ?>', <?php echo $po['req_qty']; ?>, '<?php echo $po['unit']; ?>')">
                                        <i class="fas fa-truck-loading"></i> Receive
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Issue Material Section -->
    <div class="card">
        <div class="card-header">
            <h3>Issue Materials to Site</h3>
        </div>
        <div class="card-body">
            <?php if (empty($received_items)): ?>
                <div class="empty-state">
                    <i class="fas fa-truck"></i>
                    <p>No materials to issue</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table table-striped table-bordered">
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
                            <?php foreach ($received_items as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['project_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo $item['quantity'] . ' ' . $item['unit']; ?></td>
                                <td><span class="badge badge-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="openIssueMaterial(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['quantity']; ?>, '<?php echo $item['unit']; ?>')">
                                        <i class="fas fa-paper-plane"></i> Issue
                                    </button>
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

<!-- Receive Goods Modal -->
<div id="receiveGoodsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Receive Goods</h3>
            <button class="modal-close" onclick="closeModal('receiveGoodsModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="receive_goods" value="1">
                <input type="hidden" name="po_id" id="recPoId">
                <div class="form-group">
                    <label>Item</label>
                    <p id="recItem" class="text-muted"></p>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="received_qty">Received Quantity *</label>
                        <input type="number" id="received_qty" name="received_qty" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="accepted_qty">Accepted Quantity *</label>
                        <input type="number" id="accepted_qty" name="accepted_qty" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="rejected_qty">Rejected Quantity</label>
                        <input type="number" id="rejected_qty" name="rejected_qty" step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="rec_remarks">Remarks</label>
                    <textarea id="rec_remarks" name="remarks" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('receiveGoodsModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Receive</button>
            </div>
        </form>
    </div>
</div>

<!-- Issue Material Modal -->
<div id="issueMaterialModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Issue Material to Site</h3>
            <button class="modal-close" onclick="closeModal('issueMaterialModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="issue_material" value="1">
                <input type="hidden" name="request_id" id="issueRequestId">
                <div class="form-group">
                    <label>Item</label>
                    <p id="issueItem" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label for="issue_qty">Issue Quantity *</label>
                    <input type="number" id="issue_qty" name="issue_qty" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('issueMaterialModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Issue</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReceiveGoods(id, item, qty, unit) {
    document.getElementById('recPoId').value = id;
    document.getElementById('recItem').textContent = item + ' - ' + qty + ' ' + unit;
    document.getElementById('received_qty').value = qty;
    document.getElementById('accepted_qty').value = qty;
    openModal('receiveGoodsModal');
}

function openIssueMaterial(id, item, qty, unit) {
    document.getElementById('issueRequestId').value = id;
    document.getElementById('issueItem').textContent = item + ' - ' + qty + ' ' + unit;
    document.getElementById('issue_qty').value = qty;
    document.getElementById('issue_qty').max = qty;
    openModal('issueMaterialModal');
}
</script>

<?php require_once '../includes/footer.php'; ?>