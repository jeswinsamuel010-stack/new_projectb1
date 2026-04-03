<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /new_projectb/login.php");
    exit;
}

// Get current user info
$current_user = [
    'id' => $_SESSION['user_id'] ?? 0,
    'username' => $_SESSION['username'] ?? '',
    'full_name' => $_SESSION['full_name'] ?? '',
    'role' => $_SESSION['role'] ?? ''
];

/**
 * Role-Based Access Control (RBAC) Configuration
 * Format: [module][action] = [allowed_roles]
 * Module names should match folder names: projects, bills, approval, etc.
 */
$GLOBALS['rbac_config'] = [
    // Enquiry module
    'enquiry' => [
        'view'   => ['admin', 'sales', 'estimator', 'project_manager', 'accounts'],
        'create' => ['admin', 'sales'],
        'edit'   => ['admin', 'sales'],
        'delete' => ['admin'],
        'list'   => ['admin', 'sales', 'estimator', 'project_manager', 'accounts']
    ],
    // Quotation module
    'quotation' => [
        'view'   => ['admin', 'sales', 'estimator', 'project_manager', 'accounts'],
        'create' => ['admin', 'estimator'],
        'edit'   => ['admin', 'estimator'],
        'delete' => ['admin'],
        'approve' => ['admin', 'project_manager'],
        'list'   => ['admin', 'sales', 'estimator', 'project_manager', 'accounts']
    ],
    // Project module (maps to projects folder)
    'projects' => [
        'view'   => ['admin', 'project_manager', 'accounts', 'site_engineer'],
        'create' => ['admin', 'project_manager'],
        'edit'   => ['admin', 'project_manager'],
        'delete' => ['admin'],
        'list'   => ['admin', 'project_manager', 'accounts', 'site_engineer'],
        'manage' => ['admin', 'project_manager']
    ],
    // Milestone module (maps to milestones folder)
    'milestones' => [
        'view'   => ['admin', 'project_manager'],
        'create' => ['admin', 'project_manager'],
        'edit'   => ['admin', 'project_manager'],
        'delete' => ['admin'],
        'list'   => ['admin', 'project_manager']
    ],
    // Material Request module (maps to requests folder)
    'requests' => [
        'view'   => ['admin', 'project_manager', 'purchase_team', 'store_keeper'],
        'create' => ['admin', 'project_manager', 'site_engineer'],
        'approve' => ['admin', 'project_manager'],
        'issue'  => ['admin', 'store_keeper'],
        'list'   => ['admin', 'project_manager', 'purchase_team', 'store_keeper']
    ],
    // Purchase Order module (maps to purchase folder)
    'purchase' => [
        'view'   => ['admin', 'project_manager', 'purchase_team', 'accounts'],
        'create' => ['admin', 'purchase_team'],
        'approve' => ['admin', 'project_manager'],
        'list'   => ['admin', 'project_manager', 'purchase_team', 'accounts']
    ],
    // Approval module (maps to approval folder)
    'approval' => [
        'view'   => ['admin', 'project_manager'],
        'approve' => ['admin', 'project_manager'],
        'list'   => ['admin', 'project_manager']
    ],
    // Billing/Bills module (maps to bills folder)
    'bills' => [
        'view'   => ['admin', 'accounts', 'project_manager'],
        'create' => ['admin', 'accounts'],
        'approve' => ['admin', 'accounts'],
        'list'   => ['admin', 'accounts', 'project_manager']
    ],
    // Payment module (maps to payments folder)
    'payments' => [
        'view'   => ['admin', 'accounts', 'project_manager'],
        'create' => ['admin', 'accounts'],
        'list'   => ['admin', 'accounts', 'project_manager']
    ],
    // Inventory module (maps to inventory folder)
    'inventory' => [
        'view'   => ['admin', 'store_keeper', 'project_manager'],
        'create' => ['admin', 'store_keeper'],
        'edit'   => ['admin', 'store_keeper'],
        'list'   => ['admin', 'store_keeper', 'project_manager']
    ],
    // Issues module (maps to issues folder)
    'issues' => [
        'view'   => ['admin', 'store_keeper', 'project_manager'],
        'create' => ['admin', 'store_keeper', 'project_manager'],
        'issue'  => ['admin', 'store_keeper'],
        'list'   => ['admin', 'store_keeper', 'project_manager']
    ],
    // Reports module
    'reports' => [
        'view'   => ['admin', 'project_manager', 'accounts'],
        'list'   => ['admin', 'project_manager', 'accounts']
    ],
    // Users module
    'users' => [
        'view'   => ['admin'],
        'create' => ['admin'],
        'edit'   => ['admin'],
        'delete' => ['admin'],
        'list'   => ['admin']
    ],
    // Site Visits module
    'site_visits' => [
        'view'   => ['admin', 'site_engineer', 'project_manager'],
        'create' => ['admin', 'site_engineer', 'project_manager'],
        'edit'   => ['admin', 'site_engineer', 'project_manager'],
        'list'   => ['admin', 'site_engineer', 'project_manager']
    ],
    // Measurements module
    'measurements' => [
        'view'   => ['admin', 'site_engineer', 'project_manager'],
        'create' => ['admin', 'site_engineer', 'project_manager'],
        'edit'   => ['admin', 'site_engineer', 'project_manager'],
        'list'   => ['admin', 'site_engineer', 'project_manager']
    ],
    // Settings module
    'settings' => [
        'view'   => ['admin'],
        'edit'   => ['admin'],
        'list'   => ['admin']
    ]
];

/**
 * Check if current user has permission for module/action
 * @param string $module - Module name (folder name like 'projects', 'bills', 'approval')
 * @param string $action - Action name (view, list, create, edit, etc.)
 */
function hasPermission($module, $action = 'view') {
    $config = $GLOBALS['rbac_config'] ?? [];
    $role = $_SESSION['role'] ?? '';

    // Admin has full access
    if ($role === 'admin') {
        return true;
    }

    // Check if module exists (case-insensitive search)
    $module_lower = strtolower($module);
    $module_found = null;

    foreach ($config as $key => $value) {
        if (strtolower($key) === $module_lower) {
            $module_found = $key;
            break;
        }
    }

    if ($module_found === null) {
        return false;
    }

    // Check if action exists
    if (!isset($config[$module_found][$action])) {
        return false;
    }

    // Check role permission
    return in_array($role, $config[$module_found][$action]);
}

/**
 * Check access - aborts with error if no permission
 * @param array $allowed_roles - For backward compatibility: array of allowed roles
 * @param string $module - Module name (folder name) for RBAC
 * @param string $action - Action name (view, list, create, edit, etc.)
 */
function checkAccess($allowed_roles, $module = null, $action = null) {
    // If simple array of roles provided (backward compatibility)
    if (is_array($allowed_roles) && $module === null) {
        if (!hasRole($allowed_roles)) {
            header("Location: /new_projectb/index.php?error=access_denied");
            exit;
        }
        return;
    }

    // If module/action provided, use RBAC
    if ($module !== null && $action !== null) {
        if (!hasPermission($module, $action)) {
            http_response_code(403);
            header("Location: /new_projectb/index.php?error=access_denied");
            exit;
        }
        return;
    }

    // Fallback to old behavior
    if (!hasRole($allowed_roles)) {
        header("Location: /new_projectb/index.php?error=access_denied");
        exit;
    }
}

/**
 * Check permission and return boolean (no redirect)
 */
function canAccess($module, $action = 'view') {
    return hasPermission($module, $action);
}

/**
 * Get user's allowed actions for a module
 */
function getAllowedActions($module) {
    $config = $GLOBALS['rbac_config'] ?? [];
    $role = $_SESSION['role'] ?? '';

    if ($role === 'admin') {
        return ['view', 'create', 'edit', 'delete', 'approve', 'list'];
    }

    return $config[$module] ?? [];
}

/**
 * Backward compatible hasRole function
 */
function hasRole($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    return in_array($_SESSION['role'], $allowed_roles);
}

/**
 * Redirect user to their default page based on role
 */
function redirectByRole() {
    $role = $_SESSION['role'];
    $base = '/new_projectb/';
    switch ($role) {
        case 'admin':
            header("Location: {$base}index.php");
            break;
        case 'sales':
            header("Location: {$base}enquiry/list.php");
            break;
        case 'estimator':
            header("Location: {$base}quotation/list.php");
            break;
        case 'project_manager':
            header("Location: {$base}projects/list.php");
            break;
        case 'accounts':
            header("Location: {$base}bills/list.php");
            break;
        case 'site_engineer':
            header("Location: {$base}projects/list.php");
            break;
        case 'purchase_team':
            header("Location: {$base}purchase/list.php");
            break;
        case 'store_keeper':
            header("Location: {$base}inventory/list.php");
            break;
        default:
            header("Location: {$base}index.php");
    }
    exit;
}

// Generate CSRF token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}