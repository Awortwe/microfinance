<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$loan_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    setFlash('error', 'Invalid action');
    redirect('approve.php');
}

// Get loan details
$loan = dbSingle("SELECT * FROM loans WHERE id = :id", [':id' => $loan_id]);

if (!$loan) {
    setFlash('error', 'Loan not found');
    redirect('approve.php');
}

// Check if loan is pending
if ($loan['status'] != 'pending') {
    setFlash('warning', 'This loan has already been processed');
    redirect('approve.php');
}

if ($action == 'approve') {
    // Approve the loan
    dbExecute(
        "UPDATE loans SET status = 'approved', approved_by = :admin_id, approval_date = CURDATE() WHERE id = :id",
        [
            ':admin_id' => $_SESSION['user_id'],
            ':id' => $loan_id
        ]
    );
    
    logActivity('Loan Approved', 'loans', $loan_id);
    setFlash('success', 'Loan #' . $loan['loan_number'] . ' has been approved successfully');
    
} elseif ($action == 'reject') {
    // Reject the loan
    dbExecute(
        "UPDATE loans SET status = 'rejected', approved_by = :admin_id, rejection_reason = 'Rejected by admin' WHERE id = :id",
        [
            ':admin_id' => $_SESSION['user_id'],
            ':id' => $loan_id
        ]
    );
    
    logActivity('Loan Rejected', 'loans', $loan_id);
    setFlash('warning', 'Loan #' . $loan['loan_number'] . ' has been rejected');
}

redirect('approve.php');
?>