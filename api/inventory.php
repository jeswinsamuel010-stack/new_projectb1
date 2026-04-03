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

// Total
$total = fetchOne("SELECT COUNT(*) as count FROM inventory");
$recordsTotal = $total['count'];

// Filtered - using prepared statements for security
$where = "";
$params = [];
$types = '';

if ($search) {
    $search_param = '%' . $search . '%';
    $where = " WHERE item_name LIKE ?";
    $params[] = $search_param;
    $types .= 's';
}

$filtered = fetchOne("SELECT COUNT(*) as count FROM inventory $where", $types, $params);
$recordsFiltered = $filtered['count'];

// Data - using prepared statements
$params[] = $start;
$params[] = $length;
$types .= 'ii';

$sql = "SELECT * FROM inventory $where ORDER BY item_name ASC LIMIT ?, ?";
$items = fetchAll($sql, $types, $params);

$data = [];
foreach ($items as $item) {
    $is_low = $item['current_stock'] <= $item['min_stock_level'];
    $row_style = $is_low ? 'background: #fff3cd;' : '';

    $data[] = [
        $item['id'],
        htmlspecialchars($item['item_name']),
        htmlspecialchars($item['unit']),
        '<strong>' . $item['current_stock'] . '</strong>',
        $item['min_stock_level'],
        number_format($item['rate'], 2),
        $is_low ? '<span class="badge badge-rejected">Low Stock</span>' : '<span class="badge badge-active">OK</span>',
        '<button class="btn btn-success btn-sm" onclick="openUpdateStock(' . $item['id'] . ', \'' . htmlspecialchars($item['item_name']) . '\', \'add\')"><i class="fas fa-plus"></i></button> ' .
        '<button class="btn btn-danger btn-sm" onclick="openUpdateStock(' . $item['id'] . ', \'' . htmlspecialchars($item['item_name']) . '\', \'remove\')"><i class="fas fa-minus"></i></button>'
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);