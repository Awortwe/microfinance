<?php
/**
 * Employee Access Check
 * Verifies that logged-in user is an employee
 * Include this after config/init.php
 */

// First check if user is logged in
require_once __DIR__ . '/auth_check.php';

// Check if user is employee
if ($_SESSION['user_type'] !== 'employee') {
    // Log unauthorized access attempt
    logActivity('Unauthorized employee access attempt', 'auth');
    
    // Set flash message
    setFlash('error', 'You do not have permission to access this area.');
    
    // Redirect to appropriate dashboard
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../auth/login.php');
    }
    exit();
}

/**
 * Check employee permissions
 */
function hasEmployeePermission($permission) {
    $employee_permissions = [
        'view_customers' => true,
        'add_customers' => true,
        'edit_customers' => true,
        'delete_customers' => false,
        'create_loans' => true,
        'approve_loans' => false,
        'disburse_loans' => true,
        'process_repayments' => true,
        'manage_savings' => true,
        'view_reports' => true,
        'manage_users' => false,
    ];
    
    return isset($employee_permissions[$permission]) ? $employee_permissions[$permission] : false;
}

/**
 * Get employee-specific dashboard data
 */
function getEmployeeDashboardData() {
    $db = getDB();
    $employee_id = $_SESSION['user_id'];
    $stats = [];
    
    // Total customers (all active)
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
    
    // Today's collections (all)
    $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM loan_repayments WHERE DATE(payment_date) = CURDATE()");
    $stats['today_collections'] = $db->single()['total'];
    
    // My customers
    $db->query("SELECT COUNT(*) as total FROM customers WHERE created_by = :employee_id AND status = 'active'");
    $db->bind(':employee_id', $employee_id);
    $stats['my_customers'] = $db->single()['total'];
    
    // My loans processed
    $db->query("SELECT COUNT(*) as total FROM loans WHERE created_by = :employee_id");
    $db->bind(':employee_id', $employee_id);
    $stats['my_loans'] = $db->single()['total'];
    
    // My collections today
    $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM loan_repayments 
              WHERE processed_by = :employee_id AND DATE(payment_date) = CURDATE()");
    $db->bind(':employee_id', $employee_id);
    $stats['my_today_collections'] = $db->single()['total'];
    
    // My pending tasks
    $db->query("SELECT COUNT(*) as total FROM loans 
              WHERE created_by = :employee_id AND status = 'pending'");
    $db->bind(':employee_id', $employee_id);
    $stats['my_pending_loans'] = $db->single()['total'];
    
    // Today's savings deposits I processed
    $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM savings_transactions 
              WHERE processed_by = :employee_id AND transaction_type = 'deposit' 
              AND DATE(transaction_date) = CURDATE()");
    $db->bind(':employee_id', $employee_id);
    $stats['my_today_deposits'] = $db->single()['total'];
    
    return $stats;
}
?>