<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get Dashboard Stats
if ($action === 'stats') {
    $role = $_SESSION['role'];
    $stats = [];

    // Active Projects
    $stats['active_projects'] = fetchOne("SELECT COUNT(*) as c FROM projects WHERE status = 'active'")['c'] ?? 0;

    // Total Quotations
    $stats['total_quotations'] = fetchOne("SELECT COUNT(*) as c FROM project_quotations")['c'] ?? 0;

    // Pending Approvals (from user_material_requests)
    $stats['pending_approvals'] = fetchOne("SELECT COUNT(*) as c FROM user_material_requests WHERE status = 'pending'")['c'] ?? 0;

    // Additional stats based on role
    if ($role == 'admin') {
        $stats['total_users'] = fetchOne("SELECT COUNT(*) as c FROM users WHERE status = 'active'")['c'] ?? 0;
    }

    // Inventory items
    $stats['inventory_items'] = fetchOne("SELECT COUNT(*) as c FROM buy_inventory")['c'] ?? 0;

    // Low stock
    $stats['low_stock'] = fetchOne("SELECT COUNT(*) as c FROM buy_inventory WHERE current_stock <= min_stock_level")['c'] ?? 0;

    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

// Get Active Projects
if ($action === 'active_projects') {
    $projects = fetchAll("
        SELECT id, project_name, client_name, start_date, status, project_value
        FROM projects
        WHERE status = 'active'
        ORDER BY created_at DESC
        LIMIT 10
    ");

    $data = [];
    foreach ($projects as $p) {
        $data[] = [
            'id' => $p['id'],
            'project_name' => htmlspecialchars($p['project_name']),
            'client_name' => htmlspecialchars($p['client_name']),
            'start_date' => date('d M Y', strtotime($p['start_date'])),
            'status' => $p['status'],
            'project_value' => number_format($p['project_value'], 2)
        ];
    }

    echo json_encode([
        'success' => true,
        'projects' => $data,
        'count' => count($data)
    ]);
    exit;
}

// Get Latest Quotations
if ($action === 'latest_quotations') {
    $quotations = fetchAll("
        SELECT id, quotation_number, client_name, project_title, estimated_amount, status, created_at
        FROM project_quotations
        ORDER BY created_at DESC
        LIMIT 10
    ");

    $data = [];
    foreach ($quotations as $q) {
        $statusClass = match($q['status']) {
            'Approved', 'Converted' => 'success',
            'Rejected' => 'danger',
            'Sent' => 'warning',
            default => 'secondary'
        };

        $data[] = [
            'id' => $q['id'],
            'quotation_number' => htmlspecialchars($q['quotation_number']),
            'client_name' => htmlspecialchars($q['client_name']),
            'project_title' => htmlspecialchars($q['project_title'] ?? 'N/A'),
            'estimated_amount' => number_format($q['estimated_amount'], 2),
            'status' => $q['status'],
            'status_class' => $statusClass,
            'created_at' => date('d M Y', strtotime($q['created_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'quotations' => $data,
        'count' => count($data)
    ]);
    exit;
}

// Get Pending Approvals
if ($action === 'pending_approvals') {
    $approvals = fetchAll("
        SELECT r.id, r.request_number, r.item_name, r.quantity, r.unit, r.status, r.created_at,
               p.project_name, u.full_name as requested_by_name
        FROM user_material_requests r
        LEFT JOIN projects p ON r.project_id = p.id
        LEFT JOIN users u ON r.requested_by = u.id
        WHERE r.status = 'pending'
        ORDER BY r.created_at DESC
        LIMIT 10
    ");

    $data = [];
    foreach ($approvals as $a) {
        $data[] = [
            'id' => $a['id'],
            'request_number' => htmlspecialchars($a['request_number']),
            'project_name' => htmlspecialchars($a['project_name'] ?? 'N/A'),
            'item_name' => htmlspecialchars($a['item_name']),
            'quantity' => $a['quantity'] . ' ' . $a['unit'],
            'requested_by' => htmlspecialchars($a['requested_by_name']),
            'created_at' => date('d M Y', strtotime($a['created_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'approvals' => $data,
        'count' => count($data)
    ]);
    exit;
}

// Default - Return error
echo json_encode(['error' => 'Invalid action']);