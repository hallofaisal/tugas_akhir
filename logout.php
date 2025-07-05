<?php
/**
 * Logout Handler
 * File: logout.php
 * Description: Handles user logout and session cleanup
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include visitor logger
require_once 'db.php';
require_once 'includes/visitor_logger.php';

// Log visitor automatically before logout
$logger = new VisitorLogger($pdo);
$logger->logVisitor('logout.php');

// Store user info for logout message
$userName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? '';

// Clear all session data
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

// Start a new session for logout message
session_start();

// Set logout message
$_SESSION['logout_message'] = "Anda berhasil logout dari sistem.";
$_SESSION['logout_user'] = $userName;
$_SESSION['logout_role'] = $userRole;

// Log logout activity (optional)
error_log("User logout: $userName ($userRole) at " . date('Y-m-d H:i:s'));

// Redirect to login page
header('Location: login.php');
exit();
?> 
