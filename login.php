<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':
            header("Location: index.php");
            break;
        case 'site_engineer':
            header("Location: requests/list.php");
            break;
        case 'project_manager':
            header("Location: approval/index.php");
            break;
        case 'purchase_team':
            header("Location: purchase/list.php");
            break;
        case 'store_keeper':
            header("Location: inventory/list.php");
            break;
        case 'accounts':
            header("Location: bills/list.php");
            break;
        default:
            header("Location: index.php");
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/db.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        // Case-insensitive login - check both username and email
        $sql = "SELECT id, username, email, password, full_name, role, status FROM users WHERE (LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)) AND status = 'active'";
        $user = fetchOne($sql, "ss", [$username, $username]);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful - regenerate session
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: index.php");
                    break;
                case 'site_engineer':
                    header("Location: requests/list.php");
                    break;
                case 'project_manager':
                    header("Location: approval/index.php");
                    break;
                case 'purchase_team':
                    header("Location: purchase/list.php");
                    break;
                case 'store_keeper':
                    header("Location: inventory/list.php");
                    break;
                case 'accounts':
                    header("Location: bills/list.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit;
        } else {
            $error = 'Invalid username/email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Construction ERP</title>
    <link rel="stylesheet" href="/new_projectb/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2><i class="fas fa-hard-hat"></i> Construction ERP</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" placeholder="Enter username or email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-size: 14px;">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </div>

        </div>
    </div>
</body>
</html>