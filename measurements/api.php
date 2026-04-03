
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
    // Add Measurement
    case 'add_measurement':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);
        $site_visit_id = intval($_POST['site_visit_id'] ?? 0);
        $area_sqft = floatval($_POST['area_sqft'] ?? 0);
        $length = floatval($_POST['length'] ?? 0);
        $width = floatval($_POST['width'] ?? 0);
        $height = floatval($_POST['height'] ?? 0);
        $notes = sanitize($_POST['measurement_notes'] ?? '');

        // Required validation
        if (!$enquiry_id || !$area_sqft || !$site_visit_id) {
            json_response(false, 'All required fields including site visit must be selected');
        }

        // Check if enquiry exists and get current status
        $enquiry = fetchOne("SELECT id, status FROM enquiries WHERE id = ?", "i", [$enquiry_id]);
        if (!$enquiry) {
            json_response(false, 'Enquiry not found');
        }

        // Validate: Site visit must be scheduled before adding measurement
        if (!in_array($enquiry['status'], ['site_visit_scheduled', 'new', 'measurement_added'])) {
            json_response(false, 'Schedule site visit first');
        }

        // Insert measurement and update site visit status in transaction
        $conn = getDB();
        $conn->begin_transaction();

        try {
            // Insert measurement
            $stmt = $conn->prepare("INSERT INTO measurements (enquiry_id, site_visit_id, area_sqft, length, width, height, measurement_notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iidddds", $enquiry_id, $site_visit_id, $area_sqft, $length, $width, $height, $notes);
            $stmt->execute();
            $measurement_id = $stmt->insert_id;
            $stmt->close();

            // Update site visit status to completed
            $stmt = $conn->prepare("UPDATE site_visits SET visit_completed = 1 WHERE id = ?");
            $stmt->bind_param("i", $site_visit_id);
            $stmt->execute();
            $stmt->close();

            // Update enquiry status to measurement_added
            $stmt = $conn->prepare("UPDATE enquiries SET status = 'measurement_added' WHERE id = ?");
            $stmt->bind_param("i", $enquiry_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            json_response(true, 'Measurement added successfully!', [
                'measurement_id' => $measurement_id,
                'enquiry_id' => $enquiry_id
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            json_response(false, $e->getMessage());
        }
        break;

    // Get Measurements
    case 'get_measurements':
        $enquiry_id = intval($_POST['enquiry_id'] ?? 0);

        if ($enquiry_id) {
            $measurements = fetchAll("SELECT * FROM measurements WHERE enquiry_id = ? ORDER BY created_at DESC", "i", [$enquiry_id]);
        } else {
            $measurements = fetchAll("SELECT * FROM measurements ORDER BY created_at DESC");
        }
        json_response(true, '', ['measurements' => $measurements]);
        break;

    // Delete Measurement
    case 'delete_measurement':
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            json_response(false, 'Invalid measurement ID');
        }

        $result = runQuery("DELETE FROM measurements WHERE id = ?", "i", [$id]);

        if ($result['success']) {
            json_response(true, 'Measurement deleted successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    // Update Measurement
    case 'update_measurement':
        $id = intval($_POST['id'] ?? 0);
        $area_sqft = floatval($_POST['area_sqft'] ?? 0);
        $length = floatval($_POST['length'] ?? 0);
        $width = floatval($_POST['width'] ?? 0);
        $height = floatval($_POST['height'] ?? 0);
        $notes = sanitize($_POST['measurement_notes'] ?? '');

        if (!$id || !$area_sqft) {
            json_response(false, 'Required fields missing');
        }

        $result = runQuery(
            "UPDATE measurements SET area_sqft = ?, length = ?, width = ?, height = ?, measurement_notes = ? WHERE id = ?",
            "ddddsi",
            [$area_sqft, $length, $width, $height, $notes, $id]
        );

        if ($result['success']) {
            json_response(true, 'Measurement updated successfully!');
        } else {
            json_response(false, $result['message']);
        }
        break;

    default:
        json_response(false, 'Invalid action');
}