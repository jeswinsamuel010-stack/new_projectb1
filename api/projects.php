<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function json_response($success, $message = '', $data = [])
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// If action is provided, handle as API endpoint (not DataTables)
if (!empty($action)) {
    $role = $_SESSION['role'];

    switch ($action) {
        // Get single project
        case 'get_project':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                json_response(false, 'Invalid project ID');
            }

            $project = fetchOne("SELECT * FROM projects WHERE id = ?", "i", [$id]);
            if (!$project) {
                json_response(false, 'Project not found');
            }

            json_response(true, '', ['project' => $project]);
            break;

        // Update milestone status with remarks
        case 'update_milestone_status':
            $milestone_id = intval($_POST['milestone_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'pending');
            $remarks = sanitize($_POST['remarks'] ?? '');
            $completion_date = $_POST['completion_date'] ?? null;
            $user_id = intval($_POST['user_id'] ?? 0);

            if (!$milestone_id || !$user_id) {
                json_response(false, 'Invalid data');
            }

            $conn = getDB();
            $conn->begin_transaction();

        case 'delete_project':

            if ($role != 'admin') {
                json_response(false, 'Permission denied');
            }

            $id = intval($_POST['id'] ?? 0);

            if (!$id) {
                json_response(false, 'Invalid Project ID');
            }

            $conn = getDB();

            try {

                // Optional: delete milestones first
                $stmt = $conn->prepare("DELETE FROM project_milestones WHERE project_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                // Delete project
                $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                json_response(true, 'Project deleted successfully');
            } catch (Exception $e) {
                json_response(false, $e->getMessage());
            }

            break;

            try {
                // Update milestone status
                $stmt = $conn->prepare("UPDATE project_milestones SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $milestone_id);
                $stmt->execute();
                $stmt->close();

                // Insert update record
                $stmt = $conn->prepare("INSERT INTO milestone_updates (milestone_id, updated_by, status, remarks, completion_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $milestone_id, $user_id, $status, $remarks, $completion_date);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                json_response(true, 'Status updated successfully!');
            } catch (Exception $e) {
                $conn->rollback();
                json_response(false, $e->getMessage());
            }
            break;

        // Get milestone updates history
        case 'get_milestone_updates':
            $milestone_id = intval($_POST['milestone_id'] ?? 0);

            if (!$milestone_id) {
                json_response(false, 'Invalid milestone ID');
            }

            $updates = fetchAll("
            SELECT mu.*, u.full_name as updated_by_name
            FROM milestone_updates mu
            LEFT JOIN users u ON mu.updated_by = u.id
            WHERE mu.milestone_id = ?
            ORDER BY mu.created_at DESC
        ", "i", [$milestone_id]);

            json_response(true, '', ['updates' => $updates]);
            break;

        default:
            // No action - return DataTables response
            break;
    }
}

// DataTables endpoint (when no action is provided)
$role = $_SESSION['role'];
$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

// Get total count
$total = fetchOne("SELECT COUNT(*) as count FROM projects");
$recordsTotal = $total['count'];

// Filtered count
$where = "";
if ($search) {
    $search = sanitize($search);
    $where = " WHERE project_name LIKE '%$search%' OR client_name LIKE '%$search%'";
}

$filtered = fetchOne("SELECT COUNT(*) as count FROM projects $where");
$recordsFiltered = $filtered['count'];

// Get data
$sql = "SELECT * FROM projects $where ORDER BY created_at DESC LIMIT $start, $length";
$projects = fetchAll($sql);

$data = [];
foreach ($projects as $p) {
    $actions = '';
    // if ($role == 'admin') {
    //     $actions = '<a href="../projects/edit.php?id=' . $p['id'] . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a> ';
    // }
    if ($role == 'admin') {
        $actions = '<a href="../projects/edit.php?id=' . $p['id'] . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a> ';

        $actions .= '<button class="btn btn-danger btn-sm deleteProject" data-id="' . $p['id'] . '">
                    <i class="fas fa-trash"></i>
                 </button> ';
    }
    $actions .= '<a href="../projects/view.php?id=' . $p['id'] . '" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>';

    $data[] = [
        $p['id'],
        htmlspecialchars($p['project_name']),
        htmlspecialchars($p['client_name']),
        number_format($p['project_value'], 2),
        date('d M Y', strtotime($p['start_date'])),
        '<span class="badge badge-' . $p['status'] . '">' . ucfirst($p['status']) . '</span>',
        $actions
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data
]);
