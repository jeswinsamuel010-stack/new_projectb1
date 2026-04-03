<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle GRN creation from PDF import
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create GRN from PDF data
    if ($action === 'create_from_pdf') {
        try {
            $supplier_id = intval($_POST['supplier_id'] ?? 0);
            $supplier_name = sanitize($_POST['supplier_name'] ?? '');
            $product_name = sanitize($_POST['product_name'] ?? '');
            $quantity = floatval($_POST['quantity'] ?? 0);
            $unit = sanitize($_POST['unit'] ?? '');
            $invoice_number = sanitize($_POST['invoice_number'] ?? '');
            $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
            $po_id = intval($_POST['po_id'] ?? 0);
            $remarks = sanitize($_POST['remarks'] ?? '');

            // Validation
            if (empty($supplier_name) || empty($product_name) || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
                exit;
            }

            // Get material_request_id from PO if provided
            $material_request_id = 0;
            if ($po_id > 0) {
                $po = fetchOne("SELECT material_request_id FROM purchase_orders WHERE id = ?", "i", [$po_id]);
                if ($po) {
                    $material_request_id = $po['material_request_id'];
                }
            }

            // Try to find matching material request by product name
            if ($material_request_id == 0) {
                $mr = fetchOne("SELECT id, quantity FROM material_requests WHERE item_name LIKE ? LIMIT 1", "s", ["%" . $product_name . "%"]);
                if ($mr) {
                    $material_request_id = $mr['id'];
                }
            }

            // If still no PO, we can still create GRN without PO link
            $accepted_qty = $quantity;
            $rejected_qty = 0;

            // Insert GRN (Goods Receipt)
            $sql = "INSERT INTO goods_receipts (purchase_order_id, material_request_id, invoice_number, invoice_date, received_date, received_quantity, accepted_quantity, rejected_quantity, remarks, received_by)
                    VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)";

            $result = insertAndGetId($sql, "iissdddsi", [
                $po_id,
                $material_request_id,
                $invoice_number,
                $invoice_date,
                $quantity,
                $accepted_qty,
                $rejected_qty,
                $remarks,
                $_SESSION['user_id']]
            );

            if (!$result['success']) {
                echo json_encode(['success' => false, 'message' => 'Failed to create GRN: ' . $result['message']]);
                exit;
            }

            $grn_id = $result['id'];

            // Update inventory stock
            $inv = fetchOne("SELECT id, current_stock FROM inventory WHERE item_name LIKE ?", "s", ["%" . $product_name . "%"]);

            if ($inv) {
                // Update existing inventory
                $new_stock = $inv['current_stock'] + $accepted_qty;
                runQuery("UPDATE inventory SET current_stock = ?, updated_at = NOW() WHERE id = ?", "di", [$new_stock, $inv['id']]);
            } else {
                // Create new inventory item
                insertAndGetId(
                    "INSERT INTO inventory (item_name, unit, current_stock, min_stock_level, rate) VALUES (?, ?, ?, 0, 0)",
                    "ssd",
                    [$product_name, $unit, $accepted_qty]
                );
            }

            // Update PO status if linked
            if ($po_id > 0) {
                $po_data = fetchOne("SELECT mr.quantity FROM material_requests mr WHERE mr.id = ?", "i", [$material_request_id]);
                if ($po_data) {
                    if ($accepted_qty >= $po_data['quantity']) {
                        runQuery("UPDATE purchase_orders SET status = 'completed', updated_at = NOW() WHERE id = ?", "i", [$po_id]);
                    } else {
                        runQuery("UPDATE purchase_orders SET status = 'partial', updated_at = NOW() WHERE id = ?", "i", [$po_id]);
                    }
                }

                // Update material request status
                if ($material_request_id > 0) {
                    runQuery("UPDATE material_requests SET status = 'received', updated_at = NOW() WHERE id = ?", "i", [$material_request_id]);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'GRN created successfully!',
                'grn_id' => $grn_id,
                'inventory_updated' => true
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Get GRN details
    if ($action === 'get') {
        $grn_id = intval($_POST['grn_id'] ?? 0);

        if (empty($grn_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid GRN ID']);
            exit;
        }

        $sql = "SELECT gr.*, mr.item_name, mr.unit, po.po_number, p.project_name
                FROM goods_receipts gr
                LEFT JOIN material_requests mr ON gr.material_request_id = mr.id
                LEFT JOIN purchase_orders po ON gr.purchase_order_id = po.id
                LEFT JOIN projects p ON mr.project_id = p.id
                WHERE gr.id = ?";

        $grn = fetchOne($sql, "i", [$grn_id]);

        if ($grn) {
            echo json_encode(['success' => true, 'data' => $grn]);
        } else {
            echo json_encode(['success' => false, 'message' => 'GRN not found']);
        }
        exit;
    }

    // List GRNs
    if ($action === 'list') {
        $draw = intval($_POST['draw'] ?? 1);
        $start = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $search = $_POST['search']['value'] ?? '';

        $where = "";
        $params = [];
        $types = '';

        if ($search) {
            $search_param = '%' . $search . '%';
            $where = " WHERE gr.invoice_number LIKE ? OR po.po_number LIKE ? OR mr.item_name LIKE ?";
            $params = [$search_param, $search_param, $search_param];
            $types = 'sss';
        }

        // Total count
        $total = fetchOne("SELECT COUNT(*) as count FROM goods_receipts gr");
        $recordsTotal = $total['count'];

        // Filtered count
        if ($where) {
            $filtered = fetchOne("SELECT COUNT(*) as count FROM goods_receipts gr LEFT JOIN material_requests mr ON gr.material_request_id = mr.id LEFT JOIN purchase_orders po ON gr.purchase_order_id = po.id $where", $types, $params);
        } else {
            $filtered = fetchOne("SELECT COUNT(*) as count FROM goods_receipts gr");
        }
        $recordsFiltered = $filtered['count'];

        // Get data
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $sql = "SELECT gr.*, mr.item_name, mr.unit, po.po_number
                FROM goods_receipts gr
                LEFT JOIN material_requests mr ON gr.material_request_id = mr.id
                LEFT JOIN purchase_orders po ON gr.purchase_order_id = po.id
                $where
                ORDER BY gr.created_at DESC LIMIT ?, ?";

        $grns = fetchAll($sql, $types, $params);

        $data = [];
        foreach ($grns as $grn) {
            $data[] = [
                $grn['id'],
                $grn['invoice_number'] ?: 'N/A',
                $grn['po_number'] ?: '-',
                htmlspecialchars($grn['item_name'] ?? '-'),
                $grn['accepted_quantity'] . ' ' . ($grn['unit'] ?: ''),
                date('d M Y', strtotime($grn['received_date'])),
                $grn['created_at']
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

    // Get available POs for linking
    if ($action === 'get_pos') {
        $search = $_GET['search'] ?? '';

        $where = "WHERE po.status IN ('ordered', 'partial')";
        $params = [];
        $types = '';

        if ($search) {
            $where .= " AND (po.po_number LIKE ? OR mr.item_name LIKE ? OR po.supplier_name LIKE ?)";
            $search_param = '%' . $search . '%';
            $params = [$search_param, $search_param, $search_param];
            $types = 'sss';
        }

        $sql = "SELECT po.id, po.po_number, po.supplier_name, mr.item_name, mr.quantity as req_qty, mr.unit
                FROM purchase_orders po
                LEFT JOIN material_requests mr ON po.material_request_id = mr.id
                $where
                ORDER BY po.created_at DESC LIMIT 20";

        $pos = fetchAll($sql, $types, $params);

        echo json_encode([
            'success' => true,
            'purchase_orders' => $pos
        ]);
        exit;
    }
}

// Default: Return error for invalid requests
echo json_encode(['error' => 'Invalid request']);