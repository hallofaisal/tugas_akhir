<?php
/**
 * Logout Handler
 * File: logout.php
 * Description: Handles user logout and session cleanup
 */

// Include authentication helper
require_once 'includes/auth.php';

// Perform logout
logout();

// Redirect to login page with success message
session_start();
$_SESSION['logout_message'] = 'Anda berhasil logout. Silakan login kembali.';

// Redirect to login page
header('Location: login.php');
exit();
?> 