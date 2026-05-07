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

// Check if already blacklisted
if ($customer['status'] == 'blacklisted') {
    setFlash('warning', 'Customer is already blacklisted');
    redirect('index.php');
}

// Blacklist the customer
dbExecute(
    "UPDATE customers SET status = 'blacklisted' WHERE id = :id AND status != 'blacklisted'",
    [':id' => $customer_id]
);

// Log the activity
logActivity('Customer Blacklisted', 'customers', $customer_id);

setFlash('success', 'Customer "' . $customer['first_name'] . ' ' . $customer['last_name'] . '" has been blacklisted successfully');
redirect('index.php');
?>