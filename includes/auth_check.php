<?php
/**
 * Authentication Check Middleware
 * Verifies that user is logged in
 * Include this at the top of all protected pages
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the attempted URL for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Set flash message
    setFlash('warning', 'Please log in to access this page.');
    
    // Redirect to login page
    header('Location: ' . getLoginUrl());
    exit();
}

// Check if user account is still active
try {
    $db = getDB();
    
    $db->query("SELECT is_active FROM users WHERE id = :id");
    $db->bind(':id', $_SESSION['user_id']);
    $user = $db->single();
    
    if (!$user || !$user['is_active']) {
        // User account is deactivated
        session_destroy();
        setFlash('error', 'Your account has been deactivated. Please contact the administrator.');
        header('Location: ' . getLoginUrl());
        exit();
    }
} catch (Exception $e) {
    error_log("Auth Check Error: " . $e->getMessage());
}

/**
 * Get login URL based on current location
 */
function getLoginUrl() {
    $current_path = $_SERVER['PHP_SELF'];
    
    if (strpos($current_path, '/admin/') !== false) {
        return '../auth/login.php';
    } elseif (strpos($current_path, '/employee/') !== false) {
        return '../auth/login.php';
    } elseif (strpos($current_path, '/shared/') !== false) {
        return '../auth/login.php';
    } else {
        return 'auth/login.php';
    }
}

/**
 * Get current user data
 */
function getCurrentUser() {
    try {
        $db = getDB();
        
        $db->query("SELECT * FROM users WHERE id = :id");
        $db->bind(':id', $_SESSION['user_id']);
        return $db->single();
    } catch (Exception $e) {
        return null;
    }
}
?>