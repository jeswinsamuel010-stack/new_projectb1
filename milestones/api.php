<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function json_response($success, $message = '', $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

switch ($action) {
    // Create Milestone with Execution Plans
    case 'create_milestone':
        $project_id = intval($_POST['project_id'] ?? 0);
        $phase_name = sanitize($_POST['phase_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $status = sanitize($_POST['status'] ?? 'pending');

        // Validation
        if (!$project_id || empty($phase_name) || empty($start_date) || empty($end_date)) {
            json_response(false, 'Please fill all required fields');
        }

        $conn = getDB();
        $conn->begin_transaction();

        try {
            // Insert milestone
            $stmt = $conn->prepare("
                INSERT INTO project_milestones (project_id, phase_name, description, start_date, end_date, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssss", $project_id, $phase_name, $description, $start_date, $end_date, $status);
            $stmt->execute();
            $milestone_id = $stmt->insert_id;
            $stmt->close();

            $conn->commit();
            json_response(true, 'Milestone created successfully!', ['milestone_id' => $milestone_id]);

        } catch (Exception $e) {
            $conn->rollback();
            json_response(false, $e->getMessage());
        }
        break;

    // Update Milestone
    case 'update_milestone':
        $id = intval($_POST['id'] ?? 0);
        $phase_name = sanitize($_POST['phase_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $status = sanitize($_POST['status'] ?? 'pending');

        if (!$id || empty($phase_name) || empty($start_date) || empty($end_date)) {
            json_response(false, 'Please fill all required fields');
        }

        $result = runQuery(
            "UPDATE project_milestones SET phase_name = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?",
            "sssssi",
            [$phase_name, $description, $start_date, $end_date, $status, $id]
        );

        if ($result['success']) {
            json_response(true, 'Milestone updated successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Update Milestone Plans
    case 'update_plans':
        $milestone_id = intval($_POST['milestone_id'] ?? 0);
        $plans = json_decode($_POST['plans'] ?? '[]', true);

        if (!$milestone_id) {
            json_response(false, 'Invalid milestone');
        }

        $conn = getDB();
        $conn->begin_transaction();

        try {
            // Delete existing plans
            $stmt = $conn->prepare("DELETE FROM milestone_plans WHERE milestone_id = ?");
            $stmt->bind_param("i", $milestone_id);
            $stmt->execute();
            $stmt->close();

            // Insert new plans
            if (!empty($plans)) {
                $stmt = $conn->prepare("
                    INSERT INTO milestone_plans (milestone_id, plan_type, plan_number, work_description, expected_completion_date, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                foreach ($plans as $plan) {
                    $plan_type = $plan['plan_type'] ?? 'month';
                    $plan_number = intval($plan['plan_number'] ?? 1);
                    $work_description = sanitize($plan['work_description'] ?? '');
                    $expected_date = $plan['expected_completion_date'] ?? null;
                    $plan_status = $plan['status'] ?? 'pending';

                    if (!empty($work_description)) {
                        $stmt->bind_param("isissi", $milestone_id, $plan_type, $plan_number, $work_description, $expected_date, $plan_status);
                        $stmt->execute();
                    }
                }
                $stmt->close();
            }

            $conn->commit();
            json_response(true, 'Plans updated successfully!');

        } catch (Exception $e) {
            $conn->rollback();
            json_response(false, $e->getMessage());
        }
        break;

    // Get Milestone with Plans
    case 'get_milestone':
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid milestone ID');
        }

        $milestone = fetchOne("SELECT * FROM project_milestones WHERE id = ?", "i", [$id]);
        if (!$milestone) {
            json_response(false, 'Milestone not found');
        }

        // Get associated plans
        $plans = fetchAll("SELECT * FROM milestone_plans WHERE milestone_id = ? ORDER BY plan_type, plan_number", "i", [$id]);

        json_response(true, '', [
            'milestone' => $milestone,
            'plans' => $plans
        ]);
        break;

    // Get All Milestones
    case 'get_milestones':
        $project_id = intval($_POST['project_id'] ?? 0);

        if ($project_id) {
            $milestones = fetchAll("
                SELECT pm.*, p.project_name
                FROM project_milestones pm
                LEFT JOIN projects p ON pm.project_id = p.id
                WHERE pm.project_id = ?
                ORDER BY pm.start_date", "i", [$project_id]);
        } else {
            $milestones = fetchAll("
                SELECT pm.*, p.project_name
                FROM project_milestones pm
                LEFT JOIN projects p ON pm.project_id = p.id
                ORDER BY pm.start_date DESC");
        }

        // Add empty arrays for plans and updates
        foreach ($milestones as &$milestone) {
            $milestone['plans'] = [];
            $milestone['latest_update'] = null;
        }

        json_response(true, '', ['milestones' => $milestones]);
        break;

    // Delete Milestone
    case 'delete_milestone':
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid milestone ID');
        }

        $result = runQuery("DELETE FROM project_milestones WHERE id = ?", "i", [$id]);

        if ($result['success']) {
            json_response(true, 'Milestone deleted successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Delete Individual Plan
    case 'delete_plan':
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid plan ID');
        }

        $result = runQuery("DELETE FROM milestone_plans WHERE id = ?", "i", [$id]);

        if ($result['success']) {
            json_response(true, 'Plan deleted successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Update Individual Plan
    case 'update_plan':
        $id = intval($_POST['id'] ?? 0);
        $work_description = sanitize($_POST['work_description'] ?? '');
        $expected_completion_date = $_POST['expected_completion_date'] ?? null;
        $status = sanitize($_POST['status'] ?? 'pending');

        if (!$id || empty($work_description)) {
            json_response(false, 'Please fill all required fields');
        }

        $result = runQuery(
            "UPDATE milestone_plans SET work_description = ?, expected_completion_date = ?, status = ? WHERE id = ?",
            "sssi",
            [$work_description, $expected_completion_date, $status, $id]
        );

        if ($result['success']) {
            json_response(true, 'Plan updated successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    default:
        json_response(false, 'Invalid action');
}