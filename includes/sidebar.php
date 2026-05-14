<?php
/**
 * Dynamic Sidebar Navigation
 * Changes based on user type (admin/employee)
 * Beautiful modern design with fixed footer
 */

// Get current page and directory
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Determine if we're in admin or employee section
$is_admin = ($_SESSION['user_type'] == 'admin');

// ============================================
// PATH CALCULATIONS
// ============================================

// Logout path - uses local logout.php in admin/ or employee/ directory
if ($current_dir == 'admin' || $current_dir == 'employee') {
    $logout_path = 'logout.php';
} elseif (in_array($current_dir, ['users', 'products', 'customers', 'loans', 'reports', 'settings', 'savings', 'collections', 'agents'])) {
    $logout_path = '../logout.php';
} elseif ($current_dir == 'shared') {
    $logout_path = ($_SESSION['user_type'] == 'admin') ? '../admin/logout.php' : '../employee/logout.php';
} else {
    $logout_path = 'logout.php';
}

// Dashboard link
$dashboard_link = ($current_dir == 'admin' || $current_dir == 'employee') ? 'dashboard.php' : '../dashboard.php';
?>

<div class="sidebar-wrapper">
    <!-- User Profile Section -->
    <div class="sidebar-profile">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 2)); ?>
            <span class="status-dot online"></span>
        </div>
        <div class="profile-info">
            <h6 class="profile-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h6>
            <span class="profile-role">
                <i class="bi bi-<?php echo $_SESSION['user_type'] == 'admin' ? 'shield-check' : 'person-badge'; ?>"></i>
                <?php echo ucfirst($_SESSION['user_type']); ?>
            </span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <!-- ============================================ -->
        <!-- DASHBOARD -->
        <!-- ============================================ -->
        <a href="<?php echo $dashboard_link; ?>" 
           class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <span class="nav-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>
            <span class="nav-text">Dashboard</span>
        </a>

        <?php if ($is_admin): ?>
        <!-- ============================================ -->
        <!-- ADMIN MENU -->
        <!-- ============================================ -->
        
        <div class="nav-divider">
            <span>Management</span>
        </div>
        
        <!-- Users -->
        <a href="<?php echo ($current_dir == 'admin') ? 'users/index.php' : '../users/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'users') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-people-fill"></i></span>
            <span class="nav-text">Manage Users</span>
        </a>
        
        <!-- Customers -->
        <a href="<?php echo ($current_dir == 'admin') ? 'customers/index.php' : '../customers/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'customers') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-person-lines-fill"></i></span>
            <span class="nav-text">All Customers</span>
        </a>

        <!-- Agents -->
        <a href="<?php echo ($current_dir == 'admin') ? 'agents/index.php' : '../agents/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'agents') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-person-badge"></i></span>
            <span class="nav-text">Manage Agents</span>
        </a>

        <div class="nav-divider">
            <span>Loan Management</span>
        </div>
        
        <!-- Loan Products -->
        <a href="<?php echo ($current_dir == 'admin') ? 'products/index.php' : '../products/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'products') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-box-seam"></i></span>
            <span class="nav-text">Loan Products</span>
        </a>
        
        <!-- Approve Loans -->
        <a href="<?php echo ($current_dir == 'admin') ? 'loans/approve.php' : '../loans/approve.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'approve.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-check-circle"></i></span>
            <span class="nav-text">Approve Loans</span>
            <?php
            try {
                $pending = dbSingle("SELECT COUNT(*) as count FROM loans WHERE status = 'pending'");
                if ($pending && $pending['count'] > 0):
            ?>
                <span class="nav-badge"><?php echo $pending['count']; ?></span>
            <?php endif; } catch(Exception $e) {} ?>
        </a>
        
        <!-- Write-off -->
        <a href="<?php echo ($current_dir == 'admin') ? 'loans/write_off.php' : '../loans/write_off.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'write_off.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-x-circle"></i></span>
            <span class="nav-text">Write-off Loans</span>
        </a>
        
        <!-- Restructure -->
        <a href="<?php echo ($current_dir == 'admin') ? 'loans/restructure.php' : '../loans/restructure.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'restructure.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-arrow-repeat"></i></span>
            <span class="nav-text">Restructure Loans</span>
        </a>

        <div class="nav-divider">
            <span>Reports & Settings</span>
        </div>
        
        <!-- Reports -->
        <a href="<?php echo ($current_dir == 'admin') ? 'reports/index.php' : '../reports/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'reports') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-graph-up-arrow"></i></span>
            <span class="nav-text">Reports</span>
        </a>
        
        <!-- Audit Trail -->
        <a href="<?php echo ($current_dir == 'admin') ? 'reports/audit_trail.php' : '../reports/audit_trail.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'audit_trail.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-journal-text"></i></span>
            <span class="nav-text">Audit Trail</span>
        </a>
        
        <!-- Settings -->
        <a href="<?php echo ($current_dir == 'admin') ? 'settings/index.php' : '../settings/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'settings') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-gear-fill"></i></span>
            <span class="nav-text">Settings</span>
        </a>
        
        <?php else: ?>
        <!-- ============================================ -->
        <!-- EMPLOYEE MENU -->
        <!-- ============================================ -->
        
        <div class="nav-divider">
            <span>Customers</span>
        </div>
        
        <!-- My Customers -->
        <a href="<?php echo ($current_dir == 'employee') ? 'customers/index.php' : '../customers/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'customers') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-person-lines-fill"></i></span>
            <span class="nav-text">My Customers</span>
        </a>
        
        <!-- Add Customer -->
        <a href="<?php echo ($current_dir == 'employee') ? 'customers/add.php' : '../customers/add.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'add.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-person-plus-fill"></i></span>
            <span class="nav-text">Add Customer</span>
        </a>
        
        <!-- Search -->
        <a href="<?php echo ($current_dir == 'employee') ? 'customers/search.php' : '../customers/search.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-search"></i></span>
            <span class="nav-text">Search Customers</span>
        </a>

        <div class="nav-divider">
            <span>Savings (Susu)</span>
        </div>
        
        <!-- Savings Accounts -->
        <a href="<?php echo ($current_dir == 'employee') ? 'savings/index.php' : '../savings/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'savings') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-piggy-bank-fill"></i></span>
            <span class="nav-text">Savings Accounts</span>
        </a>
        
        <!-- Create Account -->
        <a href="<?php echo ($current_dir == 'employee') ? 'savings/create.php' : '../savings/create.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'create.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-plus-circle-fill"></i></span>
            <span class="nav-text">Create Account</span>
        </a>
        
        <!-- Deposit -->
        <a href="<?php echo ($current_dir == 'employee') ? 'savings/deposit.php' : '../savings/deposit.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'deposit.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-arrow-down-circle-fill"></i></span>
            <span class="nav-text">Make Deposit</span>
        </a>
        
        <!-- Withdrawal -->
        <a href="<?php echo ($current_dir == 'employee') ? 'savings/withdraw.php' : '../savings/withdraw.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'withdraw.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-arrow-up-circle-fill"></i></span>
            <span class="nav-text">Make Withdrawal</span>
        </a>

        <div class="nav-divider">
            <span>Loan Management</span>
        </div>
        
        <!-- Loans -->
        <a href="<?php echo ($current_dir == 'employee') ? 'loans/index.php' : '../loans/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'loans') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-cash-stack"></i></span>
            <span class="nav-text">My Loans</span>
        </a>
        
        <!-- New Loan -->
        <a href="<?php echo ($current_dir == 'employee') ? 'loans/create.php' : '../loans/create.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'create.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-file-earmark-plus-fill"></i></span>
            <span class="nav-text">New Loan</span>
        </a>
        
        <!-- Disburse -->
        <a href="<?php echo ($current_dir == 'employee') ? 'loans/disburse.php' : '../loans/disburse.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'disburse.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-send-fill"></i></span>
            <span class="nav-text">Disburse Loan</span>
        </a>
        
        <!-- Repay -->
        <a href="<?php echo ($current_dir == 'employee') ? 'loans/repay.php' : '../loans/repay.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'repay.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-cash-coin"></i></span>
            <span class="nav-text">Make Repayment</span>
        </a>
        
        <!-- History -->
        <a href="<?php echo ($current_dir == 'employee') ? 'loans/history.php' : '../loans/history.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'history.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-clock-history"></i></span>
            <span class="nav-text">Loan History</span>
        </a>

        <div class="nav-divider">
            <span>Collections & Reports</span>
        </div>
        
        <!-- Collections -->
        <a href="<?php echo ($current_dir == 'employee') ? 'collections/index.php' : '../collections/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'collections') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-collection-fill"></i></span>
            <span class="nav-text">Collections</span>
        </a>
        
        <!-- Record Collection -->
        <a href="<?php echo ($current_dir == 'employee') ? 'collections/record.php' : '../collections/record.php'; ?>" 
           class="nav-item <?php echo ($current_page == 'record.php') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-plus-circle-fill"></i></span>
            <span class="nav-text">Record Collection</span>
        </a>
        
        <!-- Reports -->
        <a href="<?php echo ($current_dir == 'employee') ? 'reports/index.php' : '../reports/index.php'; ?>" 
           class="nav-item <?php echo ($current_dir == 'reports') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-file-earmark-bar-graph-fill"></i></span>
            <span class="nav-text">My Reports</span>
        </a>
        <?php endif; ?>
    </nav>
</div>

<!-- Sidebar Footer - FIXED at bottom -->
<div class="sidebar-footer-fixed">
    <div class="footer-status">
        <span class="status-indicator online"></span>
        <span>Online</span>
    </div>
    <div class="footer-version">
        <small>v<?php echo APP_VERSION; ?></small>
    </div>
    <a href="<?php echo $logout_path; ?>" class="btn-logout-sidebar">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>
</div>