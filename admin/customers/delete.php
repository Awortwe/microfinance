<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$customer_id = $_GET['id'] ?? 0;

// Check if customer exists
$customer = dbSingle("SELECT * FROM customers WHERE id = :id", [':id' => $customer_id]);

if (!$customer) {
    setFlash('error', 'Customer not found');
    redirect('index.php');
}

// Check if customer has active accounts
$hasActiveSavings = dbSingle(
    "SELECT COUNT(*) as count FROM savings_accounts WHERE customer_id = :id AND status = 'active'",
    [':id' => $customer_id]
);

$hasActiveLoans = dbSingle(
    "SELECT COUNT(*) as count FROM loans WHERE customer_id = :id AND status IN ('active', 'disbursed')",
    [':id' => $customer_id]
);

if ($hasActiveSavings['count'] > 0 || $hasActiveLoans['count'] > 0) {
    setFlash('error', 'Cannot delete customer with active savings accounts or loans. Please close all accounts first.');
    redirect('view.php?id=' . $customer_id);
}

// Soft delete - set status to inactive
dbExecute("UPDATE customers SET status = 'inactive' WHERE id = :id", [':id' => $customer_id]);

logActivity('Customer Deleted', 'customers', $customer_id);

setFlash('success', 'Customer "' . $customer['first_name'] . ' ' . $customer['last_name'] . '" has been deleted successfully');
redirect('index.php');
?>