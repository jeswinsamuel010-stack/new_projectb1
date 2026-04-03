<?php
/**
 * Test Mail Script
 * Use this to verify PHPMailer is working correctly
 *
 * Usage: Open this file in browser or run via CLI
 * http://localhost/new_projectb/test_mail.php
 */

// Include mail configuration
require_once __DIR__ . '/config/mail.php';

// Load PHPMailer classes - Manual installation (no Composer)
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Test settings - Change these to test
$testEmail = 'suriyadevad@gmail.com';  // Change to your test email
$testSubject = 'Test Email - Construction ERP';
$testBody = '<h1>Test Email</h1><p>This is a test email from Construction ERP system.</p>';

// Results
$success = false;
$errorMessage = '';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Mail - Construction ERP</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; border: 1px solid #bee5eb; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>PHPMailer Test</h1>";

try {
    echo "<div class='info'>Starting mail test...</div>";

    // Create PHPMailer instance
    $mail = new PHPMailer(true);

    // Server settings
    $mail->SMTPDebug = 2;  // Enable verbose debug output (2 = server messages)
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;

    // Recipients
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($testEmail, 'Test User');

    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $testSubject;
    $mail->Body    = $testBody;
    $mail->AltBody = strip_tags($testBody);

    echo "<div class='info'>Sending email to: $testEmail</div>";

    // Send email
    $mail->send();

    $success = true;
    echo "<div class='success'>Email sent successfully!</div>";
    echo "<p><strong>To:</strong> $testEmail</p>";
    echo "<p><strong>From:</strong> " . MAIL_FROM . "</p>";

} catch (Exception $e) {
    $errorMessage = $mail->ErrorInfo;
    echo "<div class='error'>Email failed to send!</div>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($errorMessage) . "</p>";

    // Provide helpful troubleshooting
    echo "<h3>Troubleshooting Tips:</h3>
    <pre>
1. Check SMTP settings in config/mail.php
2. For Gmail: Use App Password, not your regular password
3. Enable 2-Factor Authentication on Gmail
4. Generate App Password: https://myaccount.google.com/apppasswords
5. Make sure IMAP is enabled in Gmail settings
    </pre>";
}

echo "</body></html>";

// Also output in CLI mode
if (php_sapi_name() === 'cli') {
    if ($success) {
        echo "\n✓ Email sent successfully!\n";
    } else {
        echo "\n✗ Email failed: $errorMessage\n";
    }
}
