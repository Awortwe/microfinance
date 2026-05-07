<?php
/**
 * Common Helper Functions
 * Utility functions used throughout the application
 * NOTE: Some functions have been moved to config/config.php
 */

/**
 * Generate customer code
 */
function generateCustomerCode() {
    $result = dbSingle("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM customers");
    return 'CUS' . str_pad($result['next_id'], 6, '0', STR_PAD_LEFT);
}

/**
 * Generate loan number
 */
function generateLoanNumber() {
    $result = dbSingle("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM loans");
    return 'LN' . str_pad($result['next_id'], 8, '0', STR_PAD_LEFT);
}

/**
 * Generate savings account number
 */
function generateSavingsAccountNumber() {
    $result = dbSingle("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM savings_accounts");
    return 'SAV' . str_pad($result['next_id'], 8, '0', STR_PAD_LEFT);
}

/**
 * Get customer full name by ID
 */
function getCustomerName($customer_id) {
    $result = dbSingle("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM customers WHERE id = :id", 
        [':id' => $customer_id]);
    return $result ? $result['full_name'] : 'Unknown';
}

/**
 * Get customer by code
 */
function getCustomerByCode($customer_code) {
    return dbSingle("SELECT * FROM customers WHERE customer_code = :code", 
        [':code' => $customer_code]);
}

/**
 * Get loan by number
 */
function getLoanByNumber($loan_number) {
    return dbSingle("SELECT * FROM loans WHERE loan_number = :number", 
        [':number' => $loan_number]);
}

/**
 * Get savings account by number
 */
function getSavingsAccount($account_number) {
    return dbSingle("SELECT * FROM savings_accounts WHERE account_number = :number", 
        [':number' => $account_number]);
}

/**
 * Check if customer has active loan
 */
function hasActiveLoan($customer_id) {
    $result = dbSingle("SELECT COUNT(*) as count FROM loans WHERE customer_id = :id AND status IN ('active', 'disbursed')", 
        [':id' => $customer_id]);
    return $result['count'] > 0;
}

/**
 * Get customer active loans count
 */
function getActiveLoansCount($customer_id) {
    $result = dbSingle("SELECT COUNT(*) as count FROM loans WHERE customer_id = :id AND status IN ('active', 'disbursed')", 
        [':id' => $customer_id]);
    return $result['count'];
}

/**
 * Get total savings balance for customer
 */
function getCustomerSavingsBalance($customer_id) {
    $result = dbSingle("SELECT COALESCE(SUM(balance), 0) as total FROM savings_accounts WHERE customer_id = :id AND status = 'active'", 
        [':id' => $customer_id]);
    return $result['total'];
}

/**
 * Get total loan balance for customer
 */
function getCustomerLoanBalance($customer_id) {
    $result = dbSingle("SELECT COALESCE(SUM(balance), 0) as total FROM loans WHERE customer_id = :id AND status IN ('active', 'disbursed')", 
        [':id' => $customer_id]);
    return $result['total'];
}

/**
 * Calculate loan schedule
 */
function calculateLoanSchedule($principal, $interest_rate, $duration_months, $interest_type = 'reducing_balance') {
    $schedule = [];
    $principal = (float)$principal;
    $interest_rate = (float)$interest_rate;
    $duration_months = (int)$duration_months;
    
    if ($interest_type == 'flat') {
        $monthly_principal = $principal / $duration_months;
        $monthly_interest = ($principal * ($interest_rate / 100)) / 12;
        $monthly_payment = $monthly_principal + $monthly_interest;
        $balance = $principal;
        
        for ($i = 1; $i <= $duration_months; $i++) {
            $balance -= $monthly_principal;
            $schedule[] = [
                'installment' => $i,
                'principal' => round($monthly_principal, 2),
                'interest' => round($monthly_interest, 2),
                'total' => round($monthly_payment, 2),
                'balance' => round($balance, 2)
            ];
        }
    } else {
        // Reducing balance
        $monthly_principal = $principal / $duration_months;
        $balance = $principal;
        
        for ($i = 1; $i <= $duration_months; $i++) {
            $monthly_interest = ($balance * ($interest_rate / 100)) / 12;
            $monthly_payment = $monthly_principal + $monthly_interest;
            $balance -= $monthly_principal;
            
            $schedule[] = [
                'installment' => $i,
                'principal' => round($monthly_principal, 2),
                'interest' => round($monthly_interest, 2),
                'total' => round($monthly_payment, 2),
                'balance' => round($balance, 2)
            ];
        }
    }
    
    return $schedule;
}

/**
 * Calculate total interest
 */
function calculateTotalInterest($principal, $interest_rate, $duration_months, $interest_type = 'reducing_balance') {
    $principal = (float)$principal;
    $interest_rate = (float)$interest_rate;
    $duration_months = (int)$duration_months;
    $total_interest = 0;
    
    if ($interest_type == 'flat') {
        $total_interest = $principal * ($interest_rate / 100) * ($duration_months / 12);
    } else {
        $schedule = calculateLoanSchedule($principal, $interest_rate, $duration_months, 'reducing_balance');
        foreach ($schedule as $installment) {
            $total_interest += $installment['interest'];
        }
    }
    
    return round($total_interest, 2);
}

/**
 * Send notification to user
 */
function sendNotification($user_id, $title, $message, $type = 'info', $link = null) {
    if (!NOTIFICATIONS_ENABLED) {
        return;
    }
    
    try {
        dbExecute(
            "INSERT INTO notifications (user_id, title, message, type, link) 
             VALUES (:user_id, :title, :message, :type, :link)",
            [
                ':user_id' => $user_id,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':link' => $link
            ]
        );
    } catch (Exception $e) {
        error_log("Notification Error: " . $e->getMessage());
    }
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    $stats = [];
    
    // Total customers
    $result = dbSingle("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $stats['total_customers'] = $result['total'];
    
    // Total savings
    $result = dbSingle("SELECT COALESCE(SUM(balance), 0) as total FROM savings_accounts WHERE status = 'active'");
    $stats['total_savings'] = $result['total'];
    
    // Active loans
    $result = dbSingle("SELECT COUNT(*) as count, COALESCE(SUM(balance), 0) as total FROM loans WHERE status IN ('active', 'disbursed')");
    $stats['active_loans'] = $result['count'];
    $stats['outstanding_loans'] = $result['total'];
    
    // Today's collections
    $result = dbSingle("SELECT COALESCE(SUM(amount), 0) as total FROM loan_repayments WHERE DATE(payment_date) = CURDATE()");
    $stats['today_collections'] = $result['total'];
    
    // Month collections
    $result = dbSingle("SELECT COALESCE(SUM(amount), 0) as total FROM loan_repayments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $stats['month_collections'] = $result['total'];
    
    return $stats;
}
?>