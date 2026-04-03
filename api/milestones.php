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
$total = fetchOne("SELECT COUNT(*) as count FROM project_milestones");
$recordsTotal = $total['count'];

// Filtered count
$where = "";
if ($search) {
    $search = sanitize($search);
    $where = " WHERE pm.phase_name LIKE '%$search%' OR p.project_name LIKE '%$search%'";
}

$filtered = fetchOne("SELECT COUNT(*) as count FROM project_milestones pm LEFT JOIN projects p ON pm.project_id = p.id $where");
$recordsFiltered = $filtered['count'];

// Get data
$sql = "SELECT pm.*, p.project_name FROM project_milestones pm LEFT JOIN projects p ON pm.project_id = p.id $where ORDER BY pm.id DESC LIMIT $start, $length";
$milestones = fetchAll($sql);

$data = [];
foreach ($milestones as $m) {
    $statusClass = '';

switch ($m['status']) {
    case 'pending':
        $statusClass = 'pending';
        break;

    case 'in_progress':
        $statusClass = 'issued';
        break;

    case 'completed':
        $statusClass = 'completed';
        break;

    default:
        $statusClass = 'pending';
        break;
}

    $actions = '<a href="../milestones/edit_milestone.php?id=' . $m['id'] . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>';

    $data[] = [
        $m['id'],
        htmlspecialchars($m['project_name'] ?? '-'),
        htmlspecialchars($m['phase_name'] ?? '-'),
        htmlspecialchars($m['description'] ?? '-'),
        $m['start_date'] ? date('d M Y', strtotime($m['start_date'])) : '-',
        $m['end_date'] ? date('d M Y', strtotime($m['end_date'])) : '-',
        '<span class="badge badge-' . $statusClass . '">' . ucfirst(str_replace('_', ' ', $m['status'])) . '</span>',
        $actions
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);