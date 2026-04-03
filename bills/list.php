<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'bills', 'list');

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// POST handlers for Bills management (MUST be before header)
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
    // Handle: Create New Bill
    // -------------------------------------------------------
    if (isset($_POST['create_bill'])) {
        // Sanitize and validate inputs
        $project_id = intval($_POST['project_id'] ?? 0);
        $bill_date = trim($_POST['bill_date'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        // Validation
        $errors = [];

        if ($project_id <= 0) {
            $errors[] = 'Please select a project';
        }
        if (empty($bill_date)) {
            $errors[] = 'Bill date is required';
        }
        if ($amount <= 0) {
            $errors[] = 'Amount must be greater than zero';
        }

        // Verify project exists
        $project = fetchOne("SELECT id, project_name FROM projects WHERE id = ?", 'i', [$project_id]);
        if (!$project) {
            $errors[] = 'Invalid project selected';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header('Location: list.php');
            exit;
        }

        // Generate bill number: BILL-YYYY-XXX
        $year = date('Y');
        $last_bill = fetchOne("SELECT bill_number FROM bills WHERE bill_number LIKE ? ORDER BY id DESC", 's', ["BILL-$year-%"]);
        $next_num = 1;
        if ($last_bill) {
            $last_num = intval(substr($last_bill['bill_number'], -3));
            $next_num = $last_num + 1;
        }
        $bill_number = sprintf("BILL-%s-%03d", $year, $next_num);

        // Get created_by from session
        $created_by = $_SESSION['user_id'];

        // Insert bill using prepared statement
        $sql = "INSERT INTO bills (bill_number, project_id, bill_date, amount, description, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())";
        $result = insertAndGetId($sql, 'sisdsi', [$bill_number, $project_id, $bill_date, $amount, $description, $created_by]);

        if ($result['success']) {
            $_SESSION['success'] = 'Bill "' . htmlspecialchars($bill_number) . '" created successfully';
        } else {
            $_SESSION['error'] = 'Failed to create bill: ' . $result['message'];
        }

        header('Location: list.php');
        exit;
    }

    // -------------------------------------------------------
    // Handle: Update Bill Status
    // -------------------------------------------------------
    if (isset($_POST['update_status'])) {
        $bill_id = intval($_POST['bill_id'] ?? 0);
        $new_status = trim($_POST['status'] ?? '');

        // Validation
        $errors = [];

        if ($bill_id <= 0) {
            $errors[] = 'Invalid bill';
        }
        if (!in_array($new_status, ['pending', 'paid'])) {
            $errors[] = 'Invalid status';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header('Location: list.php');
            exit;
        }

        // Get current bill
        $bill = fetchOne("SELECT id, bill_number, status FROM bills WHERE id = ?", 'i', [$bill_id]);

        if (!$bill) {
            $_SESSION['error'] = 'Bill not found';
            header('Location: list.php');
            exit;
        }

        // Prevent updating to same status
        if ($bill['status'] === $new_status) {
            $_SESSION['error'] = 'Bill is already ' . $new_status;
            header('Location: list.php');
            exit;
        }

        // Update status using prepared statement
        $sql = "UPDATE bills SET status = ?, updated_at = NOW() WHERE id = ?";
        $result = runQuery($sql, 'si', [$new_status, $bill_id]);

        if ($result['success']) {
            $_SESSION['success'] = 'Bill "' . htmlspecialchars($bill['bill_number']) . '" marked as ' . $new_status;
        } else {
            $_SESSION['error'] = 'Failed to update bill status: ' . $result['message'];
        }

        header('Location: list.php');
        exit;
    }
}

$page_title = 'Bills';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Billing</h2>
        <button class="btn btn-primary" onclick="openModal('createBillModal')">
            <i class="fas fa-plus"></i> Create Bill
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

    <!-- Stats -->
    <?php
    $pending_bills = fetchOne("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as amount FROM bills WHERE status = 'pending'");
    $paid_bills = fetchOne("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as amount FROM bills WHERE status = 'paid'");
    ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h4>Pending Bills</h4>
                <div class="value"><?php echo number_format($pending_bills['amount'], 0); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h4>Paid Bills</h4>
                <div class="value"><?php echo number_format($paid_bills['amount'], 0); ?></div>
            </div>
        </div>
    </div>

    <!-- Bills List -->
    <div class="card">
        <div class="card-header">
            <h3>All Bills</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table id="billsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Bill No.</th>
                            <th>Project</th>
                            <th>Amount (INR)</th>
                            <th>Date</th>
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

<!-- Create Bill Modal -->
<div id="createBillModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New Bill</h3>
            <button class="modal-close" onclick="closeModal('createBillModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="create_bill" value="1">
                <?php $projects = fetchAll("SELECT id, project_name FROM projects WHERE status = 'active' ORDER BY project_name"); ?>
                <div class="form-group">
                    <label for="project_id">Project *</label>
                    <select id="project_id" name="project_id" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="bill_date">Bill Date *</label>
                        <input type="date" id="bill_date" name="bill_date" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (INR) *</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('createBillModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Bill</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Bill Status</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="bill_id" id="statusBillId">
                <input type="hidden" name="status" id="statusValue">
                <p>Are you sure you want to mark this bill as <strong id="statusText"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateBillStatus(id, status) {
    document.getElementById('statusBillId').value = id;
    document.getElementById('statusValue').value = status;
    document.getElementById('statusText').textContent = status;
    openModal('statusModal');
}

$(document).ready(function() {
    initDataTable('#billsTable', '/new_projectb/api/bills.php', [
        { title: 'Bill No.' },
        { title: 'Project' },
        { title: 'Amount (INR)' },
        { title: 'Date' },
        { title: 'Status' },
        { title: 'Actions', orderable: false }
    ]);
});
</script>

<?php require_once '../includes/footer.php'; ?>