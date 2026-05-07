<?php
require_once '../../config/init.php';
require_once '../../includes/employee_check.php';

$account_id = $_GET['account_id'] ?? 0;

// Get account
$account = dbSingle("SELECT * FROM savings_accounts WHERE id = :id", [':id' => $account_id]);

if (!$account) {
    setFlash('error', 'Account not found');
} elseif ($account['balance'] > 0) {
    setFlash('error', 'Please withdraw all funds before closing the account. Current balance: ' . formatMoney($account['balance']));
} elseif ($account['status'] == 'closed') {
    setFlash('warning', 'Account is already closed');
} else {
    dbExecute(
        "UPDATE savings_accounts SET status = 'closed', closed_date = CURDATE() WHERE id = :id",
        [':id' => $account_id]
    );
    
    logActivity('Account Closed', 'savings', $account_id);
    setFlash('success', 'Account #' . $account['account_number'] . ' closed successfully');
}

redirect('index.php');
?>