<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// Total count
$total = fetchOne("SELECT COUNT(*) as count FROM users");
$recordsTotal = $total['count'];

// Filtered
$where = "";
if ($search) {
    $search = sanitize($search);
    $where = " WHERE username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%'";
}
$filtered = fetchOne("SELECT COUNT(*) as count FROM users $where");
$recordsFiltered = $filtered['count'];

// Data
$sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT $start, $length";
$users = fetchAll($sql);

$data = [];
foreach ($users as $u) {
    $data[] = [
        $u['id'],
        htmlspecialchars($u['username']),
        htmlspecialchars($u['full_name']),
        htmlspecialchars($u['email']),
        ucfirst(str_replace('_', ' ', $u['role'])),
        '<span class="badge badge-' . $u['status'] . '">' . ucfirst($u['status']) . '</span>',
        '<button class="btn btn-warning btn-sm" onclick="openEditUser(' . $u['id'] . ', \'' . htmlspecialchars($u['username']) . '\', \'' . htmlspecialchars($u['full_name']) . '\', \'' . htmlspecialchars($u['email']) . '\', \'' . $u['role'] . '\', \'' . $u['status'] . '\')"><i class="fas fa-edit"></i></button>'
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);