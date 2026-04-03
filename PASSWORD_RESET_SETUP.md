# Password Reset System Setup Guide

## Files Created

### Database
- `sql/password_resets.sql` - Password resets table schema

### Configuration
- `config/mail.php` - PHPMailer configuration
- `composer.json` - Composer dependencies

### Pages
- `forgot_password.php` - Forgot password form (email input)
- `reset_password.php` - Reset password form (new password input)
- `process_forgot_password.php` - Process forgot password request

### Modified Files
- `login.php` - Added "Forgot Password?" link
- `sql/password_resets.sql` - New table created

---

## Setup Instructions

### Step 1: Run Database Setup
Execute the password_resets SQL file in phpMyAdmin:
```
sql/password_resets.sql
```

### Step 2: Install PHPMailer

**Option A: Using Composer (Recommended)**
```bash
composer require phpmailer/phpmailer
```

**Option B: Manual Download**
1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
2. Extract the zip file
3. Copy the `src` folder contents to: `vendor/phpmailer/phpmailer/src/`
4. Make sure you have these files:
   - `vendor/phpmailer/phpmailer/src/Exception.php`
   - `vendor/phpmailer/phpmailer/src/PHPMailer.php`
   - `vendor/phpmailer/phpmailer/src/SMTP.php`

### Step 3: Configure Mail Settings

Edit `config/mail.php` and update these settings:
```php
define('MAIL_HOST', 'smtp.yourserver.com');        // Your SMTP server
define('MAIL_USERNAME', 'your-email@example.com'); // SMTP username
define('MAIL_PASSWORD', 'your-password');          // SMTP password
define('MAIL_FROM', 'noreply@yourdomain.com');    // From email
define('MAIL_FROM_NAME', 'Your Company Name');    // From name
define('SITE_URL', 'http://localhost/new_projectb'); // Your site URL
```

### Step 4: Test the System
1. Go to `forgot_password.php`
2. Enter an email address that exists in your users table
3. Check your email for the reset link
4. Click the link and set a new password

---

## Security Features Implemented

1. **Token Security**: 64-character random token stored with SHA-256 hash
2. **Token Expiry**: Tokens expire after 30 minutes (configurable)
3. **Single-Use Tokens**: Tokens can only be used once
4. **Rate Limiting**: Max 5 requests per hour per IP
5. **CSRF Protection**: Form tokens validated on submission
6. **Password Validation**: Enforces strong password requirements
7. **Email Enumeration Protection**: Generic messages regardless of email existence
8. **Session Regeneration**: New session ID on password change

---

## Database Schema

```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at)
);
```

---

## Troubleshooting

**Mail not sending?**
- Check error logs in `config/mail.php`
- Verify SMTP settings in `config/mail.php`
- Make sure PHPMailer is properly installed

**Token expired immediately?**
- Check server timezone matches PHP timezone

**"Invalid request" error?**
- Clear browser cookies and try again
- CSRF token may have expired

---

## Email Settings for Common Providers

### Gmail
```php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password'); // Use App Password, not regular password
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');
```

### Outlook
```php
define('MAIL_HOST', 'smtp.office365.com');
define('MAIL_USERNAME', 'your-email@outlook.com');
define('MAIL_PASSWORD', 'your-password');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');
```

### Mailtrap (Testing)
```php
define('MAIL_HOST', 'smtp.mailtrap.io');
define('MAIL_USERNAME', 'your-username');
define('MAIL_PASSWORD', 'your-password');
define('MAIL_PORT', 2525);
define('MAIL_ENCRYPTION', 'tls');
```