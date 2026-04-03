<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// Build WHERE clause with prepared statements
$where = "";
$params = [];
$types = '';

if ($search) {
    $search_param = '%' . $search . '%';
    $where = " WHERE b.bill_number LIKE ? OR p.project_name LIKE ?";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

// Total
$total = fetchOne("SELECT COUNT(*) as count FROM bills b LEFT JOIN projects p ON b.project_id = p.id");
$recordsTotal = $total['count'];

// Filtered
$filtered = fetchOne("SELECT COUNT(*) as count FROM bills b LEFT JOIN projects p ON b.project_id = p.id $where", $types, $params);
$recordsFiltered = $filtered['count'];

// Data
$params[] = $start;
$params[] = $length;
$types .= 'ii';

$sql = "SELECT b.*, p.project_name
        FROM bills b
        LEFT JOIN projects p ON b.project_id = p.id
        $where
        ORDER BY b.created_at DESC
        LIMIT ?, ?";

$bills = fetchAll($sql, $types, $params);

$data = [];
foreach ($bills as $bill) {
    $actions = '';
    if ($bill['status'] == 'pending') {
        $actions .= '<button class="btn btn-success btn-sm" onclick="updateBillStatus(' . $bill['id'] . ', \'paid\')"><i class="fas fa-check"></i></button> ';
    }
    $actions .= '<a href="../bills/view.php?id=' . $bill['id'] . '" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>';

    $data[] = [
        htmlspecialchars($bill['bill_number']),
        htmlspecialchars($bill['project_name']),
        '<strong>' . number_format($bill['amount'], 2) . '</strong>',
        date('d M Y', strtotime($bill['bill_date'])),
        '<span class="badge badge-' . $bill['status'] . '">' . ucfirst($bill['status']) . '</span>',
        $actions
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);