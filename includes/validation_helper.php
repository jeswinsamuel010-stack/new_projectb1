<?php
/**
 * Validation & Security Helper
 * Provides input validation, sanitization, and security functions
 */

class Validator {
    private $errors = [];

    /**
     * Validate required field
     */
    public function required($value, $field_name) {
        if (empty(trim($value))) {
            $this->errors[$field_name] = ucfirst($field_name) . ' is required';
            return false;
        }
        return true;
    }

    /**
     * Validate email
     */
    public function email($value, $field_name) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field_name] = 'Invalid email format';
            return false;
        }
        return true;
    }

    /**
     * Validate phone number (10-15 digits)
     */
    public function phone($value, $field_name) {
        $clean = preg_replace('/[^0-9]/', '', $value);
        if (strlen($clean) < 10 || strlen($clean) > 15) {
            $this->errors[$field_name] = 'Invalid phone number';
            return false;
        }
        return true;
    }

    /**
     * Validate minimum length
     */
    public function minLength($value, $field_name, $length) {
        if (strlen(trim($value)) < $length) {
            $this->errors[$field_name] = ucfirst($field_name) . ' must be at least ' . $length . ' characters';
            return false;
        }
        return true;
    }

    /**
     * Validate maximum length
     */
    public function maxLength($value, $field_name, $length) {
        if (strlen(trim($value)) > $length) {
            $this->errors[$field_name] = ucfirst($field_name) . ' must not exceed ' . $length . ' characters';
            return false;
        }
        return true;
    }

    /**
     * Validate numeric value
     */
    public function numeric($value, $field_name) {
        if (!is_numeric($value)) {
            $this->errors[$field_name] = ucfirst($field_name) . ' must be a number';
            return false;
        }
        return true;
    }

    /**
     * Validate positive number
     */
    public function positive($value, $field_name) {
        if (floatval($value) <= 0) {
            $this->errors[$field_name] = ucfirst($field_name) . ' must be greater than 0';
            return false;
        }
        return true;
    }

    /**
     * Validate date format
     */
    public function date($value, $field_name) {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!($d && $d->format('Y-m-d') === $value)) {
            $this->errors[$field_name] = 'Invalid date format';
            return false;
        }
        return true;
    }

    /**
     * Validate enum value
     */
    public function in($value, $field_name, $allowed_values) {
        if (!in_array($value, $allowed_values)) {
            $this->errors[$field_name] = 'Invalid ' . $field_name . ' value';
            return false;
        }
        return true;
    }

    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Check if has errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Get first error message
     */
    public function getFirstError() {
        return reset($this->errors);
    }
}

/**
 * Sanitize input - removes dangerous characters
 */
function sanitizeInput($value) {
    if (is_array($value)) {
        return array_map('sanitizeInput', $value);
    }
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and return with error response
 */
function validateAndRespond($validator, $json_response_func = null) {
    if ($validator->hasErrors()) {
        $msg = implode('. ', $validator->getErrors());
        if ($json_response_func) {
            $json_response_func(false, $msg);
        } else {
            json_response(false, $msg);
        }
        return false;
    }
    return true;
}

/**
 * CSRF Token generation and validation
 */
function csrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting check (simple file-based)
 */
function rateLimitCheck($identifier, $max_requests = 10, $window_seconds = 60) {
    $file = sys_get_temp_dir() . '/rate_' . md5($identifier) . '.txt';
    $now = time();

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data['time'] > $now - $window_seconds) {
            if ($data['count'] >= $max_requests) {
                return false;
            }
            $data['count']++;
        } else {
            $data = ['count' => 1, 'time' => $now];
        }
    } else {
        $data = ['count' => 1, 'time' => $now];
    }

    file_put_contents($file, json_encode($data));
    return true;
}

/**
 * Secure file upload handling
 */
function handleFileUpload($file, $allowed_types = [], $max_size = 5242880) {
    $errors = [];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }

    // Check file type
    if (!empty($allowed_types)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        if (!in_array($mime_type, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
    }

    // Generate safe filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe_name = bin2hex(random_bytes(8)) . '.' . $ext;

    return ['success' => true, 'filename' => $safe_name, 'mime' => $mime_type ?? null];
}