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
    // Add Site Visit
    case 'add_site_visit':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $scheduled_date = $_POST['scheduled_date'] ?? '';
        $scheduled_time = $_POST['scheduled_time'] ?? '';
        $engineer_name = sanitize($_POST['engineer_name'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$enquiry_id || !$scheduled_date) {
            json_response(false, 'Required fields missing');
        }

        // Check if enquiry exists
        $enquiry = fetchOne("SELECT id FROM enquiries WHERE id = ?", "i", [$enquiry_id]);
        if (!$enquiry) {
            json_response(false, 'Enquiry not found');
        }

        $result = insertAndGetId(
            "INSERT INTO site_visits (enquiry_id, scheduled_date, scheduled_time, engineer_name, visit_notes) VALUES (?, ?, ?, ?, ?)",
            "issss",
            [$enquiry_id, $scheduled_date, $scheduled_time, $engineer_name, $notes]
        );

        if ($result['success']) {
            // Update enquiry status
            runQuery("UPDATE enquiries SET status = 'site_visit_scheduled' WHERE id = ?", "i", [$enquiry_id]);
            json_response(true, 'Site visit scheduled successfully!', [
                'site_visit_id' => $result['id'],
                'enquiry_id' => $enquiry_id
            ]);
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Get Site Visits
    case 'get_site_visits':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);

        if ($enquiry_id) {
            $visits = fetchAll("SELECT * FROM site_visits WHERE enquiry_id = ? ORDER BY scheduled_date DESC", "i", [$enquiry_id]);
        } else {
            $visits = fetchAll("SELECT sv.*, e.client_name FROM site_visits sv LEFT JOIN enquiries e ON sv.enquiry_id = e.id ORDER BY sv.scheduled_date DESC");
        }

        json_response(true, '', ['site_visits' => $visits]);
        break;

    // Update Site Visit
    case 'update_site_visit':
        $id = intval($_POST['id'] ?? 0);
        $scheduled_date = $_POST['scheduled_date'] ?? '';
        $scheduled_time = $_POST['scheduled_time'] ?? '';
        $engineer_name = sanitize($_POST['engineer_name'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        // $visit_completed = isset($_POST['visit_completed']) ? 1 : 0;
        $visit_completed = isset($_POST['visit_completed']) && $_POST['visit_completed'] == 1 ? 1 : 0;

        if (!$id || !$scheduled_date) {
            json_response(false, 'Required fields missing');
        }

        $result = runQuery(
            "UPDATE site_visits SET scheduled_date = ?, scheduled_time = ?, engineer_name = ?, visit_notes = ?, visit_completed = ? WHERE id = ?",
            "ssssii",
            [$scheduled_date, $scheduled_time, $engineer_name, $notes, $visit_completed, $id]
        );

        if ($result['success']) {
            json_response(true, 'Site visit updated successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Delete Site Visit
    case 'delete_site_visit':
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid site visit ID');
        }

        $result = runQuery("DELETE FROM site_visits WHERE id = ?", "i", [$id]);

        if ($result['success']) {
            json_response(true, 'Site visit deleted successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    default:
        json_response(false, 'Invalid action');
}