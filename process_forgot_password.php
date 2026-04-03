<?php
/**
 * Process Forgot Password Request - WITH DEBUGGING
 * Handles email submission and sends reset link
 */

session_start();
require_once 'config/db.php';
require_once 'config/mail.php';

// ==================== DEBUG MODE ====================
// Set to true to see debugging information
$DEBUG = false; // Set to true only for debugging
// ====================================================

// Rate limiting
$maxRequestsPerHour = 5;

if (!isset($_SESSION['forgot_password_attempts'])) {
    $_SESSION['forgot_password_attempts'] = [];
}

$oneHourAgo = time() - 3600;
$_SESSION['forgot_password_attempts'] = array_filter(
    $_SESSION['forgot_password_attempts'],
    function($timestamp) use ($oneHourAgo) {
        return $timestamp > $oneHourAgo;
    }
);

if (count($_SESSION['forgot_password_attempts']) >= $maxRequestsPerHour) {
    $_SESSION['forgot_password_error'] = 'Too many requests. Please try again later.';
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot_password.php");
    exit;
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    $_SESSION['forgot_password_error'] = 'Invalid request. Please try again.';
    header("Location: forgot_password.php");
    exit;
}

// Get and sanitize email
$email = trim($_POST['email'] ?? '');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    $_SESSION['forgot_password_error'] = 'Please enter your email address.';
    header("Location: forgot_password.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['forgot_password_error'] = 'Please enter a valid email address.';
    header("Location: forgot_password.php");
    exit;
}

// Check if email exists
$sql = "SELECT id, email, full_name, status FROM users WHERE LOWER(email) = LOWER(?)";
$user = fetchOne($sql, "s", [$email]);

if (!$user) {
    // Security: Always show success message
    $_SESSION['forgot_password_success'] = 'If an account exists with this email, you will receive a password reset link shortly.';
    header("Location: forgot_password.php");
    exit;
}

if ($user['status'] !== 'active') {
    $_SESSION['forgot_password_error'] = 'Your account is inactive. Please contact the administrator.';
    header("Location: forgot_password.php");
    exit;
}

$_SESSION['forgot_password_attempts'][] = time();

// ==================== TOKEN GENERATION ====================
// Generate a secure random token (64 hex characters = 32 bytes)
$token = bin2hex(random_bytes(32));

// Create SHA-256 hash of the token for storage
$tokenHash = hash('sha256', $token);

// FIXED: Use MySQL NOW() for timezone consistency
// Get current time from MySQL to ensure consistency
$conn = getDB();
$mysqlNow = $conn->query("SELECT NOW() as now")->fetch_assoc();
$expiresAt = date('Y-m-d H:i:s', strtotime($mysqlNow['now']) + (PASSWORD_RESET_TOKEN_EXPIRY * 60));

if ($DEBUG) {
    echo "<h3>DEBUG: Token Generation</h3>";
    echo "<pre>";
    echo "Token (plain): " . $token . "\n";
    echo "Token Hash:    " . $tokenHash . "\n";
    echo "PHP Time:      " . date('Y-m-d H:i:s') . "\n";
    echo "Expires At:    " . $expiresAt . "\n";
    echo "Token Length:  " . strlen($token) . " characters\n";
    echo "</pre>";
    exit;
}

// Delete any existing unused tokens for this email
$deleteSql = "UPDATE password_resets SET used = 1 WHERE email = LOWER(?) AND used = 0";
runQuery($deleteSql, "s", [strtolower($email)]);

// Store token in database
// We store BOTH the plain token (for email) and hash (for security)
$insertSql = "INSERT INTO password_resets (email, token, token_hash, expires_at) VALUES (?, ?, ?, ?)";
$result = insertAndGetId($insertSql, "ssss", [strtolower($email), $token, $tokenHash, $expiresAt]);

if (!$result['success']) {
    error_log("Failed to insert password reset token: " . $result['message']);
    $_SESSION['forgot_password_error'] = 'An error occurred. Please try again later.';
    header("Location: forgot_password.php");
    exit;
}

// Send password reset email
$emailSent = sendPasswordResetEmail($email, $token, $user['full_name']);

if ($emailSent) {
    $_SESSION['forgot_password_success'] = 'If an account exists with this email, you will receive a password reset link shortly.';
    error_log("Password reset email sent to: " . $email);
} else {
    error_log("Failed to send password reset email to: " . $email);
    $_SESSION['forgot_password_success'] = 'If an account exists with this email, you will receive a password reset link shortly.';
}

header("Location: forgot_password.php");
exit;
