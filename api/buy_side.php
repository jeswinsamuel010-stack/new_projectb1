<?php
// Disable HTML error output
// ini_set('display_errors', 0);
// error_reporting(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Helper function for clean JSON response
function jsonResponse($status, $message, $data = null) {
    $response = ['status' => $status, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse('error', 'Unauthorized');
}

// =====================================================
// PURCHASE ORDER API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'purchase_order') {

    // CSRF validation helper
    function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Create Purchase Order
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
        try {
            $supplier_id = intval($_POST['supplier_id'] ?? 0);
            $po_date = $_POST['po_date'] ?? date('Y-m-d');
            $reference_number = sanitize($_POST['reference_number'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            $items = json_decode($_POST['items'] ?? '[]', true);

            // Validation - supplier is required
            if (empty($supplier_id)) {
                jsonResponse('error', 'Please provide supplier name');
            }

            // Validate at least one item
            if (empty($items) || !is_array($items) || count($items) === 0) {
                jsonResponse('error', 'Please add at least one item');
            }

            // Validate each item has required fields
            foreach ($items as $index => $item) {
                if (empty($item['item_name'])) {
                    jsonResponse('error', 'Item ' . ($index + 1) . ': Please enter item name');
                }
                if (empty($item['quantity']) || floatval($item['quantity']) <= 0) {
                    jsonResponse('error', 'Item ' . ($index + 1) . ': Please enter valid quantity');
                }
                if (empty($item['rate']) || floatval($item['rate']) <= 0) {
                    jsonResponse('error', 'Item ' . ($index + 1) . ': Please enter valid rate');
                }
            }

            // Generate PO number
            $po_number = 'BPO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insert PO
            $po_id = insertAndGetId(
                "INSERT INTO buy_purchase_orders (po_number, supplier_id, order_date, reference_number, notes, status, created_by)
                 VALUES (?, ?, ?, ?, ?, 'draft', ?)",
                "sisssi",
                [$po_number, $supplier_id, $po_date, $reference_number, $notes, $_SESSION['user_id']]
            );

            if (!$po_id['success']) {
                jsonResponse('error', 'Failed to create Purchase Order');
            }

            // Insert PO items
            foreach ($items as $item) {
                $amount = floatval($item['quantity']) * floatval($item['rate']);
                insertAndGetId(
                    "INSERT INTO buy_po_items (po_id, item_name, quantity, unit, rate, amount)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    "isssdd",
                    [$po_id['id'], $item['item_name'], $item['quantity'], $item['unit'], $item['rate'], $amount]
                );
            }

            jsonResponse('success', 'Purchase Order Saved Successfully', [
                'po_id' => $po_id['id'],
                'po_number' => $po_number
            ]);

        } catch (Exception $e) {
            jsonResponse('error', 'Error: ' . $e->getMessage());
        }
    }

    // Get PO for edit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'get') {
        $po_id = intval($_POST['po_id'] ?? 0);

        if (empty($po_id)) {
            jsonResponse('error', 'Invalid PO ID');
        }

        $po = fetchOne("SELECT * FROM buy_purchase_orders WHERE id = ?", "i", [$po_id]);
        $items = fetchAll("SELECT * FROM buy_po_items WHERE po_id = ?", "i", [$po_id]);

        if ($po) {
            // Get supplier and project info
            $po['supplier'] = fetchOne("SELECT * FROM suppliers WHERE id = ?", "i", [$po['supplier_id']]);
            $po['project'] = fetchOne("SELECT * FROM projects WHERE id = ?", "i", [$po['project_id']]);
            $po['items'] = $items;

            jsonResponse('success', 'PO retrieved', ['data' => $po]);
        } else {
            jsonResponse('error', 'PO not found');
        }
    }

    // Update PO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
        try {
            $po_id = intval($_POST['po_id'] ?? 0);
            $supplier_id = intval($_POST['supplier_id'] ?? 0);
            $po_date = $_POST['po_date'] ?? null;
            $reference_number = sanitize($_POST['reference_number'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            $status = sanitize($_POST['status'] ?? 'draft');
            $items = json_decode($_POST['items'] ?? '[]', true);

            if (empty($po_id) || empty($supplier_id)) {
                jsonResponse('error', 'Invalid data');
            }

            runQuery(
                "UPDATE buy_purchase_orders SET supplier_id = ?, order_date = ?, reference_number = ?, notes = ?, status = ?, updated_at = NOW() WHERE id = ?",
                "issssi",
                [$supplier_id, $po_date, $reference_number, $notes, $status, $po_id]
            );

            // Delete old items and insert new
            runQuery("DELETE FROM buy_po_items WHERE po_id = ?", "i", [$po_id]);

            foreach ($items as $item) {
                $amount = floatval($item['quantity']) * floatval($item['rate']);
                insertAndGetId(
                    "INSERT INTO buy_po_items (po_id, item_name, quantity, unit, rate, amount)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    "isssdd",
                    [$po_id, $item['item_name'], $item['quantity'], $item['unit'], $item['rate'], $amount]
                );
            }

            jsonResponse('success', 'PO updated successfully!');

        } catch (Exception $e) {
            jsonResponse('error', 'Error: ' . $e->getMessage());
        }
    }

    // Delete PO
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
        $po_id = intval($_POST['po_id'] ?? 0);

        if (empty($po_id)) {
            jsonResponse('error', 'Invalid PO ID');
        }

        runQuery("DELETE FROM buy_purchase_orders WHERE id = ?", "i", [$po_id]);
        jsonResponse('success', 'PO deleted');
    }

    // List POs (DataTables)
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';

    $where = "1=1";
    $params = [];
    $types = '';

    if ($search) {
        $where .= " AND (po.po_number LIKE ? OR s.name LIKE ? OR p.project_name LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = [$search_param, $search_param, $search_param];
        $types = 'sss';
    }

    $total = fetchOne("SELECT COUNT(*) as count FROM buy_purchase_orders po");
    $recordsTotal = $total['count'];

    if ($where && $params) {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id LEFT JOIN projects p ON po.project_id = p.id WHERE $where", $types, $params);
    } else {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_purchase_orders po");
    }
    $recordsFiltered = $filtered['count'];

    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';

    $sql = "SELECT po.*, s.name as supplier_name, p.project_name
            FROM buy_purchase_orders po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN projects p ON po.project_id = p.id
            WHERE $where
            ORDER BY po.created_at DESC LIMIT ?, ?";

    $pos = fetchAll($sql, $types, $params);

    $data = [];
    foreach ($pos as $po) {
        $statusClass = match($po['status']) {
            'draft' => 'secondary',
            'sent' => 'info',
            'acknowledged' => 'primary',
            'partial' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };

        $data[] = [
            htmlspecialchars($po['po_number']),
            htmlspecialchars($po['project_name'] ?? '-'),
            htmlspecialchars($po['supplier_name'] ?? '-'),
            date('d M Y', strtotime($po['order_date'])),
            '<span class="badge bg-' . $statusClass . '">' . ucfirst($po['status']) . '</span>',
            '<button class="btn btn-sm btn-info me-1" onclick="editPO(' . $po['id'] . ')"><i class="fas fa-edit"></i></button>' .
            '<button class="btn btn-sm btn-primary me-1" onclick="viewPOItems(' . $po['id'] . ')"><i class="fas fa-eye"></i></button>' .
            '<button class="btn btn-sm btn-danger" onclick="deletePO(' . $po['id'] . ')"><i class="fas fa-trash"></i></button>'
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
// GRN API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'grn') {

    // Create GRN (from PDF or manual)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
        try {
            $po_id = intval($_POST['po_id'] ?? 0);
            $supplier_id = intval($_POST['supplier_id'] ?? 0);
            $invoice_number = sanitize($_POST['invoice_number'] ?? '');
            $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
            $notes = sanitize($_POST['notes'] ?? '');
            $items = json_decode($_POST['items'] ?? '[]', true);

            if (empty($supplier_id) || empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
                exit;
            }

            // Generate GRN number
            $grn_number = 'GRN-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insert GRN
            $grn_id = insertAndGetId(
                "INSERT INTO buy_grn (grn_number, po_id, supplier_id, invoice_number, invoice_date, received_date, notes, status, created_by)
                 VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'draft', ?)",
                "iiissisi",
                [$grn_number, $po_id, $supplier_id, $invoice_number, $invoice_date, $notes, $_SESSION['user_id']]
            );

            if (!$grn_id['success']) {
                echo json_encode(['success' => false, 'message' => 'Failed to create GRN']);
                exit;
            }

            $new_grn_id = $grn_id['id'];

            // Insert GRN items and update inventory
            foreach ($items as $item) {
                $accepted_qty = floatval($item['accepted_qty'] ?? $item['quantity']);
                $rejected_qty = floatval($item['rejected_qty'] ?? 0);
                $rate = floatval($item['rate'] ?? 0);
                $amount = $accepted_qty * $rate;

                insertAndGetId(
                    "INSERT INTO buy_grn_items (grn_id, item_name, quantity, unit, accepted_qty, rejected_qty, rate, amount)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    "isssddds",
                    [$new_grn_id, $item['item_name'], $item['quantity'], $item['unit'], $accepted_qty, $rejected_qty, $rate, $amount]
                );

                // Update or create inventory (ONLY source of stock)
                $inv = fetchOne("SELECT id, current_stock, total_received FROM buy_inventory WHERE item_name = ?", "s", [$item['item_name']]);

                if ($inv) {
                    runQuery(
                        "UPDATE buy_inventory SET current_stock = current_stock + ?, total_received = total_received + ?, last_grn_id = ?, last_updated = NOW() WHERE id = ?",
                        "ddii",
                        [$accepted_qty, $accepted_qty, $new_grn_id, $inv['id']]
                    );
                } else {
                    insertAndGetId(
                        "INSERT INTO buy_inventory (item_name, unit, current_stock, total_received, rate) VALUES (?, ?, ?, ?, ?)",
                        "ssddd",
                        [$item['item_name'], $item['unit'], $accepted_qty, $accepted_qty, $rate]
                    );
                }
            }

            // Update PO status if linked
            if ($po_id > 0) {
                $po_items = fetchAll("SELECT SUM(quantity) as total_qty, SUM(received_qty) as received FROM buy_po_items WHERE po_id = ?", "i", [$po_id]);
                $grn_items = fetchAll("SELECT SUM(accepted_qty) as received FROM buy_grn_items WHERE grn_id = ?", "i", [$new_grn_id]);

                $total = $po_items[0]['total_qty'] ?? 0;
                $received = ($po_items[0]['received'] ?? 0) + ($grn_items[0]['received'] ?? 0);

                if ($received >= $total && $total > 0) {
                    runQuery("UPDATE buy_purchase_orders SET status = 'completed' WHERE id = ?", "i", [$po_id]);
                } else if ($received > 0) {
                    runQuery("UPDATE buy_purchase_orders SET status = 'partial' WHERE id = ?", "i", [$po_id]);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'GRN created successfully! Inventory updated.',
                'grn_id' => $new_grn_id,
                'grn_number' => $grn_number,
                'redirect' => 'bills/create.php?grn_id=' . $new_grn_id
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get GRN details
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'get') {
        $grn_id = intval($_POST['grn_id'] ?? 0);

        $grn = fetchOne("SELECT g.*, s.name as supplier_name, po.po_number
                        FROM buy_grn g
                        LEFT JOIN suppliers s ON g.supplier_id = s.id
                        LEFT JOIN buy_purchase_orders po ON g.po_id = po.id
                        WHERE g.id = ?", "i", [$grn_id]);

        $items = fetchAll("SELECT * FROM buy_grn_items WHERE grn_id = ?", "i", [$grn_id]);

        if ($grn) {
            $grn['items'] = $items;
            echo json_encode(['success' => true, 'data' => $grn]);
        } else {
            echo json_encode(['success' => false, 'message' => 'GRN not found']);
        }
        exit;
    }

    // List GRNs
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';

    $where = "1=1";
    $params = [];
    $types = '';

    if ($search) {
        $where .= " AND (g.grn_number LIKE ? OR g.invoice_number LIKE ? OR s.name LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = [$search_param, $search_param, $search_param];
        $types = 'sss';
    }

    $total = fetchOne("SELECT COUNT(*) as count FROM buy_grn g");
    $recordsTotal = $total['count'];

    if ($where && $params) {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_grn g LEFT JOIN suppliers s ON g.supplier_id = s.id WHERE $where", $types, $params);
    } else {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_grn g");
    }
    $recordsFiltered = $filtered['count'];

    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';

    $sql = "SELECT g.*, s.name as supplier_name, po.po_number
            FROM buy_grn g
            LEFT JOIN suppliers s ON g.supplier_id = s.id
            LEFT JOIN buy_purchase_orders po ON g.po_id = po.id
            WHERE $where
            ORDER BY g.created_at DESC LIMIT ?, ?";

    $grns = fetchAll($sql, $types, $params);

    $data = [];
    foreach ($grns as $grn) {
        $statusClass = $grn['status'] === 'completed' ? 'success' : 'secondary';

        $data[] = [
            htmlspecialchars($grn['grn_number']),
            htmlspecialchars($grn['po_number'] ?? '-'),
            htmlspecialchars($grn['supplier_name'] ?? '-'),
            htmlspecialchars($grn['invoice_number'] ?? '-'),
            date('d M Y', strtotime($grn['received_date'])),
            '<span class="badge bg-' . $statusClass . '">' . ucfirst($grn['status']) . '</span>',
            '<button class="btn btn-sm btn-info" onclick="viewGRN(' . $grn['id'] . ')"><i class="fas fa-eye"></i></button>'
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
// INVENTORY API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'inventory') {

    // List Inventory
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
        $isLow = $item['current_stock'] <= $item['min_stock_level'];
        $rowClass = $isLow ? 'table-warning' : '';

        $data[] = [
            htmlspecialchars($item['item_name']),
            htmlspecialchars($item['unit']),
            '<strong>' . number_format($item['total_received'], 2) . '</strong>',
            '<strong>' . number_format($item['current_stock'], 2) . '</strong>',
            number_format($item['min_stock_level'], 2),
            number_format($item['rate'], 2),
            $isLow ? '<span class="badge bg-warning text-dark">Low Stock</span>' : '<span class="badge bg-success">OK</span>'
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
// BILL API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'bill') {

    // Create Bill from GRN
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_from_grn') {
        try {
            $grn_id = intval($_POST['grn_id'] ?? 0);
            $supplier_id = intval($_POST['supplier_id'] ?? 0);
            $due_date = $_POST['due_date'] ?? null;
            $notes = sanitize($_POST['notes'] ?? '');

            if (empty($grn_id) || empty($supplier_id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            // Get GRN items
            $grn_items = fetchAll("SELECT * FROM buy_grn_items WHERE grn_id = ?", "i", [$grn_id]);

            if (empty($grn_items)) {
                echo json_encode(['success' => false, 'message' => 'No items in GRN']);
                exit;
            }

            // Calculate total
            $total_amount = 0;
            foreach ($grn_items as $item) {
                $total_amount += floatval($item['amount']);
            }

            // Generate bill number
            $bill_number = 'BILL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insert bill
            $bill_id = insertAndGetId(
                "INSERT INTO buy_bills (bill_number, grn_id, supplier_id, bill_date, due_date, total_amount, notes, created_by)
                 VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?)",
                "iiisddsi",
                [$bill_number, $grn_id, $supplier_id, $due_date, $total_amount, $notes, $_SESSION['user_id']]
            );

            if (!$bill_id['success']) {
                echo json_encode(['success' => false, 'message' => 'Failed to create bill']);
                exit;
            }

            // Insert bill items
            foreach ($grn_items as $item) {
                insertAndGetId(
                    "INSERT INTO buy_bill_items (bill_id, item_name, quantity, unit, rate, amount)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    "isssdd",
                    [$bill_id['id'], $item['item_name'], $item['accepted_qty'], $item['unit'], $item['rate'], $item['amount']]
                );
            }

            // Update GRN status
            runQuery("UPDATE buy_grn SET status = 'completed' WHERE id = ?", "i", [$grn_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Bill created successfully!',
                'bill_id' => $bill_id['id'],
                'bill_number' => $bill_number,
                'total_amount' => $total_amount,
                'redirect' => 'payments/create.php?bill_id=' . $bill_id['id']
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Create manual bill
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
        try {
            $supplier_id = intval($_POST['supplier_id'] ?? 0);
            $due_date = $_POST['due_date'] ?? null;
            $notes = sanitize($_POST['notes'] ?? '');
            $items = json_decode($_POST['items'] ?? '[]', true);

            if (empty($supplier_id) || empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
                exit;
            }

            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += floatval($item['quantity']) * floatval($item['rate']);
            }

            $bill_number = 'BILL-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $bill_id = insertAndGetId(
                "INSERT INTO buy_bills (bill_number, supplier_id, bill_date, due_date, total_amount, notes, created_by)
                 VALUES (?, ?, CURDATE(), ?, ?, ?, ?)",
                "iisddsi",
                [$bill_number, $supplier_id, $due_date, $total_amount, $notes, $_SESSION['user_id']]
            );

            foreach ($items as $item) {
                $amount = floatval($item['quantity']) * floatval($item['rate']);
                insertAndGetId(
                    "INSERT INTO buy_bill_items (bill_id, item_name, quantity, unit, rate, amount)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    "isssdd",
                    [$bill_id['id'], $item['item_name'], $item['quantity'], $item['unit'], $item['rate'], $amount]
                );
            }

            echo json_encode([
                'success' => true,
                'message' => 'Bill created successfully!',
                'bill_id' => $bill_id['id'],
                'bill_number' => $bill_number
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get bill
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'get') {
        $bill_id = intval($_POST['bill_id'] ?? 0);

        $bill = fetchOne("SELECT b.*, s.name as supplier_name
                         FROM buy_bills b
                         LEFT JOIN suppliers s ON b.supplier_id = s.id
                         WHERE b.id = ?", "i", [$bill_id]);

        $items = fetchAll("SELECT * FROM buy_bill_items WHERE bill_id = ?", "i", [$bill_id]);

        // Get payments
        $payments = fetchAll("SELECT * FROM buy_payments WHERE bill_id = ?", "i", [$bill_id]);

        if ($bill) {
            $bill['items'] = $items;
            $bill['payments'] = $payments;
            $bill['pending_amount'] = $bill['total_amount'] - $bill['paid_amount'];
            echo json_encode(['success' => true, 'data' => $bill]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
        }
        exit;
    }

    // List Bills
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';

    $where = "1=1";
    $params = [];
    $types = '';

    if ($search) {
        $where .= " AND (b.bill_number LIKE ? OR s.name LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = [$search_param, $search_param];
        $types = 'sss';
    }

    $total = fetchOne("SELECT COUNT(*) as count FROM buy_bills b");
    $recordsTotal = $total['count'];

    if ($where && $params) {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_bills b LEFT JOIN suppliers s ON b.supplier_id = s.id WHERE $where", $types, $params);
    } else {
        $filtered = fetchOne("SELECT COUNT(*) as count FROM buy_bills b");
    }
    $recordsFiltered = $filtered['count'];

    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';

    $sql = "SELECT b.*, s.name as supplier_name
            FROM buy_bills b
            LEFT JOIN suppliers s ON b.supplier_id = s.id
            WHERE $where
            ORDER BY b.created_at DESC LIMIT ?, ?";

    $bills = fetchAll($sql, $types, $params);

    $data = [];
    foreach ($bills as $bill) {
        $statusClass = match($bill['status']) {
            'pending' => 'warning',
            'partial' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            default => 'secondary'
        };
        $pending = $bill['total_amount'] - $bill['paid_amount'];

        $data[] = [
            htmlspecialchars($bill['bill_number']),
            htmlspecialchars($bill['supplier_name'] ?? '-'),
            number_format($bill['total_amount'], 2),
            number_format($pending, 2),
            date('d M Y', strtotime($bill['bill_date'])),
            '<span class="badge bg-' . $statusClass . '">' . ucfirst($bill['status']) . '</span>',
            '<button class="btn btn-sm btn-info me-1" onclick="viewBill(' . $bill['id'] . ')"><i class="fas fa-eye"></i></button>' .
            '<button class="btn btn-sm btn-primary" onclick="addPayment(' . $bill['id'] . ', ' . $pending . ')"><i class="fas fa-rupee-sign"></i></button>'
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
// PAYMENT API
// =====================================================
if (isset($_GET['module']) && $_GET['module'] === 'payment') {

    // Create payment
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
        try {
            $bill_id = intval($_POST['bill_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $payment_method = sanitize($_POST['payment_method'] ?? 'bank_transfer');
            $reference_number = sanitize($_POST['reference_number'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');

            if (empty($bill_id) || $amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
                exit;
            }

            // Check bill
            $bill = fetchOne("SELECT * FROM buy_bills WHERE id = ?", "i", [$bill_id]);
            if (!$bill) {
                echo json_encode(['success' => false, 'message' => 'Bill not found']);
                exit;
            }

            $pending = $bill['total_amount'] - $bill['paid_amount'];
            if ($amount > $pending) {
                echo json_encode(['success' => false, 'message' => 'Amount exceeds pending balance: ' . number_format($pending, 2)]);
                exit;
            }

            // Generate payment number
            $payment_number = 'PAY-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insert payment
            $payment_id = insertAndGetId(
                "INSERT INTO buy_payments (payment_number, bill_id, payment_date, amount, payment_method, reference_number, notes, created_by)
                 VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)",
                "sidisssi",
                [$payment_number, $bill_id, $amount, $payment_method, $reference_number, $notes, $_SESSION['user_id']]
            );

            // Update bill paid amount
            $new_paid = $bill['paid_amount'] + $amount;
            $new_status = 'partial';
            if ($new_paid >= $bill['total_amount']) {
                $new_status = 'paid';
            }

            runQuery(
                "UPDATE buy_bills SET paid_amount = ?, status = ?, updated_at = NOW() WHERE id = ?",
                "dsi",
                [$new_paid, $new_status, $bill_id]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Payment recorded successfully!',
                'payment_id' => $payment_id['id'],
                'payment_number' => $payment_number
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // List payments for a bill
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'list_by_bill') {
        $bill_id = intval($_POST['bill_id'] ?? 0);
        $payments = fetchAll("SELECT * FROM buy_payments WHERE bill_id = ? ORDER BY payment_date DESC", "i", [$bill_id]);
        echo json_encode(['success' => true, 'payments' => $payments]);
        exit;
    }

    // List all payments
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);

    $total = fetchOne("SELECT COUNT(*) as count FROM buy_payments");
    $recordsTotal = $total['count'];
    $recordsFiltered = $recordsTotal;

    $sql = "SELECT p.*, b.bill_number, s.name as supplier_name
            FROM buy_payments p
            LEFT JOIN buy_bills b ON p.bill_id = b.id
            LEFT JOIN suppliers s ON b.supplier_id = s.id
            ORDER BY p.created_at DESC LIMIT $start, $length";

    $payments = fetchAll($sql);

    $data = [];
    foreach ($payments as $pay) {
        $data[] = [
            htmlspecialchars($pay['payment_number']),
            htmlspecialchars($pay['bill_number']),
            htmlspecialchars($pay['supplier_name'] ?? '-'),
            number_format($pay['amount'], 2),
            date('d M Y', strtotime($pay['payment_date'])),
            ucfirst($pay['payment_method']),
            htmlspecialchars($pay['reference_number'] ?? '-')
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