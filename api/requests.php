<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// Build query based on role
if ($role == 'admin') {
    $where = "";
    if ($search) {
        $search = sanitize($search);
        $where = " WHERE mr.item_name LIKE '%$search%' OR p.project_name LIKE '%$search%'";
    }
    $total = fetchOne("SELECT COUNT(*) as count FROM material_requests mr LEFT JOIN projects p ON mr.project_id = p.id");
} else {
    $where = " WHERE mr.requested_by = $user_id";
    if ($search) {
        $search = sanitize($search);
        $where .= " AND (mr.item_name LIKE '%$search%' OR p.project_name LIKE '%$search%')";
    }
    $total = fetchOne("SELECT COUNT(*) as count FROM material_requests mr LEFT JOIN projects p ON mr.project_id = p.id WHERE mr.requested_by = $user_id");
}

$recordsTotal = $total['count'];
$recordsFiltered = $recordsTotal;

$sql = "SELECT mr.*, p.project_name
        FROM material_requests mr
        LEFT JOIN projects p ON mr.project_id = p.id
        $where
        ORDER BY mr.created_at DESC LIMIT $start, $length";

$requests = fetchAll($sql);

$data = [];
foreach ($requests as $r) {
    $data[] = [
        $r['id'],
        htmlspecialchars($r['project_name'] ?? '-'),
        htmlspecialchars($r['item_name']),
        $r['quantity'] . ' ' . $r['unit'],
        htmlspecialchars($r['reason']),
        '<span class="badge badge-' . $r['status'] . '">' . ucfirst($r['status']) . '</span>',
        date('d M Y', strtotime($r['created_at']))
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);