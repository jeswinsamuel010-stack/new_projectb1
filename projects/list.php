<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'projects', 'list');

$page_title = 'Projects';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Projects</h2>
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Project
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table id="projectsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project Name</th>
                            <th>Client</th>
                            <th>Value (INR)</th>
                            <th>Start Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#projectsTable', '/new_projectb/api/projects.php', [
        { title: 'ID' },
        { title: 'Project Name' },
        { title: 'Client' },
        { title: 'Value (INR)' },
        { title: 'Start Date' },
        { title: 'Status' },
        { title: 'Actions', orderable: false }
    ]);
});


$(document).on('click', '.deleteProject', function () {

    let id = $(this).data('id');

    if (!confirm("Are you sure you want to delete this project?")) {
        return;
    }

    $.ajax({
        url: '/new_projectb/api/projects.php',
        type: 'POST',
        data: {
            action: 'delete_project',
            id: id
        },
        success: function (response) {

            if (response.success) {

                alert(response.message);

                $('#projectsTable').DataTable().ajax.reload();

            } else {

                alert(response.message);

            }

        }
    });

});
</script>

<?php require_once '../includes/footer.php'; ?>