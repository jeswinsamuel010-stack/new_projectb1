<?php
// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(0);

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

// Handle GET - List/Search suppliers
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'active';

    $where = "WHERE status = ?";
    $params = [$status];
    $types = 's';

    if ($search) {
        $where .= " AND (name LIKE ? OR contact_person LIKE ? OR phone LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sss';
    }

    $sql = "SELECT * FROM suppliers $where ORDER BY name ASC LIMIT 50";
    $suppliers = fetchAll($sql, $types, $params);

    echo json_encode([
        'success' => true,
        'suppliers' => $suppliers
    ]);
    exit;
}

// Handle POST - Create/Update supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create supplier
    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $contact_person = sanitize($_POST['contact_person'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (empty($name)) {
            jsonResponse('error', 'Supplier name is required');
        }

        // Check if exists - return existing ID if found
        $existing = fetchOne("SELECT id FROM suppliers WHERE name = ?", "s", [$name]);
        if ($existing) {
            jsonResponse('success', 'Supplier found', ['supplier_id' => $existing['id']]);
        }

        $result = insertAndGetId(
            "INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)",
            "sssss",
            [$name, $contact_person, $phone, $email, $address]
        );

        if ($result['success']) {
            jsonResponse('success', 'Supplier created successfully', ['supplier_id' => $result['id']]);
        } else {
            jsonResponse('error', 'Failed to create supplier');
        }
    }

    // Match supplier by name (for PDF import)
    if ($action === 'match') {
        $supplier_name = sanitize($_POST['supplier_name'] ?? '');

        if (empty($supplier_name)) {
            echo json_encode(['success' => true, 'found' => false, 'message' => 'No supplier name provided']);
            exit;
        }

        // Exact match first
        $supplier = fetchOne("SELECT * FROM suppliers WHERE name = ? AND status = 'active'", "s", [$supplier_name]);

        if ($supplier) {
            echo json_encode([
                'success' => true,
                'found' => true,
                'supplier' => $supplier
            ]);
            exit;
        }

        // Fuzzy match (partial)
        $search_param = '%' . $supplier_name . '%';
        $suppliers = fetchAll("SELECT * FROM suppliers WHERE name LIKE ? AND status = 'active' LIMIT 5", "s", [$search_param]);

        if (!empty($suppliers)) {
            echo json_encode([
                'success' => true,
                'found' => true,
                'exact' => false,
                'suggestions' => $suppliers
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'found' => false,
            'message' => 'No matching supplier found'
        ]);
        exit;
    }

    // Update supplier
    if ($action === 'update') {
        $supplier_id = intval($_POST['supplier_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $contact_person = sanitize($_POST['contact_person'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (empty($supplier_id) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $result = runQuery(
            "UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?",
            "sssssi",
            [$name, $contact_person, $phone, $email, $address, $supplier_id]
        );

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update supplier']);
        }
        exit;
    }

    // Delete supplier
    if ($action === 'delete') {
        $supplier_id = intval($_POST['supplier_id'] ?? 0);

        if (empty($supplier_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid supplier ID']);
            exit;
        }

        $result = runQuery("UPDATE suppliers SET status = 'inactive' WHERE id = ?", "i", [$supplier_id]);

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete supplier']);
        }
        exit;
    }
}