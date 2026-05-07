<?php
/**
 * Common Header Template
 * Included at the top of all authenticated pages
 */

// Calculate logout path for the header dropdown
$current_dir_header = basename(dirname($_SERVER['PHP_SELF']));
if ($current_dir_header == 'admin' || $current_dir_header == 'employee') {
    $header_logout_path = 'logout.php';
} elseif (in_array($current_dir_header, ['users', 'products', 'customers', 'loans', 'reports', 'settings', 'savings', 'collections'])) {
    $header_logout_path = '../logout.php';
} elseif ($current_dir_header == 'shared') {
    $header_logout_path = ($_SESSION['user_type'] == 'admin') ? '../admin/logout.php' : '../employee/logout.php';
} else {
    $header_logout_path = 'logout.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon using Bootstrap Icon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><path fill='%23ffd700' d='M8.277.084a.5.5 0 0 0-.554 0l-7.5 5A.5.5 0 0 0 .5 6h1.875v7H1.5a.5.5 0 0 0 0 1h13a.5.5 0 0 0 0-1h-.875V6H15.5a.5.5 0 0 0 .277-.916l-7.5-5zM12.375 6v7h-1.25V6h1.25zm-2.5 0v7h-1.25V6h1.25zm-2.5 0v7h-1.25V6h1.25zm-2.5 0v7h-1.25V6h1.25zM8 1.574L13.076 5H2.924L8 1.574z'/></svg>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/css/custom.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            overflow-x: hidden;
        }

        /* ============================================ */
        /* BEAUTIFUL SIDEBAR STYLES */
        /* ============================================ */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: #1a1d29;
            color: #a2a6b4;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar .brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar .brand h5 {
            color: #ffffff;
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .sidebar .brand h5 i {
            color: #ffd700;
            font-size: 1.4rem;
        }

        .sidebar .brand small {
            color: #6c7293;
            font-size: 0.7rem;
            display: block;
            margin-top: 3px;
            margin-left: 34px;
        }

        /* Sidebar Wrapper - Scrollable */
        .sidebar-wrapper {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 0 20px 0;
        }

        .sidebar-wrapper::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }

        /* User Profile in Sidebar */
        .sidebar-profile {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.02);
        }

        .profile-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            position: relative;
            flex-shrink: 0;
        }

        .status-dot {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #1a1d29;
        }

        .status-dot.online {
            background: #2ecc71;
        }

        .profile-info {
            flex: 1;
            min-width: 0;
        }

        .profile-name {
            color: #ffffff;
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-role {
            font-size: 0.75rem;
            color: #6c7293;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .profile-role i {
            font-size: 0.7rem;
        }

        /* Navigation Items */
        .sidebar-nav {
            padding: 10px 0;
        }

        .nav-divider {
            padding: 15px 20px 8px;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #4a5073;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 11px 20px;
            color: #a2a6b4;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
            margin: 2px 10px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .nav-item:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.05);
            text-decoration: none;
        }

        .nav-item.active {
            color: #ffffff;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
        }

        .nav-item.active .nav-icon {
            color: #667eea;
        }

        .nav-indicator {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: #667eea;
            border-radius: 4px 0 0 4px;
        }

        .nav-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .nav-item:hover .nav-icon {
            color: #ffffff;
        }

        .nav-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: auto;
        }

        /* Sidebar Fixed Footer */
        .sidebar-footer-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 260px;
            background: #151821;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .footer-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            color: #6c7293;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-indicator.online {
            background: #2ecc71;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.5);
        }

        .footer-version {
            font-size: 0.7rem;
            color: #4a5073;
        }

        .btn-logout-sidebar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.2);
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-logout-sidebar:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-logout-sidebar i {
            font-size: 1rem;
        }

        /* Main content area */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Sidebar backdrop for mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-backdrop.show {
            display: block;
        }

        /* Top navbar */
        .top-navbar {
            background: white;
            padding: 12px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
        }

        /* Card styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }

        /* Stat cards */
        .stat-card {
            border-radius: 10px;
            transition: transform 0.3s ease;
            border: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        /* Border left cards */
        .border-left-danger { border-left: 4px solid #e74c3c; }
        .border-left-warning { border-left: 4px solid #f39c12; }
        .border-left-info { border-left: 4px solid #3498db; }
        .border-left-primary { border-left: 4px solid #667eea; }
        .border-left-success { border-left: 4px solid #27ae60; }

        /* Loading spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .opacity-50 { opacity: 0.5; }

        .progress {
            background-color: #e9ecef;
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }

        /* Responsive sidebar */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-footer-fixed {
                transform: translateX(-100%);
            }

            .sidebar.show ~ .sidebar-footer-fixed {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Print styles */
        @media print {
            .sidebar, .sidebar-footer-fixed, .top-navbar, .no-print, .sidebar-backdrop {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
            }
            
            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile sidebar backdrop -->
    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="brand">
            <h5><i class="bi bi-bank2"></i> <?php echo companyName(); ?></h5>
            <small>Microfinance System v<?php echo APP_VERSION; ?></small>
        </div>
        
        <?php include __DIR__ . '/sidebar.php'; ?>
    </nav>

    <!-- Sidebar Fixed Footer -->
    <div class="sidebar-footer-fixed" id="sidebarFooter">
        <div class="footer-status">
            <span class="status-indicator online"></span>
            <span>Online</span>
        </div>
        <div class="footer-version">
            <small>v<?php echo APP_VERSION; ?></small>
        </div>
        <a href="<?php echo $header_logout_path; ?>" class="btn-logout-sidebar">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation Bar -->
        <nav class="top-navbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-light d-lg-none me-2" onclick="toggleSidebar()" title="Toggle Menu">
                    <i class="bi bi-list"></i>
                </button>
                <span class="text-muted">
                    Welcome back, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                </span>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn btn-light position-relative" data-bs-toggle="dropdown" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <?php
                        try {
                            $notif_count = dbSingle(
                                "SELECT COUNT(*) as count FROM notifications WHERE user_id = :uid AND is_read = 0",
                                [':uid' => $_SESSION['user_id']]
                            );
                            $unread = $notif_count['count'] ?? 0;
                            if ($unread > 0):
                        ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread; ?>
                            </span>
                        <?php endif; } catch(Exception $e) {} ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow" style="width: 320px;">
                        <div class="dropdown-header d-flex justify-content-between">
                            <span class="fw-semibold">Notifications</span>
                            <small class="text-muted">Recent</small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php
                        try {
                            $notifications = dbQuery(
                                "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5",
                                [':uid' => $_SESSION['user_id']]
                            );
                            
                            if (count($notifications) > 0):
                                foreach ($notifications as $notif):
                        ?>
                            <a class="dropdown-item" href="<?php echo $notif['link'] ?? '#'; ?>">
                                <div class="d-flex align-items-start">
                                    <div class="me-2">
                                        <span class="badge bg-<?php echo $notif['type'] == 'danger' ? 'danger' : ($notif['type'] == 'warning' ? 'warning' : ($notif['type'] == 'success' ? 'success' : 'info')); ?> p-1">
                                            <i class="bi bi-<?php echo $notif['type'] == 'danger' ? 'exclamation' : 'info'; ?>-circle"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block"><?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?></small>
                                        <span class="small"><?php echo htmlspecialchars($notif['message']); ?></span>
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endforeach; else: ?>
                            <span class="dropdown-item text-muted text-center py-3">
                                <i class="bi bi-bell-slash me-2"></i>No new notifications
                            </span>
                        <?php endif; } catch(Exception $e) { ?>
                            <span class="dropdown-item text-muted text-center py-3">Notifications unavailable</span>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown user-dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
                        </div>
                        <div class="d-none d-md-block">
                            <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                            <small class="text-muted"><?php echo ucfirst($_SESSION['user_type']); ?></small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                       
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo $header_logout_path; ?>">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Breadcrumb -->
        <?php if (isset($page_title)): ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo $_SESSION['user_type'] == 'admin' ? '../admin/dashboard.php' : '../employee/dashboard.php'; ?>">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
                    <?php foreach ($breadcrumb as $name => $url): ?>
                        <?php if ($url): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo $name; ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?php echo $name; ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
            </ol>
        </nav>
        <?php endif; ?>

        <!-- Display Flash Messages -->
        <?php displayFlash(); ?>