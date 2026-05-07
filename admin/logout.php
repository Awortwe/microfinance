<?php
/**
 * Admin Logout Script
 * Destroys session and redirects to admin login page
 */

require_once '../config/init.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    logActivity('Logout', 'auth', $_SESSION['user_id']);
    
    // Clear remember me token from database
    try {
        $db = getDB();
        $db->query("UPDATE users SET remember_token = NULL WHERE id = :id");
        $db->bind(':id', $_SESSION['user_id']);
        $db->execute();
    } catch (Exception $e) {
        error_log("Logout Error: " . $e->getMessage());
    }
}

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for flash message
session_start();
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'You have been logged out successfully.'
];

// Redirect to admin login page
header("Location: login.php");
exit();
?>