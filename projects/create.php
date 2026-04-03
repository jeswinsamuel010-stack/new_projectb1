<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'projects', 'create');

$page_title = 'New Project';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $project_name  = sanitize($_POST['project_name'] ?? '');
    $client_name   = sanitize($_POST['client_name'] ?? '');
    $project_value = floatval($_POST['project_value'] ?? 0);
    $start_date    = $_POST['start_date'] ?? '';
    $description   = sanitize($_POST['description'] ?? '');

    if (empty($project_name) || empty($client_name) || empty($project_value) || empty($start_date)) {
        $error = 'Please fill all required fields';
    } else {

        $conn = getDB();

        // ✅ INSERT PROJECT
        $stmt = $conn->prepare("
            INSERT INTO projects 
            (project_name, client_name, project_value, start_date, description, created_by) 
            VALUES (?, ?, ?, STR_TO_DATE(?, '%Y-%m-%d'), ?, ?)
        ");

        if (!$stmt) {
            die("Project Insert Prepare Error: " . $conn->error);
        }

        $stmt->bind_param("sssssi", $project_name, $client_name, $project_value, $start_date, $description, $_SESSION['user_id']);

        if (!$stmt->execute()) {
            die("Project Insert Error: " . $stmt->error);
        }

        // ✅ GET PROJECT ID
        $project_id = $conn->insert_id;

        $stmt->close();

        // 🚨 DEBUG (optional - remove later)
        // echo "Project ID: " . $project_id; exit;

        // 🔥 AUTO CREATE MILESTONES (MATCH YOUR TABLE)
        $milestones = [
            ["Site Preparation", "Initial site setup"],
            ["Foundation Work", "Base construction"],
            ["Structure Work", "Building structure"],
            ["Finishing Work", "Interior & exterior"],
            ["Handover", "Project completion"]
        ];

        foreach ($milestones as $m) {

            $phase = $m[0];
            $desc  = $m[1];

            $sql = "INSERT INTO project_milestones 
            (project_id, phase_name, description, start_date, end_date, status)
            VALUES 
            ('$project_id', '$phase', '$desc', '$start_date', '$start_date', 'pending')";

            if (!$conn->query($sql)) {
                die("Milestone Insert Error: " . $conn->error);
            }
        }

        // ✅ REDIRECT TO VIEW PAGE
        header("Location: ../milestones/list.php?project_id=" . $project_id);
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>New Project</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">

                <div class="form-group">
                    <label>Project Name *</label>
                    <input type="text" name="project_name" required>
                </div>

                <div class="form-group">
                    <label>Client Name *</label>
                    <input type="text" name="client_name" required>
                </div>

                <div class="form-group">
                    <label>Project Value *</label>
                    <input type="number" name="project_value" required>
                </div>

                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Create Project</button>
                <a href="list.php" class="btn btn-danger">Cancel</a>

            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>