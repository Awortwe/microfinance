<?php
/**
 * Admin Access Check
 * Verifies that logged-in user is an admin
 * Include this after config/init.php
 */

// First check if user is logged in
require_once __DIR__ . '/auth_check.php';

// Check if user is admin
if ($_SESSION['user_type'] !== 'admin') {
    // Log unauthorized access attempt
    logActivity('Unauthorized admin access attempt', 'auth');
    
    // Set flash message
    setFlash('error', 'You do not have permission to access the admin area.');
    
    // Redirect to appropriate dashboard
    if ($_SESSION['user_type'] === 'employee') {
        header('Location: ../employee/dashboard.php');
    } else {
        header('Location: ../auth/login.php');
    }
    exit();
}

/**
 * Check admin privileges
 */
function hasAdminPrivilege($privilege = null) {
    // Admin has all privileges
    return true;
}

/**
 * Get admin dashboard data
 */
function getAdminDashboardData() {
    $db = getDB();
    $stats = [];
    
    // Total customers
    $db->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $stats['total_customers'] = $db->single()['total'];
    
    // Total savings
    $db->query("SELECT COALESCE(SUM(balance), 0) as total FROM savings_accounts WHERE status = 'active'");
    $stats['total_savings'] = $db->single()['total'];
    
    // Active loans
    $db->query("SELECT COUNT(*) as count, COALESCE(SUM(balance), 0) as total 
              FROM loans WHERE status IN ('active', 'disbursed')");
    $result = $db->single();
    $stats['active_loans'] = $result['count'];
    $stats['outstanding_loans'] = $result['total'];
    
    // Today's collections
    $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM loan_repayments WHERE DATE(payment_date) = CURDATE()");
    $stats['today_collections'] = $db->single()['total'];
    
    // Month collections
    $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM loan_repayments 
              WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $stats['month_collections'] = $db->single()['total'];
    
    // Total employees
    $db->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'employee' AND is_active = 1");
    $stats['total_employees'] = $db->single()['total'];
    
    // Pending loan applications
    $db->query("SELECT COUNT(*) as total FROM loans WHERE status = 'pending'");
    $stats['pending_loans'] = $db->single()['total'];
    
    // Today's new customers
    $db->query("SELECT COUNT(*) as total FROM customers WHERE DATE(created_at) = CURDATE()");
    $stats['today_new_customers'] = $db->single()['total'];
    
    // Default rate
    $db->query("SELECT 
                COUNT(CASE WHEN status = 'defaulted' THEN 1 END) as defaulted,
                COUNT(*) as total
              FROM loans WHERE status IN ('active', 'completed', 'defaulted')");
    $result = $db->single();
    $stats['default_rate'] = $result['total'] > 0 ? 
        round(($result['defaulted'] / $result['total']) * 100, 2) : 0;
    
    return $stats;
}
?>