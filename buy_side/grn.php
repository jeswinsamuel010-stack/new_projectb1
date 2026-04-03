<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'issues', 'list');

$page_title = 'GRN - Goods Receipt';

require_once '../includes/header.php';

// Create buy_grn table if not exists (check existing structure first)
$result = $conn->query("SHOW COLUMNS FROM buy_grn LIKE 'project_name'");
if ($result->num_rows === 0) {
    // Add missing columns to existing table
    $conn->query("ALTER TABLE buy_grn ADD COLUMN project_name VARCHAR(255) AFTER grn_number");
    $conn->query("ALTER TABLE buy_grn ADD COLUMN supplier_name VARCHAR(255) AFTER project_name");
    $conn->query("ALTER TABLE buy_grn ADD COLUMN item_name VARCHAR(255) AFTER supplier_name");
    $conn->query("ALTER TABLE buy_grn ADD COLUMN quantity DECIMAL(10,2) AFTER item_name");
    $conn->query("ALTER TABLE buy_grn ADD COLUMN price DECIMAL(10,2) AFTER quantity");
    $conn->query("ALTER TABLE buy_grn ADD COLUMN pdf_path VARCHAR(500) AFTER price");
}

// Make supplier_id nullable if not already
$conn->query("ALTER TABLE buy_grn MODIFY supplier_id INT NULL");

// Create buy_inventory table if not exists (for inventory module)
$invResult = $conn->query("SHOW TABLES LIKE 'buy_inventory'");
if ($invResult->num_rows === 0) {
    $conn->query("CREATE TABLE buy_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(200) NOT NULL,
        unit VARCHAR(20) NOT NULL DEFAULT 'pieces',
        current_stock DECIMAL(10,2) DEFAULT 0,
        total_received DECIMAL(10,2) DEFAULT 0,
        rate DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} else {
    // Check if total_received column exists
    $checkCol = $conn->query("SHOW COLUMNS FROM buy_inventory LIKE 'total_received'");
    if ($checkCol->num_rows === 0) {
        $conn->query("ALTER TABLE buy_inventory ADD COLUMN total_received DECIMAL(10,2) DEFAULT 0 AFTER current_stock");
    }
}

// =====================================================
// SINGLE FUNCTION: saveGRN - Both PDF and Manual use this
// =====================================================
function saveGRN($project, $supplier, $item, $qty, $price, $pdf_path) {
    $conn = getDB();

    // Insert GRN record
    $sql = "INSERT INTO buy_grn (grn_number, project_name, supplier_name, item_name, quantity, price, pdf_path, supplier_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL)";
    $grn_number = 'GRN-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssids", $grn_number, $project, $supplier, $item, $qty, $price, $pdf_path);

    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to save GRN: ' . $stmt->error];
    }
    $stmt->close();

    // Update inventory (STOCK IN - increase quantity)
    $checkSql = "SELECT id, current_stock, total_received FROM buy_inventory WHERE item_name = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $item);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Item exists - increase quantity
        $newStock = $row['current_stock'] + $qty;
        $newTotal = $row['total_received'] + $qty;
        $updateSql = "UPDATE buy_inventory SET current_stock = ?, total_received = ? WHERE item_name = ?";
        $stmt2 = $conn->prepare($updateSql);
        $stmt2->bind_param("dds", $newStock, $newTotal, $item);
        $stmt2->execute();
        $stmt2->close();
    } else {
        // Insert new item
        $insertSql = "INSERT INTO buy_inventory (item_name, unit, current_stock, total_received, rate) VALUES (?, 'pieces', ?, ?, ?)";
        $stmt2 = $conn->prepare($insertSql);
        $stmt2->bind_param("sddd", $item, $qty, $qty, $price);
        $stmt2->execute();
        $stmt2->close();
    }
    $stmt->close();

    return ['success' => true, 'message' => 'GRN saved and inventory updated'];
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = trim($_POST['project_name'] ?? '');
    $supplierName = trim($_POST['supplier_name'] ?? '');
    $itemName = trim($_POST['item_name'] ?? '');
    $quantity = $_POST['quantity'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $pdfPath = null;

    $errors = [];

    // Validate required fields
    if (empty($projectName)) {
        $errors[] = 'Project name is required';
    }
    if (empty($supplierName)) {
        $errors[] = 'Supplier name is required';
    }
    if (empty($itemName)) {
        $errors[] = 'Item name is required';
    }
    if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
        $errors[] = 'Quantity must be a number greater than 0';
    }
    if (empty($price) || !is_numeric($price) || $price < 0) {
        $errors[] = 'Price must be a number >= 0';
    }

    // Handle PDF upload if present
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $fileExt = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'pdf') {
            $errors[] = 'Only PDF files are allowed';
        } else {
            $uploadDir = __DIR__ . '/../uploads/grn/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = uniqid('grn_') . '.pdf';
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetPath)) {
                $pdfPath = 'uploads/grn/' . $newFileName;
            } else {
                $errors[] = 'Failed to upload PDF file';
            }
        }
    }

    // If no errors, save GRN
    if (empty($errors)) {
        $result = saveGRN($projectName, $supplierName, $itemName, $quantity, $price, $pdfPath);
        if ($result['success']) {
            $message = 'GRN created successfully! Inventory updated.';
            $messageType = 'success';
        } else {
            $errors[] = $result['message'];
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// Get all GRN records (Recent GRNs)
$grnRecords = fetchAll("SELECT * FROM buy_grn ORDER BY created_at DESC LIMIT 50");
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-truck-loading"></i> GRN - Goods Receipt (Buy Side)</h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- PDF Upload Section with Submit Button -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-pdf"></i> Upload PDF GRN</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="pdfGrnForm">
                        <div class="form-group">
                            <label>Project Name *</label>
                            <input type="text" name="project_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Supplier Name *</label>
                            <input type="text" name="supplier_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Item Name *</label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" name="quantity" class="form-control" min="1" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Price *</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>PDF Document (Optional)</label>
                            <input type="file" name="pdf_file" accept=".pdf" class="form-control">
                            <small class="text-muted">Only PDF files allowed</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-upload"></i> Upload & Save GRN
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Manual GRN Section -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Manual GRN</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="manualGrnForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Project Name *</label>
                                    <input type="text" name="project_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Supplier Name *</label>
                                    <input type="text" name="supplier_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Item Name *</label>
                                    <input type="text" name="item_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Quantity *</label>
                                    <input type="number" name="quantity" class="form-control" min="1" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Price *</label>
                                    <input type="number" name="price" class="form-control" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Save GRN
                        </button>
                    </form>
                </div>
            </div>

            <!-- Empty State -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-info-circle fa-2x"></i>
                        <p>GRN = Goods Received Note (Stock In)</p>
                        <p class="text-muted">Inventory will increase when GRN is created</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRN List -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Recent GRNs</h3>
        </div>
        <div class="card-body">
            <?php if (empty($grnRecords)): ?>
                <p class="text-muted text-center">No GRN records found</p>
            <?php else: ?>
                <table class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>GRN Number</th>
                            <th>Project</th>
                            <th>Supplier</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>PDF</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grnRecords as $grn): ?>
                            <tr>
                                <td><?= htmlspecialchars($grn['grn_number']) ?></td>
                                <td><?= htmlspecialchars($grn['project_name']) ?></td>
                                <td><?= htmlspecialchars($grn['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($grn['item_name']) ?></td>
                                <td><?= htmlspecialchars($grn['quantity']) ?></td>
                                <td><?= htmlspecialchars(number_format($grn['price'], 2)) ?></td>
                                <td>
                                    <?php if ($grn['pdf_path']): ?>
                                        <a href="../<?= htmlspecialchars($grn['pdf_path']) ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-file-pdf"></i> View
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($grn['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Minimal styles - using Bootstrap from header.php */
</style>

<script>
// No JavaScript needed - using pure PHP forms with page redirect
// This ensures:
// 1. PDF upload has submit button
// 2. Both PDF and Manual use same saveGRN() function
// 3. Inventory updates correctly
// 4. Recent GRN list shows immediately after submit
</script>

<?php require_once '../includes/footer.php'; ?>