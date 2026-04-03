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
$type = $_POST['type'] ?? 'pending'; // 'pending' or 'processed'

// Build WHERE clause
$where = "mr.status";
if ($type == 'pending') {
    $where .= " = 'pending'";
} else {
    $where .= " IN ('approved', 'rejected')";
}

if ($search) {
    $search = sanitize($search);
    $where .= " AND (mr.item_name LIKE '%$search%' OR p.project_name LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

// Get total count
$total = fetchOne("SELECT COUNT(*) as count FROM material_requests mr LEFT JOIN projects p ON mr.project_id = p.id LEFT JOIN users u ON mr.requested_by = u.id WHERE $where");
$recordsTotal = $total['count'];
$recordsFiltered = $recordsTotal;

// Get data based on type
if ($type == 'pending') {
    $sql = "SELECT mr.*, p.project_name, u.full_name as requested_by_name
            FROM material_requests mr
            LEFT JOIN projects p ON mr.project_id = p.id
            LEFT JOIN users u ON mr.requested_by = u.id
            WHERE $where
            ORDER BY mr.created_at ASC LIMIT $start, $length";
    $requests = fetchAll($sql);

    $data = [];
    foreach ($requests as $req) {
        $actions = '<button class="btn btn-success btn-sm" onclick="openAction(' . $req['id'] . ', \'approve\')"><i class="fas fa-check"></i></button> ' .
                   '<button class="btn btn-danger btn-sm" onclick="openAction(' . $req['id'] . ', \'reject\')"><i class="fas fa-times"></i></button>';

        $data[] = [
            $req['id'],
            htmlspecialchars($req['project_name'] ?? '-'),
            htmlspecialchars($req['item_name']),
            $req['quantity'] . ' ' . $req['unit'],
            htmlspecialchars($req['reason']),
            htmlspecialchars($req['requested_by_name'] ?? '-'),
            date('d M Y', strtotime($req['created_at'])),
            $actions
        ];
    }
} else {
    $sql = "SELECT mr.*, p.project_name, u.full_name as requested_by_name,
             a.full_name as approved_by_name
             FROM material_requests mr
             LEFT JOIN projects p ON mr.project_id = p.id
             LEFT JOIN users u ON mr.requested_by = u.id
             LEFT JOIN users a ON mr.approved_by = a.id
             WHERE $where
             ORDER BY mr.updated_at DESC LIMIT $start, $length";
    $requests = fetchAll($sql);

    $data = [];
    foreach ($requests as $req) {
        $data[] = [
            $req['id'],
            htmlspecialchars($req['project_name'] ?? '-'),
            htmlspecialchars($req['item_name']),
            $req['quantity'] . ' ' . $req['unit'],
            '<span class="badge badge-' . $req['status'] . '">' . ucfirst($req['status']) . '</span>',
            htmlspecialchars($req['approved_by_name'] ?? '-'),
            htmlspecialchars($req['approval_remarks'] ?? '-'),
            date('d M Y', strtotime($req['updated_at']))
        ];
    }
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);