<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Determine the base URL dynamically to handle absolute paths correctly
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$script_dir = str_replace('\\', '/', $script_dir);
$last_folder = basename($script_dir);
if ($last_folder === 'admin' || $last_folder === 'employee' || $last_folder === 'user') {
    $base_url = dirname($script_dir);
} else {
    $base_url = $script_dir;
}
$base_url = rtrim($base_url, '/') . '/';

// Dynamic sidebar background color
$sidebar_bg = '#0f172a'; // Default slate-900 color
try {
    if (!isset($pdo)) {
        if (file_exists(__DIR__ . '/../config/db.php')) {
            require_once __DIR__ . '/../config/db.php';
        }
    }
    if (isset($pdo)) {
        $themeStmt = $pdo->query("SELECT sidebar_bg_color FROM theme_settings LIMIT 1");
        $themeData = $themeStmt->fetch();
        if ($themeData) {
            $sidebar_bg = $themeData['sidebar_bg_color'];
        }
    }
} catch (Exception $e) {
    // Ignore and fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Management System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css?v=<?= time(); ?>">
    <style>
        :root {
            --primary: <?= htmlspecialchars($sidebar_bg) ?> !important;
        }
        .sidebar {
            background-color: var(--primary) !important;
        }
        /* Style for settings dropdown */
        .sidebar .collapse .nav-link {
            padding-left: 1.5rem !important;
            opacity: 0.85;
            margin-bottom: 0.2rem;
        }
        .sidebar .collapse .nav-link:hover, 
        .sidebar .collapse .nav-link.active {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }
        /* Chevron layout in sidebar settings */
        .dropdown-toggle {
            display: flex;
            align-items: center;
            width: 100%;
        }
        .dropdown-toggle::after {
            display: inline-block;
            margin-left: auto;
            vertical-align: 0.255em;
            content: "";
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
            transition: transform 0.2s ease;
        }
        .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }
        /* Hide caret/chevron & collapse when sidebar is collapsed and not hovered */
        body.sidebar-collapsed .sidebar:not(:hover) .dropdown-toggle::after {
            display: none;
        }
        body.sidebar-collapsed .sidebar:not(:hover) .collapse {
            display: none !important;
        }
        /* Enable scrolling on sidebar hover in collapsed state */
        body.sidebar-collapsed .sidebar:hover {
            overflow-y: auto !important;
        }
        /* Premium custom scrollbar styling for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="sidebar-collapsed">

<div class="sidebar" id="sidebar">
    <div class="sidebar-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-map-marked-alt"></i>
            <span>Travel Management System</span>
        </div>
        <button class="btn btn-link text-white p-0 d-lg-none" id="sidebarClose" onclick="closeSidebar()" style="font-size: 1.25rem; border: none; background: none; opacity: 0.8;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="nav flex-column">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/dashboard.php">
                <i class="fas fa-chart-line"></i> <span>Dashboard</span>
            </a>
            <a class="nav-link <?= ($current_page == 'trip_oversight.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/trip_oversight.php">
                <i class="fas fa-globe"></i> <span>Trip Oversight</span>
            </a>
            <a class="nav-link <?= ($current_page == 'employee_activities.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/employee_activities.php">
                <i class="fas fa-users-cog"></i> <span>Staff Activities</span>
            </a>
            <div class="nav-item">
                <a class="nav-link dropdown-toggle <?= in_array($current_page, ['master_staff.php', 'master_locations.php', 'master_hotels.php', 'master_cabs.php']) ? 'active' : '' ?>" 
                   href="#" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#mastersMenu" 
                   aria-expanded="<?= in_array($current_page, ['master_staff.php', 'master_locations.php', 'master_hotels.php', 'master_cabs.php']) ? 'true' : 'false' ?>">
                    <i class="fas fa-th-list"></i> <span>Masters</span>
                </a>
                <div class="collapse <?= in_array($current_page, ['master_staff.php', 'master_locations.php', 'master_hotels.php', 'master_cabs.php']) ? 'show' : '' ?> ps-2" id="mastersMenu">
                    <a class="nav-link py-2 <?= ($current_page == 'master_staff.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/master_staff.php" style="font-size: 0.9rem;">
                        <i class="fas fa-users" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Staff Master</span>
                    </a>
                    <a class="nav-link py-2 <?= ($current_page == 'master_locations.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/master_locations.php" style="font-size: 0.9rem;">
                        <i class="fas fa-map-marker-alt" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Location Masters</span>
                    </a>
                    <a class="nav-link py-2 <?= ($current_page == 'master_hotels.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/master_hotels.php" style="font-size: 0.9rem;">
                        <i class="fas fa-hotel" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Hotel Masters</span>
                    </a>
                    <a class="nav-link py-2 <?= ($current_page == 'master_cabs.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/master_cabs.php" style="font-size: 0.9rem;">
                        <i class="fas fa-car" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Cab Masters</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-item">
                <a class="nav-link dropdown-toggle <?= in_array($current_page, ['change_password.php', 'mail_configuration.php', 'mail_templates.php', 'color_themes.php']) ? 'active' : '' ?>" 
                   href="#" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#settingsMenu" 
                   aria-expanded="<?= in_array($current_page, ['change_password.php', 'mail_configuration.php', 'mail_templates.php', 'color_themes.php']) ? 'true' : 'false' ?>">
                    <i class="fas fa-cog"></i> <span>Settings</span>
                </a>
                <div class="collapse <?= in_array($current_page, ['change_password.php', 'mail_configuration.php', 'mail_templates.php', 'color_themes.php']) ? 'show' : '' ?> ps-2" id="settingsMenu">
                    <a class="nav-link py-2 <?= ($current_page == 'change_password.php') ? 'active' : '' ?>" href="<?= $base_url ?>change_password.php" style="font-size: 0.9rem;">
                        <i class="fas fa-key" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Change Password</span>
                    </a>
                    <a class="nav-link py-2 <?= ($current_page == 'mail_configuration.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/mail_configuration.php" style="font-size: 0.9rem;">
                        <i class="fas fa-envelope-open-text" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Mail Config</span>
                    </a>
                    <a class="nav-link py-2 <?= ($current_page == 'mail_templates.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/mail_templates.php" style="font-size: 0.9rem;">
                        <i class="fas fa-mail-bulk" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Mail Templates</span>
                    </a>
                    <a class="nav-link py-2 <?= ($current_page == 'color_themes.php') ? 'active' : '' ?>" href="<?= $base_url ?>admin/color_themes.php" style="font-size: 0.9rem;">
                        <i class="fas fa-palette" style="font-size: 0.85rem; min-width: 20px;"></i> <span>Color Themes</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="<?= $base_url ?>employee/dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
            <a class="nav-link <?= ($current_page == 'checklist.php') ? 'active' : '' ?>" href="<?= $base_url ?>employee/checklist.php"><i class="fas fa-clipboard-list"></i> <span>Manage Schedule</span></a>
            <a class="nav-link <?= ($current_page == 'change_password.php') ? 'active' : '' ?>" href="<?= $base_url ?>change_password.php"><i class="fas fa-key"></i> <span>Change Password</span></a>
        <?php endif; ?>
        
        <div class="mt-auto pt-4 border-top">
            <a class="nav-link text-danger" href="<?= $base_url ?>logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" id="menuToggle" onclick="toggleSidebar(event)" style="width: 42px; height: 42px; border-radius: 10px; background-color: var(--white); border-color: #e2e8f0 !important;">
                <i class="fas fa-bars text-secondary" style="font-size: 1.1rem;"></i>
            </button>
            <h3 class="fw-bold m-0" style="font-size: 1.5rem; letter-spacing: -0.5px;"><?php echo $pageTitle ?? 'Dashboard'; ?></h3>
        </div>
        <div class="dropdown">
            <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded-3 border shadow-sm" style="border-color: #e2e8f0 !important;">
                <span class="text-muted d-none d-sm-inline" style="font-size: 0.9rem;">Welcome, </span>
                <span class="fw-semibold text-secondary" style="font-size: 0.9rem;"><?php echo $_SESSION['full_name']; ?></span>
                <div class="bg-emerald text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Global functions to bypass any DOMContentLoaded delays or other JS script errors
    function toggleSidebar(e) {
        if (e) e.stopPropagation();
        if (window.innerWidth >= 992) {
            document.body.classList.toggle('sidebar-collapsed');
        } else {
            document.body.classList.toggle('sidebar-open');
        }
    }

    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Create overlay backdrop if it doesn't exist
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }
        
        // Click on overlay to close sidebar on mobile
        overlay.addEventListener('click', closeSidebar);
    });
    </script>

