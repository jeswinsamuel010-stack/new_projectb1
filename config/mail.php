<?php
/**
 * PHPMailer Configuration for Construction ERP
 *
 * To install PHPMailer:
 * 1. Download PHPMailer from https://github.com/PHPMailer/PHPMailer
 * 2. Extract to vendor/phpmailer/phpmailer/ folder
 * OR run: composer require phpmailer/phpmailer
 */

// Load PHPMailer classes - Manual installation (no Composer)
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

// PHPMailer namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Mail Server Settings
define('MAIL_HOST', 'smtp.gmail.com');        // SMTP server hostname
define('MAIL_USERNAME', 'suriyadevad@gmail.com');     // SMTP username
define('MAIL_PASSWORD', 'wjgl kppd yize jlrp'); // SMTP password
define('MAIL_FROM', 'suriyadevad@gmail.com');         // From email address
define('MAIL_FROM_NAME', 'Construction ERP');  // From name
define('MAIL_PORT', 587);                       // SMTP port (587 for TLS, 465 for SSL)
define('MAIL_ENCRYPTION', 'tls');                // 'tls' or 'ssl'

// Token expiration time (in minutes)
define('PASSWORD_RESET_TOKEN_EXPIRY', 30);

// Site URL for generating reset links
define('SITE_URL', 'http://localhost/new_projectb');

/**
 * Send password reset email
 *
 * @param string $email User's email address
 * @param string $token Reset token
 * @param string $full_name User's full name
 * @return bool True if email sent successfully
 */
function sendPasswordResetEmail($email, $token, $full_name) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $full_name);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Password Reset - Construction ERP';

        $resetLink = SITE_URL . '/reset_password.php?token=' . $token;

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0; text-align: center;">Construction ERP</h1>
            </div>
            <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
                <h2 style="color: #333;">Password Reset Request</h2>
                <p>Hello ' . htmlspecialchars($full_name) . ',</p>
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $resetLink . '" style="background: #667eea; color: white; padding: 14px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Reset Password</a>
                </div>
                <p style="font-size: 14px; color: #666;">
                    This link will expire in ' . PASSWORD_RESET_TOKEN_EXPIRY . ' minutes.<br>
                    If you did not request a password reset, please ignore this email or contact your administrator.
                </p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="font-size: 12px; color: #999;">
                    If the button does not work, copy and paste this link into your browser:<br>
                    ' . $resetLink . '
                </p>
            </div>
        </body>
        </html>
        ';

        $mail->AltBody = 'Password Reset Request for Construction ERP

Hello ' . $full_name . ',

We received a request to reset your password. Use the link below to create a new password:

' . $resetLink . '

This link will expire in ' . PASSWORD_RESET_TOKEN_EXPIRY . ' minutes.

If you did not request a password reset, please ignore this email or contact your administrator.';

        return $mail->send();
    } catch (Exception $e) {
        error_log("Password reset email failed: " . $mail->ErrorInfo);
        return false;
    }
}