<?php
require_once '../../config/init.php';
require_once '../../includes/admin_check.php';

$user_id = $_GET['id'] ?? 0;

// Prevent deleting own account
if ($user_id == $_SESSION['user_id']) {
    setFlash('error', 'You cannot deactivate your own account');
    redirect('index.php');
}

// Get user data
$user = dbSingle("SELECT * FROM users WHERE id = :id", [':id' => $user_id]);

if (!$user) {
    setFlash('error', 'User not found');
    redirect('index.php');
}

// Deactivate user
dbExecute("UPDATE users SET is_active = 0 WHERE id = :id", [':id' => $user_id]);

logActivity('User Deactivated', 'users', $user_id);

setFlash('success', 'User "' . $user['full_name'] . '" has been deactivated successfully');
redirect('index.php');
?>