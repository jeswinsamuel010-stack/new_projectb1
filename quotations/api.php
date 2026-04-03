<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/approval_helper.php';
require_once '../includes/validation_helper.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function json_response($success, $message = '', $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Generate quotation number
function generateQuotationNumber() {
    $year = date('Y');
    $result = fetchOne("SELECT COUNT(*) as count FROM project_quotations WHERE YEAR(created_at) = ?", "i", [$year]);
    $count = ($result['count'] ?? 0) + 1;
    return 'QTN-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// Generate secure public token
function generatePublicToken() {
    return bin2hex(random_bytes(32));
}

switch ($action) {
    // Create new quotation
    case 'create_quotation':
        checkAccess(null, 'quotation', 'create');

        // Rate limit check
        if (!rateLimitCheck('create_quotation_' . $_SESSION['user_id'], 20, 60)) {
            json_response(false, 'Too many requests. Please try again later.');
        }

        $v = new Validator();

        // Get and sanitize inputs
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $client_name = sanitizeInput($_POST['client_name'] ?? '');
        $contact_phone = sanitizeInput($_POST['contact_phone'] ?? '');
        $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
        $project_title = sanitizeInput($_POST['project_title'] ?? '');
        $scope_description = sanitizeInput($_POST['scope_description'] ?? '');
        $estimated_amount = floatval($_POST['estimated_amount'] ?? 0);
        $valid_until = $_POST['valid_until'] ?? '';

        // Validate required fields
        $v->required($client_name, 'client_name')
          ->required($contact_phone, 'contact_phone')
          ->required($project_title, 'project_title')
          ->required($estimated_amount, 'estimated_amount');

        // Validate formats
        $v->minLength($client_name, 'client_name', 2)
          ->maxLength($client_name, 'client_name', 255)
          ->phone($contact_phone, 'contact_phone')
          ->maxLength($project_title, 'project_title', 255)
          ->positive($estimated_amount, 'estimated_amount')
          ->numeric($estimated_amount, 'estimated_amount');

        // Email is optional but must be valid if provided
        if (!empty($contact_email)) {
            $v->email($contact_email, 'contact_email');
        }

        // Validate date if provided
        if (!empty($valid_until)) {
            $v->date($valid_until, 'valid_until');
        }

        if ($v->hasErrors()) {
            json_response(false, $v->getFirstError());
        }

        // Validate enquiry_id if provided
        if ($enquiry_id > 0) {
            $enquiry = fetchOne("SELECT id FROM enquiries WHERE id = ?", "i", [$enquiry_id]);
            if (!$enquiry) {
                json_response(false, 'Invalid enquiry reference');
            }
        }

        $quotation_number = generateQuotationNumber();

        $result = insertAndGetId(
            "INSERT INTO project_quotations (quotation_number, enquiry_id, client_name, contact_phone, contact_email, project_title, scope_description, estimated_amount, valid_until, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?)",
            "sisssssdsi",
            [$quotation_number, $enquiry_id > 0 ? $enquiry_id : null, $client_name, $contact_phone, $contact_email, $project_title, $scope_description, $estimated_amount, $valid_until, $_SESSION['user_id']]
        );

        if ($result['success']) {
            if ($enquiry_id > 0) {
                runQuery("UPDATE enquiries SET status = 'quotation_prepared' WHERE id = ?", "i", [$enquiry_id]);
            }
            json_response(true, 'Quotation created successfully!', ['id' => $result['id'], 'quotation_number' => $quotation_number]);
        } else {
            json_response(false, 'Failed to create quotation');
        }
        break;

    // Update quotation
    case 'update_quotation':
        checkAccess(null, 'quotation', 'create');

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            json_response(false, 'Invalid quotation ID');
        }

        $v = new Validator();

        $client_name = sanitizeInput($_POST['client_name'] ?? '');
        $contact_phone = sanitizeInput($_POST['contact_phone'] ?? '');
        $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
        $project_title = sanitizeInput($_POST['project_title'] ?? '');
        $scope_description = sanitizeInput($_POST['scope_description'] ?? '');
        $estimated_amount = floatval($_POST['estimated_amount'] ?? 0);
        $valid_until = $_POST['valid_until'] ?? '';

        // Validate
        $v->required($client_name, 'client_name')
          ->required($contact_phone, 'contact_phone')
          ->required($project_title, 'project_title')
          ->required($estimated_amount, 'estimated_amount')
          ->minLength($client_name, 'client_name', 2)
          ->maxLength($client_name, 'client_name', 255)
          ->phone($contact_phone, 'contact_phone')
          ->maxLength($project_title, 'project_title', 255)
          ->positive($estimated_amount, 'estimated_amount');

        if (!empty($contact_email)) {
            $v->email($contact_email, 'contact_email');
        }

        if (!empty($valid_until)) {
            $v->date($valid_until, 'valid_until');
        }

        if ($v->hasErrors()) {
            json_response(false, $v->getFirstError());
        }

        // Check if quotation exists
        $quotation = fetchOne("SELECT id, status FROM project_quotations WHERE id = ?", "i", [$id]);
        if (!$quotation) {
            json_response(false, 'Quotation not found');
        }

        // Check if quotation is approved - cannot update approved quotations
        $existing = fetchOne("SELECT status FROM project_quotations WHERE id = ?", "i", [$id]);
        if ($existing && $existing['status'] == 'Approved') {
            json_response(false, 'Cannot modify an approved quotation');
        }
        if ($existing && $existing['status'] == 'Converted') {
            json_response(false, 'Cannot modify a converted quotation');
        }

        $result = runQuery(
            "UPDATE project_quotations SET client_name = ?, contact_phone = ?, contact_email = ?, project_title = ?, scope_description = ?, estimated_amount = ?, valid_until = ? WHERE id = ?",
            "sssssdssi",
            [$client_name, $contact_phone, $contact_email, $project_title, $scope_description, $estimated_amount, $valid_until, $id]
        );

        if ($result['success']) {
            json_response(true, 'Quotation updated successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Delete quotation
    case 'delete_quotation':
        checkAccess(null, 'quotation', 'create');

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid quotation ID');
        }

        // Check if quotation is approved - cannot delete approved quotations
        $existing = fetchOne("SELECT status FROM project_quotations WHERE id = ?", "i", [$id]);
        if ($existing && in_array($existing['status'], ['Approved', 'Converted'])) {
            json_response(false, 'Cannot delete an approved or converted quotation');
        }

        $result = runQuery("DELETE FROM project_quotations WHERE id = ?", "i", [$id]);

        if ($result['success']) {
            json_response(true, 'Quotation deleted successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Send quotation to client (mark as sent)
    case 'send_to_client':
        checkAccess(null, 'quotation', 'create');

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid quotation ID');
        }

        $quotation = fetchOne("SELECT * FROM project_quotations WHERE id = ?", "i", [$id]);
        if (!$quotation) {
            json_response(false, 'Quotation not found');
        }

        if ($quotation['status'] !== 'Draft') {
            json_response(false, 'Only draft quotations can be sent to client');
        }

        // Update status to Sent
        $result = runQuery(
            "UPDATE project_quotations SET status = 'Sent', sent_to_client_at = NOW() WHERE id = ?",
            "i",
            [$id]
        );

        if ($result['success']) {
            json_response(true, 'Quotation marked as sent to client! Use the PDF download to share with the client.');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Submit quotation for approval
    case 'submit_for_approval':
        checkAccess(null, 'quotation', 'create');

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid quotation ID');
        }

        $quotation = fetchOne("SELECT * FROM project_quotations WHERE id = ?", "i", [$id]);
        if (!$quotation) {
            json_response(false, 'Quotation not found');
        }

        if ($quotation['status'] != 'Sent') {
            json_response(false, 'Only sent quotations can be submitted for approval');
        }

        // Use approval helper to create pending approval
        $approval = new ApprovalHelper();
        $result = $approval->submitForApproval(
            'quotation',
            $id,
            'Sent',
            'project_quotations',
            'status'
        );

        if ($result['success']) {
            json_response(true, 'Quotation submitted for approval!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Approve quotation (Admin only) - Using centralized approval
    case 'approve_quotation':
        checkAccess(null, 'quotation', 'create');

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            json_response(false, 'Invalid quotation ID');
        }

        $approval_notes = sanitizeInput($_POST['approval_notes'] ?? '');
        $v = new Validator();
        $v->maxLength($approval_notes, 'approval_notes', 1000);

        if ($v->hasErrors()) {
            json_response(false, $v->getFirstError());
        }

        $quotation = fetchOne("SELECT * FROM project_quotations WHERE id = ?", "i", [$id]);
        if (!$quotation) {
            json_response(false, 'Quotation not found');
        }

        if ($quotation['status'] != 'Sent') {
            json_response(false, 'Only sent quotations can be approved');
        }

        // Use approval helper
        $approval = new ApprovalHelper();
        $result = $approval->approve(
            'quotation',           // module_type
            $id,                   // module_id
            $_SESSION['user_id'],  // user_id
            $approval_notes,       // notes
            'project_quotations',  // table_name
            'Approved',            // approved_status
            'status'               // status_field
        );

        if ($result['success']) {
            // Update enquiry status if linked
            if ($quotation['enquiry_id']) {
                runQuery("UPDATE enquiries SET status = 'advance_paid' WHERE id = ?", "i", [$quotation['enquiry_id']]);
            }
            json_response(true, 'Quotation approved successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Reject quotation (Admin only) - Using centralized approval
    case 'reject_quotation':
        checkAccess(null, 'quotation', 'create');

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            json_response(false, 'Invalid quotation ID');
        }

        $approval_notes = sanitizeInput($_POST['approval_notes'] ?? '');
        $v = new Validator();
        $v->maxLength($approval_notes, 'approval_notes', 1000);

        if ($v->hasErrors()) {
            json_response(false, $v->getFirstError());
        }

        $quotation = fetchOne("SELECT * FROM project_quotations WHERE id = ?", "i", [$id]);
        if (!$quotation) {
            json_response(false, 'Quotation not found');
        }

        if ($quotation['status'] != 'Sent') {
            json_response(false, 'Only sent quotations can be rejected');
        }

        // Use approval helper
        $approval = new ApprovalHelper();
        $result = $approval->reject(
            'quotation',
            $id,
            $_SESSION['user_id'],
            $approval_notes,
            'project_quotations',
            'Rejected',
            'status'
        );

        if ($result['success']) {
            json_response(true, 'Quotation rejected!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Get all quotations
    case 'get_quotations':
        $quotations = fetchAll("
            SELECT q.*, e.client_name as enquiry_client, e.phone as enquiry_phone, e.project_type, e.site_location
            FROM project_quotations q
            LEFT JOIN enquiries e ON q.enquiry_id = e.id
            ORDER BY q.created_at DESC
        ");
        json_response(true, '', ['quotations' => $quotations]);
        break;

    // Get single quotation
    case 'get_quotation':
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid quotation ID');
        }

        $quotation = fetchOne("
            SELECT q.*, e.client_name as enquiry_client, e.phone as enquiry_phone, e.project_type, e.site_location,
                   u.full_name as approver_name
            FROM project_quotations q
            LEFT JOIN enquiries e ON q.enquiry_id = e.id
            LEFT JOIN users u ON q.approved_by = u.id
            WHERE q.id = ?
        ", "i", [$id]);

        if (!$quotation) {
            json_response(false, 'Quotation not found');
        }

        json_response(true, '', ['quotation' => $quotation]);
        break;

    // Get public quotation by token
    case 'get_public_quotation':
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            json_response(false, 'Invalid request');
        }

        $quotation = fetchOne("SELECT * FROM project_quotations WHERE public_token = ?", "s", [$token]);

        if (!$quotation) {
            json_response(false, 'Quotation not found or link has expired');
        }

        json_response(true, '', ['quotation' => $quotation]);
        break;

    // Get enquiries for dropdown
    case 'get_enquiries_for_quotation':
        $enquiries = fetchAll("
            SELECT id, client_name, phone, email, project_type, site_location
            FROM enquiries
            WHERE status IN ('new', 'site_visit_scheduled')
            ORDER BY created_at DESC
        ");
        json_response(true, '', ['enquiries' => $enquiries]);
        break;

    // Convert quotation to project
    case 'convert_to_project':
        checkAccess(null, 'quotation', 'create');

        $quotation_id = intval($_POST['quotation_id'] ?? 0);

        if (!$quotation_id) {
            json_response(false, 'Invalid quotation ID');
        }

        // Get quotation details
        $quotation = fetchOne("SELECT * FROM project_quotations WHERE id = ?", "i", [$quotation_id]);

        if (!$quotation) {
            json_response(false, 'Quotation not found');
        }

        if ($quotation['status'] != 'Approved') {
            json_response(false, 'Only approved quotations can be converted to projects');
        }

        // Check if project already exists for this enquiry
        if ($quotation['enquiry_id']) {
            $existing_project = fetchOne("SELECT id FROM projects WHERE enquiry_id = ?", "i", [$quotation['enquiry_id']]);
            if ($existing_project) {
                json_response(false, 'A project already exists for this enquiry');
            }
        }

        // Create project
        $result = insertAndGetId(
            "INSERT INTO projects (project_name, client_name, project_value, start_date, description, created_by, enquiry_id, quotation_id) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?)",
            "ssdsiii",
            [$quotation['project_title'], $quotation['client_name'], $quotation['estimated_amount'], $quotation['scope_description'], $_SESSION['user_id'], $quotation['enquiry_id'], $quotation_id]
        );

        if ($result['success']) {
            $project_id = $result['id'];

            // Auto-create milestones for the project
            $milestones = [
                ["Site Preparation", "Initial site setup and preparation"],
                ["Foundation Work", "Base construction and foundation"],
                ["Structure Work", "Building structure and framework"],
                ["Finishing Work", "Interior and exterior finishing"],
                ["Handover", "Project completion and handover"]
            ];

            $start_date = date('Y-m-01'); // First day of current month
            $conn = getDB();

            foreach ($milestones as $index => $m) {
                $phase = $m[0];
                $desc = $m[1];

                // Calculate end date (2 months apart for each milestone)
                $end_date = date('Y-m-t', strtotime("+" . ($index * 2) . " months"));

                $stmt = $conn->prepare("INSERT INTO project_milestones (project_id, phase_name, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("issss", $project_id, $phase, $desc, $start_date, $end_date);
                $stmt->execute();
                $stmt->close();
            }

            // Update quotation status to Converted
            runQuery("UPDATE project_quotations SET status = 'Converted' WHERE id = ?", "i", [$quotation_id]);

            // Update enquiry status if linked
            if ($quotation['enquiry_id']) {
                runQuery("UPDATE enquiries SET status = 'project_started' WHERE id = ?", "i", [$quotation['enquiry_id']]);
            }

            json_response(true, 'Project created from quotation successfully!', ['project_id' => $project_id]);
        } else {
            json_response(false, $result['message']);
        }
        break;

    default:
        json_response(false, 'Invalid action');
}