<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'];
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// Get total count
$total = fetchOne("SELECT COUNT(*) as count FROM project_payments");
$recordsTotal = $total['count'];

// Filtered count
$where = "";
if ($search) {
    $search = sanitize($search);
    $where = " WHERE pp.stage_name LIKE '%$search%' OR p.project_name LIKE '%$search%'";
}

$filtered = fetchOne("SELECT COUNT(*) as count FROM project_payments pp LEFT JOIN projects p ON pp.project_id = p.id $where");
$recordsFiltered = $filtered['count'];

// Get data
$sql = "SELECT pp.*, p.project_name FROM project_payments pp LEFT JOIN projects p ON pp.project_id = p.id $where ORDER BY pp.id DESC LIMIT $start, $length";
$payments = fetchAll($sql);

$typeLabels = [
    'advance' => 'Advance',
    'mid_work' => 'Mid-Work',
    'completion' => 'Completion'
];

$data = [];
foreach ($payments as $p) {
    $statusClass = $p['status'] == 'paid' ? 'success' : 'warning';
    $actions = '<a href="../payments/edit_payment.php?id=' . $p['id'] . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>';

    $data[] = [
        $p['id'],
        htmlspecialchars($p['project_name'] ?? '-'),
        htmlspecialchars($p['stage_name']),
        $typeLabels[$p['payment_type']] ?? $p['payment_type'],
        number_format($p['amount'], 2),
        $p['payment_date'] ? date('d M Y', strtotime($p['payment_date'])) : '-',
        '<span class="badge badge-' . $statusClass . '">' . ucfirst($p['status']) . '</span>',
        $actions
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);