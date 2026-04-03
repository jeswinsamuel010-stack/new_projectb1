<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = 'active';

    // Check duplicate
    $existing = fetchOne(
        "SELECT id FROM users WHERE username = ? OR email = ?",
        "ss",
        [$username, $email]
    );

    if ($existing) {
        header("Location: list.php?error=Username or Email already exists");
        exit;
    }

    // Insert user
    $result = insertAndGetId(
        "INSERT INTO users (username, full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)",
        "ssssss",
        [$username, $full_name, $email, $password, $role, $status]
    );

    if ($result['success']) {
        header("Location: list.php?success=User created successfully");
        exit;
    } else {
        header("Location: list.php?error=" . urlencode($result['message']));
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {

    $user_id = intval($_POST['user_id']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']);
    $status = trim($_POST['status']);
    $new_password = trim($_POST['new_password']);

    if (!empty($new_password)) {
        $password = password_hash($new_password, PASSWORD_DEFAULT);

        $result = runQuery(
            "UPDATE users SET email=?, full_name=?, role=?, status=?, password=? WHERE id=?",
            "sssssi",
            [$email, $full_name, $role, $status, $password, $user_id]
        );
    } else {
        $result = runQuery(
            "UPDATE users SET email=?, full_name=?, role=?, status=? WHERE id=?",
            "ssssi",
            [$email, $full_name, $role, $status, $user_id]
        );
    }

    if ($result['success']) {
        header("Location: list.php?success=User updated successfully");
        exit;
    } else {
        header("Location: list.php?error=" . urlencode($result['message']));
        exit;
    }
}

checkAccess(null, 'users', 'list');

$page_title = 'User Management';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>User Management</h2>
        <button class="btn btn-primary" onclick="openModal('createUserModal')">
            <i class="fas fa-plus"></i> Add User
        </button>
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
                <table id="usersTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
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

<!-- Create User Modal -->
<div id="createUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="create_user" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="site_engineer">Site Engineer</option>
                            <option value="project_manager">Project Manager</option>
                            <option value="purchase_team">Purchase Team</option>
                            <option value="store_keeper">Store Keeper</option>
                            <option value="accounts">Accounts</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('createUserModal')">Cancel</button>
                <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username</label>
                        <p id="editUsername" class="text-muted"></p>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email *</label>
                        <input type="email" id="editEmail" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editFullName">Full Name *</label>
                        <input type="text" id="editFullName" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="editRole">Role *</label>
                        <select id="editRole" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="site_engineer">Site Engineer</option>
                            <option value="project_manager">Project Manager</option>
                            <option value="purchase_team">Purchase Team</option>
                            <option value="store_keeper">Store Keeper</option>
                            <option value="accounts">Accounts</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select id="editStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password (leave blank)</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUser(id, username, full_name, email, role, status) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editUsername').textContent = username;
    document.getElementById('editFullName').value = full_name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editStatus').value = status;
    openModal('editUserModal');
}

$(document).ready(function() {
    initDataTable('#usersTable', '/new_projectb/api/users.php', [
        { title: 'ID' },
        { title: 'Username' },
        { title: 'Full Name' },
        { title: 'Email' },
        { title: 'Role' },
        { title: 'Status' },
        { title: 'Actions', orderable: false }
    ]);
});
</script>

<?php require_once '../includes/footer.php'; ?>