<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'quotation', 'create');

if (!isset($_FILES['excel_file'])) {
    header("Location: list.php?error=No file uploaded");
    exit;
}

$file = $_FILES['excel_file']['tmp_name'];

if (!file_exists($file)) {
    header("Location: list.php?error=File upload failed");
    exit;
}

$handle = fopen($file, "r");

if (!$handle) {
    header("Location: list.php?error=Unable to read file");
    exit;
}

// Skip header row
fgetcsv($handle);

$conn = getDB();
$successCount = 0;
$errorRows = [];

// Loop each row (MULTIPLE quotations supported)
while (($row = fgetcsv($handle)) !== FALSE) {

    if (count($row) < 6) {
        $errorRows[] = "Invalid column count";
        continue;
    }

    $client_name     = sanitize($row[0]);
    $contact_phone   = sanitize($row[1]);
    $contact_email   = sanitize($row[2]);
    $project_title   = sanitize($row[3]);
    $estimated_amount = floatval($row[4]);
    $valid_until     = $row[5];

    // Validation
    if (empty($client_name) || empty($contact_phone) || empty($project_title) || $estimated_amount <= 0) {
        $errorRows[] = "Invalid data in row: " . implode(",", $row);
        continue;
    }

    // Generate quotation number
    $year = date('Y');
    $result = fetchOne("SELECT COUNT(*) as count FROM project_quotations WHERE YEAR(created_at) = ?", "i", [$year]);
    $count = ($result['count'] ?? 0) + 1;
    $quotation_number = 'QTN-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

    // Insert quotation
    $stmt = $conn->prepare("
        INSERT INTO project_quotations
        (quotation_number, enquiry_id, client_name, contact_phone, contact_email, project_title, scope_description, estimated_amount, valid_until, status, created_by)
        VALUES (?, NULL, ?, ?, ?, ?, '', ?, ?, 'Draft', ?)
    ");

    $stmt->bind_param("sssssdsi",
        $quotation_number,
        $client_name,
        $contact_phone,
        $contact_email,
        $project_title,
        $estimated_amount,
        $valid_until,
        $_SESSION['user_id']
    );

    if ($stmt->execute()) {
        $successCount++;
    } else {
        $errorRows[] = "DB Error: " . $stmt->error;
    }

    $stmt->close();
}

fclose($handle);

// Redirect with result
if ($successCount > 0) {
    $msg = "$successCount quotations imported successfully";
    if (!empty($errorRows)) {
        $msg .= " (Some rows failed)";
    }
    header("Location: list.php?success=" . urlencode($msg));
} else {
    header("Location: list.php?error=Import failed");
}

exit;
?>