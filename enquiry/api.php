<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

$action = $_POST['action'] ?? $_GET['action'] ?? '';


function json_response($success, $message = '', $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

switch ($action) {
    // Create new enquiry
    case 'create_enquiry':
        $client_name = sanitize($_POST['client_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $project_type = sanitize($_POST['project_type'] ?? '');
        $site_location = sanitize($_POST['site_location'] ?? '');

        if (empty($client_name) || empty($phone) || empty($project_type) || empty($site_location)) {
            json_response(false, 'Please fill in all required fields');
        }

        $result = insertAndGetId(
            "INSERT INTO enquiries (client_name, phone, email, project_type, site_location, status, created_by) VALUES (?, ?, ?, ?, ?, 'new', ?)",
            "sssssi",
            [$client_name, $phone, $email, $project_type, $site_location, $_SESSION['user_id']]
        );

        if ($result['success']) {
            json_response(true, 'Enquiry created successfully!', [
                'id' => $result['id'],
                'enquiry_id' => $result['id']
            ]);
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Update enquiry
    case 'update_enquiry':
        $id = intval($_POST['id'] ?? 0);
        $client_name = sanitize($_POST['client_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $project_type = sanitize($_POST['project_type'] ?? '');
        $site_location = sanitize($_POST['site_location'] ?? '');
        $status = sanitize($_POST['status'] ?? 'new');

        if (!$id || empty($client_name) || empty($phone) || empty($project_type) || empty($site_location)) {
            json_response(false, 'Please fill in all required fields');
        }

        $result = runQuery(
            "UPDATE enquiries SET client_name = ?, phone = ?, email = ?, project_type = ?, site_location = ?, status = ? WHERE id = ?",
            "ssssssi",
            [$client_name, $phone, $email, $project_type, $site_location, $status, $id]
        );

        if ($result['success']) {
            json_response(true, 'Enquiry updated successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Delete enquiry
    // case 'delete_enquiry':
    //     $id = intval($_POST['id'] ?? 0);

    //     if (!$id) {
    //         json_response(false, 'Invalid enquiry ID');
    //     }

    //     // Delete related records first
    //     runQuery("DELETE FROM site_visits WHERE enquiry_id = ?", "i", [$id]);
    //     runQuery("DELETE FROM quotations WHERE enquiry_id = ?", "i", [$id]);
    //     runQuery("DELETE FROM project_stages WHERE enquiry_id = ?", "i", [$id]);
    //     runQuery("DELETE FROM boq WHERE enquiry_id = ?", "i", [$id]);
    //     // runQuery("DELETE FROM purchases WHERE enquiry_id = ?", "i", [$id]);
    //     runQuery("DELETE FROM payments WHERE enquiry_id = ?", "i", [$id]);

    //     $result = runQuery("DELETE FROM enquiries WHERE id = ?", "i", [$id]);

    //     if ($result['success']) {
    //         json_response(true, 'Enquiry deleted successfully!');
    //     } else {
    //         json_response(false, $result['message']);
    //     }
    //     break;

    case 'delete_enquiry':
    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        json_response(false, 'Invalid ID');
    }

    $result = runQuery("DELETE FROM enquiries WHERE id = ?", "i", [$id]);

    if ($result['success']) {
        json_response(true, 'Deleted successfully');
    } else {
        json_response(false, $result['message']);
    }

    break;

    // Get all enquiries (for list page)
    case 'get_enquiries':
        $enquiries = fetchAll("SELECT * FROM enquiries ORDER BY created_at DESC");
        json_response(true, '', ['enquiries' => $enquiries]);
        break;

    // Get single enquiry details
    case 'get_enquiry':
        $enquiry_id = intval($_POST['id'] ?? 0);
        if (!$enquiry_id) {
            json_response(false, 'Invalid enquiry ID');
        }

        $enquiry = fetchOne("SELECT * FROM enquiries WHERE id = ?", "i", [$enquiry_id]);
        if (!$enquiry) {
            json_response(false, 'Enquiry not found');
        }

        // Get related data
        $site_visits = fetchAll("SELECT * FROM site_visits WHERE enquiry_id = ? ORDER BY scheduled_date DESC", "i", [$enquiry_id]);
        $quotations = fetchAll("SELECT * FROM quotations WHERE enquiry_id = ? ORDER BY created_at DESC", "i", [$enquiry_id]);
        $project_stages = fetchAll("SELECT * FROM project_stages WHERE enquiry_id = ?", "i", [$enquiry_id]);
        $boq_items = fetchAll("SELECT * FROM boq WHERE enquiry_id = ?", "i", [$enquiry_id]);
        $purchases = fetchAll("SELECT * FROM purchases WHERE enquiry_id = ? ORDER BY created_at DESC", "i", [$enquiry_id]);
        $payments = fetchAll("SELECT * FROM payments WHERE enquiry_id = ? ORDER BY payment_date DESC", "i", [$enquiry_id]);

        json_response(true, '', [
            'enquiry' => $enquiry,
            'site_visits' => $site_visits,
            'quotations' => $quotations,
            'project_stages' => $project_stages,
            'boq_items' => $boq_items,
            'purchases' => $purchases,
            'payments' => $payments
        ]);
        break;

    // Schedule Site Visit
    case 'schedule_visit':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $scheduled_date = $_POST['scheduled_date'] ?? '';
        $scheduled_time = $_POST['scheduled_time'] ?? '';
        $engineer_name = sanitize($_POST['engineer_name'] ?? '');
        $visit_notes = sanitize($_POST['visit_notes'] ?? '');

        if (!$enquiry_id || !$scheduled_date) {
            json_response(false, 'Required fields missing');
        }

        $result = insertAndGetId(
            "INSERT INTO site_visits (enquiry_id, scheduled_date, scheduled_time, engineer_name, visit_notes) VALUES (?, ?, ?, ?, ?)",
            "issss",
            [$enquiry_id, $scheduled_date, $scheduled_time, $engineer_name, $visit_notes]
        );

        if ($result['success']) {
            runQuery("UPDATE enquiries SET status = 'site_visit_scheduled' WHERE id = ?", "i", [$enquiry_id]);
            json_response(true, 'Site visit scheduled successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Add Quotation
    case 'add_quotation':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $quotation_amount = floatval($_POST['quotation_amount'] ?? 0);
        $drawing_details = sanitize($_POST['drawing_details'] ?? '');
        $valid_until = $_POST['valid_until'] ?? '';
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$enquiry_id || !$quotation_amount) {
            json_response(false, 'Required fields missing');
        }

        $result = insertAndGetId(
            "INSERT INTO quotations (enquiry_id, quotation_amount, drawing_details, valid_until, notes) VALUES (?, ?, ?, ?, ?)",
            "idsss",
            [$enquiry_id, $quotation_amount, $drawing_details, $valid_until, $notes]
        );

        if ($result['success']) {
            runQuery("UPDATE enquiries SET status = 'quotation_prepared' WHERE id = ?", "i", [$enquiry_id]);
            json_response(true, 'Quotation added successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Record Advance Payment
    case 'advance_payment':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $payment_mode = sanitize($_POST['payment_mode'] ?? '');
        $reference_number = sanitize($_POST['reference_number'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$enquiry_id || !$amount || !$payment_mode) {
            json_response(false, 'Required fields missing');
        }

        $result = insertAndGetId(
            "INSERT INTO payments (enquiry_id, payment_type, amount, payment_date, payment_mode, reference_number, notes) VALUES (?, 'advance', ?, ?, ?, ?, ?)",
            "idssss",
            [$enquiry_id, $amount, $payment_date, $payment_mode, $reference_number, $notes]
        );

        if ($result['success']) {
            runQuery("UPDATE enquiries SET status = 'advance_paid' WHERE id = ?", "i", [$enquiry_id]);
            json_response(true, 'Advance payment recorded successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Start Project
    case 'start_project':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        if (!$enquiry_id) {
            json_response(false, 'Invalid enquiry ID');
        }

        runQuery("UPDATE enquiries SET status = 'project_started' WHERE id = ?", "i", [$enquiry_id]);
        json_response(true, 'Project started successfully!');
        break;

    // Add Construction Stage
    case 'add_stage':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $stage_name = sanitize($_POST['stage_name'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$enquiry_id || !$stage_name) {
            json_response(false, 'Required fields missing');
        }

        $result = insertAndGetId(
            "INSERT INTO project_stages (enquiry_id, stage_name, status, start_date, notes) VALUES (?, ?, 'in_progress', CURDATE(), ?)",
            "iss",
            [$enquiry_id, $stage_name, $notes]
        );

        if ($result['success']) {
            runQuery("UPDATE enquiries SET status = 'in_progress' WHERE id = ?", "i", [$enquiry_id]);
            json_response(true, 'Construction stage added!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Update Stage Status
    case 'update_stage':
        $stage_id = intval($_POST['stage_id'] ?? 0);
        $stage_status = sanitize($_POST['stage_status'] ?? '');
        $completion_date = $stage_status == 'completed' ? date('Y-m-d') : null;

        if (!$stage_id || !$stage_status) {
            json_response(false, 'Required fields missing');
        }

        runQuery(
            "UPDATE project_stages SET status = ?, completion_date = ? WHERE id = ?",
            "ssi",
            [$stage_status, $completion_date, $stage_id]
        );
        json_response(true, 'Stage updated!');
        break;

    // Add BOQ
    case 'add_boq':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $item_description = sanitize($_POST['item_description'] ?? '');
        $unit = sanitize($_POST['unit'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $rate = floatval($_POST['rate'] ?? 0);
        $amount = $quantity * $rate;
        $category = sanitize($_POST['category'] ?? '');

        if (!$enquiry_id || !$item_description || !$quantity || !$rate) {
            json_response(false, 'Required fields missing');
        }

        $result = insertAndGetId(
            "INSERT INTO boq (enquiry_id, item_description, unit, quantity, rate, amount, category) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "isssdds",
            [$enquiry_id, $item_description, $unit, $quantity, $rate, $amount, $category]
        );

        if ($result['success']) {
            json_response(true, 'BOQ item added!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Add Purchase
    case 'add_purchase':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $item_name = sanitize($_POST['item_name'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $unit = sanitize($_POST['unit'] ?? '');
        $estimated_cost = floatval($_POST['estimated_cost'] ?? 0);
        $vendor_name = sanitize($_POST['vendor_name'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$enquiry_id || !$item_name || !$quantity) {
            json_response(false, 'Required fields missing');
        }

        $result = insertAndGetId(
            "INSERT INTO purchases (enquiry_id, item_name, quantity, unit, estimated_cost, vendor_name, status, notes) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)",
            "isssdss",
            [$enquiry_id, $item_name, $quantity, $unit, $estimated_cost, $vendor_name, $notes]
        );

        if ($result['success']) {
            json_response(true, 'Purchase added!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Add Payment
    case 'add_payment':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $payment_type = sanitize($_POST['payment_type'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $payment_mode = sanitize($_POST['payment_mode'] ?? '');
        $reference_number = sanitize($_POST['reference_number'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');

        if (!$enquiry_id || !$payment_type || !$amount || !$payment_mode) {
            json_response(false, 'Required fields missing');
        }

        $result = insertAndGetId(
            "INSERT INTO payments (enquiry_id, payment_type, amount, payment_date, payment_mode, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "sdsssss",
            [$enquiry_id, $payment_type, $amount, $payment_date, $payment_mode, $reference_number, $notes]
        );

        if ($result['success']) {
            json_response(true, 'Payment recorded!');
        } else {
            json_response(false, $result['message']);
        }
        break;

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

        $result = insertAndGetId(
            "INSERT INTO site_visits (enquiry_id, scheduled_date, scheduled_time, engineer_name, visit_notes) VALUES (?, ?, ?, ?, ?)",
            "issss",
            [$enquiry_id, $scheduled_date, $scheduled_time, $engineer_name, $notes]
        );

        if ($result['success']) {
            // Update enquiry status
            runQuery("UPDATE enquiries SET status = 'site_visit_scheduled' WHERE id = ?", "i", [$enquiry_id]);
            json_response(true, 'Site visit scheduled successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Get Site Visits
    case 'get_site_visits':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);

        if (!$enquiry_id) {
            json_response(false, 'Invalid enquiry ID');
        }

        $visits = fetchAll("SELECT * FROM site_visits WHERE enquiry_id = ? ORDER BY scheduled_date DESC", "i", [$enquiry_id]);
        json_response(true, '', ['site_visits' => $visits]);
        break;

    default:
        json_response(false, 'Invalid action');
}