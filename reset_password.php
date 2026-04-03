<?php
/**
 * Reset Password Page - WITH DEBUGGING
 * Users land here from the email reset link
 */

session_start();

// ==================== DEBUG MODE ====================
// Set to true to see debugging information
$DEBUG = false; // Set to true only for debugging
// ====================================================

$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid reset link. Please request a new password reset.';
    $showForm = false;
} else {
    require_once 'config/db.php';

    // Hash the token to compare with stored hash
    $tokenHash = hash('sha256', $token);

    if ($DEBUG) {
        echo "<h3>DEBUG: Token Validation</h3>";
        echo "<pre>";
        echo "Token from URL: " . $token . "\n";
        echo "Token Hash:     " . $tokenHash . "\n";
        echo "Token Length:   " . strlen($token) . " characters\n";
        echo "</pre>";

        // Debug: Show what's in the database
        echo "<h3>DEBUG: Database Check</h3>";
        $checkSql = "SELECT id, email, token, token_hash, expires_at, used, created_at FROM password_resets WHERE token_hash = ?";
        $dbRecord = fetchOne($checkSql, "s", [$tokenHash]);

        if ($dbRecord) {
            echo "<pre>";
            echo "Found record:\n";
            print_r($dbRecord);
            echo "</pre>";
        } else {
            echo "<p>No record found for this token hash.</p>";

            // Check if token matches plain token (for debugging)
            $plainCheckSql = "SELECT id, email, token, token_hash, expires_at, used FROM password_resets WHERE token = ?";
            $plainRecord = fetchOne($plainCheckSql, "s", [$token]);

            if ($plainRecord) {
                echo "<p><strong>Found by plain token match!</strong></p>";
                echo "<pre>";
                print_r($plainRecord);
                echo "</pre>";
                echo "<p><strong>Solution:</strong> Your validation is using token_hash but storing plain token. Fix the comparison!</p>";
            }
        }

        // Check all tokens for this email
        echo "<h3>DEBUG: All Active Tokens</h3>";
        $allTokensSql = "SELECT id, email, token, token_hash, expires_at, used, created_at FROM password_resets WHERE used = 0 ORDER BY created_at DESC";
        $allTokens = fetchAll($allTokensSql);
        echo "<pre>";
        print_r($allTokens);
        echo "</pre>";
        exit;
    }

    // Check if token is valid and not expired
    // FIXED: Use MySQL time for comparison (both in same timezone)
    $sql = "SELECT pr.*, u.full_name, u.email
            FROM password_resets pr
            JOIN users u ON LOWER(pr.email) = LOWER(u.email)
            WHERE pr.token_hash = ?
            AND pr.used = 0
            AND pr.expires_at > NOW()";

    $resetRequest = fetchOne($sql, "s", [$tokenHash]);

    if (!$resetRequest) {
        // Debug: Let's see what happened
        $error = 'This reset link has expired or has already been used. Please request a new password reset.';
        $showForm = false;
    } else {
        $showForm = true;
        $userEmail = $resetRequest['email'];
        $userName = $resetRequest['full_name'];
    }
}

// Check for session messages
if (isset($_SESSION['reset_password_error'])) {
    $error = $_SESSION['reset_password_error'];
    unset($_SESSION['reset_password_error']);
}

if (isset($_SESSION['reset_password_success'])) {
    $success = $_SESSION['reset_password_success'];
    unset($_SESSION['reset_password_success']);
}

// Process password reset if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($newPassword)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $error = 'Password must contain at least one number.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Validate token again before updating
        $tokenHash = hash('sha256', $token);
        $checkSql = "SELECT id FROM password_resets
                     WHERE token_hash = ?
                     AND used = 0
                     AND expires_at > NOW()";

        $validToken = fetchOne($checkSql, "s", [$tokenHash]);

        if (!$validToken) {
            $error = 'This reset link has expired. Please request a new password reset.';
            $showForm = false;
        } else {
            // Hash the new password
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update user password
            $updateSql = "UPDATE users SET password = ? WHERE email = LOWER(?)";
            $result = runQuery($updateSql, "ss", [$passwordHash, $userEmail]);

            if ($result['success']) {
                // Mark token as used
                $markUsedSql = "UPDATE password_resets SET used = 1 WHERE token_hash = ?";
                runQuery($markUsedSql, "s", [$tokenHash]);

                // Invalidate all other active tokens
                $invalidateSql = "UPDATE password_resets SET used = 1 WHERE email = LOWER(?) AND used = 0";
                runQuery($invalidateSql, "s", [$userEmail]);

                $success = 'Your password has been reset successfully. You can now login with your new password.';
                error_log("Password reset completed for user: " . $userEmail);
                $showForm = false;
            } else {
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Construction ERP</title>
    <link rel="stylesheet" href="/new_projectb/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { width: 100%; max-width: 450px; }
        .login-box { background: white; border-radius: 10px; padding: 40px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2); }
        .login-box h2 { text-align: center; color: #333; margin-bottom: 10px; font-size: 28px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 14px; }
        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 15px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .password-requirements { font-size: 12px; color: #666; margin-top: 8px; padding: 10px; background: #f9f9f9; border-radius: 5px; }
        .password-requirements ul { margin: 5px 0 0 0; padding-left: 20px; }
        .password-requirements li { margin-bottom: 3px; }
        .btn { width: 100%; padding: 14px; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .links { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .links a { color: #667eea; text-decoration: none; font-size: 14px; }
        .links a:hover { text-decoration: underline; }
        .icon-large { font-size: 48px; color: #667eea; text-align: center; margin-bottom: 20px; }
        .user-info { text-align: center; padding: 15px; background: #f9f9f9; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <?php if ($showForm): ?>
                <div class="icon-large"><i class="fas fa-lock"></i></div>
                <h2><i class="fas fa-hard-hat"></i> Construction ERP</h2>
                <p class="subtitle">Create a new password for your account</p>

                <div class="user-info">
                    <i class="fas fa-user"></i> Resetting password for: <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="8">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>

                    <div class="password-requirements">
                        <strong>Password requirements:</strong>
                        <ul>
                            <li>At least 8 characters</li>
                            <li>At least one uppercase letter (A-Z)</li>
                            <li>At least one lowercase letter (a-z)</li>
                            <li>At least one number (0-9)</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>

            <?php else: ?>
                <div class="icon-large"><i class="fas fa-check-circle"></i></div>
                <h2><i class="fas fa-hard-hat"></i> Construction ERP</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <div class="links">
                        <a href="forgot_password.php"><i class="fas fa-redo"></i> Request New Reset Link</a>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <div class="links">
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
