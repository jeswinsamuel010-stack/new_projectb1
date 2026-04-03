<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'inventory', 'list');

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// POST handlers for inventory management (MUST be before header)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: list.php');
        exit;
    }
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Unauthorized access';
        header('Location: ../login.php');
        exit;
    }

    $conn = getDB();

    // -------------------------------------------------------
    // Handle: Add New Item
    // -------------------------------------------------------
    if (isset($_POST['add_item'])) {
        // Sanitize and validate inputs
        $item_name = trim($_POST['item_name'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $rate = floatval($_POST['rate'] ?? 0);
        $current_stock = floatval($_POST['current_stock'] ?? 0);
        $min_stock_level = floatval($_POST['min_stock_level'] ?? 0);

        // Validation
        $errors = [];

        if (empty($item_name)) {
            $errors[] = 'Item name is required';
        }
        if (empty($unit)) {
            $errors[] = 'Unit is required';
        }
        if ($rate < 0) {
            $errors[] = 'Rate cannot be negative';
        }
        if ($current_stock < 0) {
            $errors[] = 'Current stock cannot be negative';
        }
        if ($min_stock_level < 0) {
            $errors[] = 'Minimum stock level cannot be negative';
        }

        // Check for duplicate item name
        $check = fetchOne("SELECT id FROM inventory WHERE item_name = ?", 's', [$item_name]);
        if ($check) {
            $errors[] = 'Item already exists';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header('Location: list.php');
            exit;
        }

        // Insert new item using prepared statement
        $sql = "INSERT INTO inventory (item_name, unit, rate, current_stock, min_stock_level, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $result = insertAndGetId($sql, 'ssdds', [$item_name, $unit, $rate, $current_stock, $min_stock_level]);

        if ($result['success']) {
            $_SESSION['success'] = 'Item "' . htmlspecialchars($item_name) . '" added successfully';
        } else {
            $_SESSION['error'] = 'Failed to add item: ' . $result['message'];
        }

        header('Location: list.php');
        exit;
    }

    // -------------------------------------------------------
    // Handle: Update Stock (Add or Remove)
    // -------------------------------------------------------
    if (isset($_POST['update_stock'])) {
        $item_id = intval($_POST['item_id'] ?? 0);
        $operation = $_POST['operation'] ?? '';
        $quantity = floatval($_POST['quantity'] ?? 0);

        // Validation
        $errors = [];

        if ($item_id <= 0) {
            $errors[] = 'Invalid item';
        }
        if (!in_array($operation, ['add', 'remove'])) {
            $errors[] = 'Invalid operation';
        }
        if ($quantity <= 0) {
            $errors[] = 'Quantity must be greater than zero';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header('Location: list.php');
            exit;
        }

        // Get current stock
        $item = fetchOne("SELECT id, item_name, current_stock FROM inventory WHERE id = ?", 'i', [$item_id]);

        if (!$item) {
            $_SESSION['error'] = 'Item not found';
            header('Location: list.php');
            exit;
        }

        // Calculate new stock
        $new_stock = $operation === 'add'
            ? $item['current_stock'] + $quantity
            : $item['current_stock'] - $quantity;

        // Prevent negative stock on remove
        if ($operation === 'remove' && $new_stock < 0) {
            $_SESSION['error'] = 'Insufficient stock. Current stock: ' . $item['current_stock'];
            header('Location: list.php');
            exit;
        }

        // Update stock using prepared statement
        $sql = "UPDATE inventory SET current_stock = ? WHERE id = ?";
        $result = runQuery($sql, 'di', [$new_stock, $item_id]);

        if ($result['success']) {
            $_SESSION['success'] = 'Stock ' . ($operation === 'add' ? 'added' : 'removed') . ' successfully';
        } else {
            $_SESSION['error'] = 'Failed to update stock: ' . $result['message'];
        }

        header('Location: list.php');
        exit;
    }
}

$page_title = 'Inventory';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Inventory Management</h2>
        <button class="btn btn-primary" onclick="openModal('addItemModal')">
            <i class="fas fa-plus"></i> Add Item
        </button>
    </div>

    <?php
    // Display session messages
    if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table id="inventoryTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Unit</th>
                            <th>Current Stock</th>
                            <th>Min Level</th>
                            <th>Rate (INR)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Item</h3>
            <button class="modal-close" onclick="closeModal('addItemModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="add_item" value="1">
                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" id="item_name" name="item_name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="unit">Unit *</label>
                        <select id="unit" name="unit" required>
                            <option value="pieces">Pieces</option>
                            <option value="bags">Bags</option>
                            <option value="kg">KG</option>
                            <option value="tons">Tons</option>
                            <option value="cu.mt">Cubic Meter</option>
                            <option value="sq.ft">Square Feet</option>
                            <option value="liter">Liter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rate">Rate (INR)</label>
                        <input type="number" id="rate" name="rate" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_stock">Current Stock</label>
                        <input type="number" id="current_stock" name="current_stock" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="min_stock_level">Min Stock Level</label>
                        <input type="number" id="min_stock_level" name="min_stock_level" step="0.01" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('addItemModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Stock Modal -->
<div id="updateStockModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="stockModalTitle">Update Stock</h3>
            <button class="modal-close" onclick="closeModal('updateStockModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="update_stock" value="1">
                <input type="hidden" name="item_id" id="stockItemId">
                <input type="hidden" name="operation" id="stockOperation">
                <div class="form-group">
                    <label>Item</label>
                    <p id="stockItemName" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('updateStockModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUpdateStock(id, name, operation) {
    document.getElementById('stockItemId').value = id;
    document.getElementById('stockItemName').textContent = name;
    document.getElementById('stockOperation').value = operation;
    document.getElementById('stockModalTitle').textContent = operation == 'add' ? 'Add Stock' : 'Remove Stock';
    openModal('updateStockModal');
}

$(document).ready(function() {
    initDataTable('#inventoryTable', '/new_projectb/api/inventory.php', [
        { title: 'ID' },
        { title: 'Item Name' },
        { title: 'Unit' },
        { title: 'Current Stock' },
        { title: 'Min Level' },
        { title: 'Rate (INR)' },
        { title: 'Status' },
        { title: 'Actions', orderable: false }
    ]);
});
</script>

<?php require_once '../includes/footer.php'; ?>