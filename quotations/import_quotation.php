<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'quotation', 'create');

$page_title = 'Import Quotation';
require_once '../includes/header.php';
?>

<div class="content">
    <h3>Import Quotation from Excel</h3>

    <form action="process_import.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Upload Excel File</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.csv" required>
        </div>

        <button type="submit" class="btn btn-success">Upload & Import</button>
        <a href="list.php" class="btn btn-secondary">Back</a>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>