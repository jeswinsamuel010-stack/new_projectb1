<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// =====================================================
// MATERIAL REQUEST API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'request') {

    // Create Material Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
        try {
            $project_id = intval($_POST['project_id'] ?? 0);
            $item_name = sanitize($_POST['item_name'] ?? '');
            $quantity = floatval($_POST['quantity'] ?? 0);
            $unit = sanitize($_POST['unit'] ?? '');
            $location = sanitize($_POST['location'] ?? '');
            $reason = sanitize($_POST['reason'] ?? '');

            if (empty($project_id) || empty($item_name) || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
                exit;
            }

            // Generate request number
            $request_number = 'MR-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insert request
            $result = insertAndGetId(
                "INSERT INTO user_material_requests (request_number, project_id, item_name, quantity, unit, location, reason, status, requested_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)",
                "issiisssi",
                [$request_number, $project_id, $item_name, $quantity, $unit, $location, $reason, $_SESSION['user_id']]
            );

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Material request created! Waiting for approval.',
                    'request_id' => $result['id'],
                    'request_number' => $request_number
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create request']);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get single request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'get') {
        $request_id = intval($_POST['request_id'] ?? 0);

        $request = fetchOne(
            "SELECT r.*, p.project_name, u.full_name as requested_by_name
             FROM user_material_requests r
             LEFT JOIN projects p ON r.project_id = p.id
             LEFT JOIN users u ON r.requested_by = u.id
             WHERE r.id = ?",
            "i",
            [$request_id]
        );

        if ($request) {
            echo json_encode(['success' => true, 'data' => $request]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }
        exit;
    }

    // Update request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
        $request_id = intval($_POST['request_id'] ?? 0);
        $item_name = sanitize($_POST['item_name'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $unit = sanitize($_POST['unit'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');

        $result = runQuery(
            "UPDATE user_material_requests SET item_name = ?, quantity = ?, unit = ?, location = ?, reason = ? WHERE id = ? AND status = 'pending'",
            "ssissi",
            [$item_name, $quantity, $unit, $location, $reason, $request_id]
        );

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Request updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update (only pending requests can be edited)']);
        }
        exit;
    }

    // Delete request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
        $request_id = intval($_POST['request_id'] ?? 0);
        runQuery("DELETE FROM user_material_requests WHERE id = ? AND status = 'pending'", "i", [$request_id]);
        echo json_encode(['success' => true, 'message' => 'Request deleted']);
        exit;
    }

    // List requests (DataTables)
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';
    $status_filter = $_POST['status'] ?? '';

    $where = "1=1";
    $params = [];
    $types = '';

    if ($search) {
        $where .= " AND (r.request_number LIKE ? OR p.project_name LIKE ? OR r.item_name LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = [$search_param, $search_param, $search_param];
        $types = 'sss';
    }

    if ($status_filter) {
        $where .= " AND r.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    $total = fetchOne("SELECT COUNT(*) as count FROM user_material_requests r");
    $recordsTotal = $total['count'];

    if ($where && $params) {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM user_material_requests r LEFT JOIN projects p ON r.project_id = p.id WHERE $where", $types, $params);
    } else {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM user_material_requests r");
    }
    $recordsFiltered = $filtered['count'];

    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';

    $sql = "SELECT r.*, p.project_name, u.full_name as requested_by_name
            FROM user_material_requests r
            LEFT JOIN projects p ON r.project_id = p.id
            LEFT JOIN users u ON r.requested_by = u.id
            WHERE $where
            ORDER BY r.created_at DESC LIMIT ?, ?";

    $requests = fetchAll($sql, $types, $params);

    $data = [];
    foreach ($requests as $r) {
        $statusClass = match($r['status']) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'issued' => 'info',
            default => 'secondary'
        };

        $data[] = [
            htmlspecialchars($r['request_number']),
            htmlspecialchars($r['project_name'] ?? '-'),
            htmlspecialchars($r['item_name']),
            $r['quantity'] . ' ' . $r['unit'],
            date('d M Y', strtotime($r['created_at'])),
            '<span class="badge bg-' . $statusClass . '">' . ucfirst($r['status']) . '</span>',
            '<button class="btn btn-sm btn-info me-1" onclick="viewRequest(' . $r['id'] . ')"><i class="fas fa-eye"></i></button>' .
            ($r['status'] === 'pending' ? '<button class="btn btn-sm btn-danger" onclick="deleteRequest(' . $r['id'] . ')"><i class="fas fa-trash"></i></button>' : '')
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
    exit;
}

// =====================================================
// APPROVAL API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'approval') {

    // Approve/Reject request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'approve') {
        try {
            $request_id = intval($_POST['request_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? ''); // approved or rejected
            $remarks = sanitize($_POST['remarks'] ?? '');

            if (empty($request_id) || !in_array($status, ['approved', 'rejected'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            // Check current status
            $request = fetchOne("SELECT status FROM user_material_requests WHERE id = ?", "i", [$request_id]);
            if (!$request || $request['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
                exit;
            }

            // Update request
            runQuery(
                "UPDATE user_material_requests SET status = ?, approved_by = ?, approved_date = NOW(), approval_remarks = ? WHERE id = ?",
                "sisi",
                [$status, $_SESSION['user_id'], $remarks, $request_id]
            );

            $redirect = $status === 'approved' ? 'issues.php?request_id=' . $request_id : '';

            echo json_encode([
                'success' => true,
                'message' => 'Request ' . $status . ' successfully!',
                'redirect' => $redirect
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get pending approvals count
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'count') {
        $count = fetchOne("SELECT COUNT(*) as count FROM user_material_requests WHERE status = 'pending'");
        echo json_encode(['success' => true, 'count' => $count['count']]);
        exit;
    }
}

// =====================================================
// ISSUE MATERIAL API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'issue') {

    // Issue material
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
        try {
            $request_id = intval($_POST['request_id'] ?? 0);
            $issue_quantity = floatval($_POST['issue_quantity'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');

            if (empty($request_id) || $issue_quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            // Get request details
            $request = fetchOne("SELECT * FROM user_material_requests WHERE id = ?", "i", [$request_id]);
            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                exit;
            }

            if ($request['status'] !== 'approved') {
                echo json_encode(['success' => false, 'message' => 'Request must be approved before issuing']);
                exit;
            }

            // Check stock in buy_inventory
            $inventory = fetchOne("SELECT * FROM buy_inventory WHERE item_name = ?", "s", [$request['item_name']]);

            $available = $inventory ? floatval($inventory['current_stock']) : 0;

            if ($available < $issue_quantity) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Insufficient stock! Available: ' . $available . ' ' . $request['unit']
                ]);
                exit;
            }

            // Generate issue number
            $issue_number = 'ISS-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insert issue
            $result = insertAndGetId(
                "INSERT INTO user_issues (issue_number, request_id, issue_date, issue_quantity, issued_by, notes)
                 VALUES (?, ?, CURDATE(), ?, ?, ?)",
                "sidisi",
                [$issue_number, $request_id, $issue_quantity, $_SESSION['user_id'], $notes]
            );

            if (!$result['success']) {
                echo json_encode(['success' => false, 'message' => 'Failed to create issue']);
                exit;
            }

            // Update inventory stock (decrease)
            if ($inventory) {
                runQuery(
                    "UPDATE buy_inventory SET current_stock = current_stock - ?, total_issued = total_issued + ?, last_updated = NOW() WHERE id = ?",
                    "ddi",
                    [$issue_quantity, $issue_quantity, $inventory['id']]
                );
            }

            // Update request status
            $new_status = 'issued';
            if ($issue_quantity < $request['quantity']) {
                // Partial issue - could add partial handling
                $new_status = 'issued';
            }

            runQuery(
                "UPDATE user_material_requests SET status = ?, updated_at = NOW() WHERE id = ?",
                "si",
                [$new_status, $request_id]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Material issued successfully! Stock updated.',
                'issue_id' => $result['id'],
                'issue_number' => $issue_number,
                'stock_updated' => true
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get request with stock info for issue page
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'get_for_issue') {
        $request_id = intval($_POST['request_id'] ?? 0);

        $request = fetchOne(
            "SELECT r.*, p.project_name
             FROM user_material_requests r
             LEFT JOIN projects p ON r.project_id = p.id
             WHERE r.id = ?",
            "i",
            [$request_id]
        );

        if ($request) {
            // Get stock info
            $inventory = fetchOne("SELECT * FROM buy_inventory WHERE item_name = ?", "s", [$request['item_name']]);
            $request['available_stock'] = $inventory ? floatval($inventory['current_stock']) : 0;
            $request['total_issued'] = $inventory ? floatval($inventory['total_issued']) : 0;

            echo json_encode(['success' => true, 'data' => $request]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }
        exit;
    }

    // List issued materials
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);

    $total = fetchOne("SELECT COUNT(*) as count FROM user_issues");
    $recordsTotal = $total['count'];
    $recordsFiltered = $recordsTotal;

    $sql = "SELECT i.*, r.request_number, r.item_name, r.unit, r.quantity as requested_qty, p.project_name, u.full_name as issued_by_name
            FROM user_issues i
            LEFT JOIN user_material_requests r ON i.request_id = r.id
            LEFT JOIN projects p ON r.project_id = p.id
            LEFT JOIN users u ON i.issued_by = u.id
            ORDER BY i.created_at DESC LIMIT $start, $length";

    $issues = fetchAll($sql);

    $data = [];
    foreach ($issues as $issue) {
        $data[] = [
            htmlspecialchars($issue['issue_number']),
            htmlspecialchars($issue['request_number']),
            htmlspecialchars($issue['project_name'] ?? '-'),
            htmlspecialchars($issue['item_name']),
            $issue['issue_quantity'] . ' ' . $issue['unit'],
            date('d M Y', strtotime($issue['issue_date'])),
            htmlspecialchars($issue['issued_by_name'] ?? '-')
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
    exit;
}

// =====================================================
// USER INVENTORY API (Stock Out View)
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'user_inventory') {

    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';

    $where = "1=1";
    $params = [];
    $types = '';

    if ($search) {
        $where .= " AND item_name LIKE ?";
        $search_param = '%' . $search . '%';
        $params = [$search_param];
        $types = 's';
    }

    $total = fetchOne("SELECT COUNT(*) as count FROM buy_inventory");
    $recordsTotal = $total['count'];

    if ($where && $params) {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_inventory WHERE $where", $types, $params);
    } else {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_inventory");
    }
    $recordsFiltered = $filtered['count'];

    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';

    $sql = "SELECT * FROM buy_inventory WHERE $where ORDER BY item_name ASC LIMIT ?, ?";

    $items = fetchAll($sql, $types, $params);

    $data = [];
    foreach ($items as $item) {
        $isLow = floatval($item['current_stock']) <= floatval($item['min_stock_level']);
        $isOut = floatval($item['current_stock']) <= 0;

        $data[] = [
            htmlspecialchars($item['item_name']),
            htmlspecialchars($item['unit']),
            '<strong>' . number_format($item['total_received'], 2) . '</strong>',
            '<strong>' . number_format($item['total_issued'], 2) . '</strong>',
            $isOut ? '<span class="badge bg-danger">Out of Stock</span>' :
            ($isLow ? '<span class="badge bg-warning text-dark">Low Stock</span>' :
            '<span class="badge bg-success">Available</span>'),
            number_format($item['current_stock'], 2)
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
    exit;
}

// Default response
echo json_encode(['error' => 'Invalid request']);