<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle POST requests for create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create Purchase Order
    if ($action === 'create') {
        try {
            $request_id = intval($_POST['request_id'] ?? 0);
            $supplier_name = sanitize($_POST['supplier_name'] ?? '');
            $expected_date = $_POST['expected_date'] ?? null;
            $total_amount = floatval($_POST['total_amount'] ?? 0);

            // Validation
            if (empty($request_id) || empty($supplier_name) || $total_amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
                exit;
            }

            // Generate unique PO number (PO-YYYYMMDD-XXXX)
            $po_number = 'PO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insert purchase order
            $sql = "INSERT INTO purchase_orders (material_request_id, po_number, supplier_name, expected_date, total_amount, order_date, status, created_at)
                    VALUES (?, ?, ?, ?, ?, CURDATE(), 'ordered', NOW())";

            $result = insertAndGetId($sql, "isssd", [
                $request_id,
                $po_number,
                $supplier_name,
                $expected_date,
                $total_amount
            ]);

            if ($result['success']) {
                // Update material request status to 'ordered'
                $update_sql = "UPDATE material_requests SET status = 'ordered', updated_at = NOW() WHERE id = ?";
                $update_result = runQuery($update_sql, "i", [$request_id]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Purchase Order created successfully!',
                    'po_number' => $po_number
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create purchase order']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Update Purchase Order
    if ($action === 'update') {
        try {
            $po_id = intval($_POST['po_id'] ?? 0);
            $supplier_name = sanitize($_POST['supplier_name'] ?? '');
            $expected_date = $_POST['expected_date'] ?? null;
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'ordered');

            // Validation
            if (empty($po_id) || empty($supplier_name) || $total_amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
                exit;
            }

            // Valid statuses
            $valid_statuses = ['ordered', 'received', 'cancelled'];
            if (!in_array($status, $valid_statuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }

            // Update purchase order
            $sql = "UPDATE purchase_orders SET supplier_name = ?, expected_date = ?, total_amount = ?, status = ?, updated_at = NOW() WHERE id = ?";

            $result = runQuery($sql, "ssdsi", [
                $supplier_name,
                $expected_date,
                $total_amount,
                $status,
                $po_id
            ]);

            if ($result['success']) {
                // If status is 'received', update material request status
                if ($status === 'received') {
                    $po = fetchOne("SELECT material_request_id FROM purchase_orders WHERE id = ?", "i", [$po_id]);
                    if ($po && $po['material_request_id']) {
                        $update_sql = "UPDATE material_requests SET status = 'received', updated_at = NOW() WHERE id = ?";
                        runQuery($update_sql, "i", [$po['material_request_id']]);
                    }
                }

                echo json_encode(['success' => true, 'message' => 'Purchase Order updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update purchase order']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get single PO for edit
    if ($action === 'get') {
        $po_id = intval($_POST['po_id'] ?? 0);

        if (empty($po_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
            exit;
        }

        $sql = "SELECT po.*, mr.item_name, mr.quantity as req_qty, mr.unit, p.project_name
                FROM purchase_orders po
                LEFT JOIN material_requests mr ON po.material_request_id = mr.id
                LEFT JOIN projects p ON mr.project_id = p.id
                WHERE po.id = ?";

        $po = fetchOne($sql, "i", [$po_id]);

        if ($po) {
            echo json_encode(['success' => true, 'data' => $po]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found']);
        }
        exit;
    }
}

// Handle GET request for DataTables
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// Build WHERE clause
$where = "";
if ($search) {
    $search_param = sanitize($search);
    $where = " WHERE po.po_number LIKE '%$search_param%' OR p.project_name LIKE '%$search_param%' OR mr.item_name LIKE '%$search_param%' OR po.supplier_name LIKE '%$search_param%'";
}

// Get total count
$total = fetchOne("SELECT COUNT(*) as count FROM purchase_orders po LEFT JOIN material_requests mr ON po.material_request_id = mr.id LEFT JOIN projects p ON mr.project_id = p.id");
$recordsTotal = $total['count'];

// Get filtered count
$filtered = fetchOne("SELECT COUNT(*) as count FROM purchase_orders po LEFT JOIN material_requests mr ON po.material_request_id = mr.id LEFT JOIN projects p ON mr.project_id = p.id $where");
$recordsFiltered = $filtered['count'];

// Get data with actions
$sql = "SELECT po.*, mr.item_name, mr.quantity as req_qty, mr.unit, p.project_name
        FROM purchase_orders po
        LEFT JOIN material_requests mr ON po.material_request_id = mr.id
        LEFT JOIN projects p ON mr.project_id = p.id
        $where
        ORDER BY po.created_at DESC LIMIT $start, $length";

$pos = fetchAll($sql);

$data = [];
foreach ($pos as $po) {
    // Status badge
    $status_badge = '<span class="badge badge-' . $po['status'] . '">' . ucfirst($po['status']) . '</span>';

    // Action buttons
    $actions = '<button class="btn btn-sm btn-info" onclick="editPO(' . $po['id'] . ')"><i class="fas fa-edit"></i> Edit</button>';

    $data[] = [
        htmlspecialchars($po['po_number']),
        htmlspecialchars($po['project_name'] ?? '-'),
        htmlspecialchars($po['item_name'] ?? '-'),
        htmlspecialchars($po['supplier_name']),
        number_format($po['total_amount'], 2),
        date('d M Y', strtotime($po['order_date'])),
        $status_badge,
        $actions
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);