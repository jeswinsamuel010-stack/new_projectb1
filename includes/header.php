<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Construction ERP</title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/new_projectb/css/style.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>

        <main class="main-content <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'login-page' : ''; ?>">
            <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
               <header class="top-bar">
    <div class="left-section">
        <!-- Sidebar toggle button -->
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page title -->
        <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
    </div>

    <div class="right-section">
        <div class="dropdown">
            <button class="btn user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                
                <!-- User Icon Circle -->
                <div class="user-icon">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                </div>

                <!-- User Name -->
                <span><?php echo $_SESSION['full_name'] ?? 'User'; ?></span>
            </button>

            <!-- Dropdown Menu -->
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2">
                    <strong><?php echo $_SESSION['full_name'] ?? 'User'; ?></strong><br>
                    <small><?php echo ucfirst($_SESSION['role'] ?? 'user'); ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="/new_projectb/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
            <?php endif; ?>