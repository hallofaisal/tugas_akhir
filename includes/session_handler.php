<?php
/**
 * Session Handler
 * File: includes/session_handler.php
 * Description: Handles session management, timeouts, and security
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if session has expired
 * @param int $timeout_minutes Session timeout in minutes
 * @return bool True if session expired, false otherwise
 */
function is_session_expired($timeout_minutes = 30) {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    $timeout_seconds = $timeout_minutes * 60;
    $current_time = time();
    $login_time = $_SESSION['login_time'];
    
    return ($current_time - $login_time) > $timeout_seconds;
}

/**
 * Update session activity time
 */
function update_session_activity() {
    $_SESSION['last_activity'] = time();
}

/**
 * Check and handle session timeout
 * @param int $timeout_minutes Session timeout in minutes
 * @return bool True if session is still valid, false if expired
 */
function check_session_timeout($timeout_minutes = 30) {
    if (is_session_expired($timeout_minutes)) {
        // Session expired, logout user
        logout_user();
        return false;
    }
    
    // Update activity time
    update_session_activity();
    return true;
}

/**
 * Logout user and destroy session
 */
function logout_user() {
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
    
    // Set timeout message
    $_SESSION['logout_message'] = "Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.";
    $_SESSION['logout_user'] = $userName;
    $_SESSION['logout_role'] = $userRole;
    
    // Log timeout activity
    error_log("Session timeout: $userName ($userRole) at " . date('Y-m-d H:i:s'));
}

/**
 * Regenerate session ID for security
 */
function regenerate_session_id() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Set session security headers
 */
function set_session_security_headers() {
    // Set secure session cookie if using HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Set HTTP-only flag
    ini_set('session.cookie_httponly', 1);
    
    // Set same-site attribute
    ini_set('session.cookie_samesite', 'Strict');
}

/**
 * Initialize secure session
 */
function init_secure_session() {
    set_session_security_headers();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['session_regenerated']) || 
        (time() - $_SESSION['session_regenerated']) > 300) { // 5 minutes
        regenerate_session_id();
        $_SESSION['session_regenerated'] = time();
    }
}

/**
 * Get session info for debugging
 * @return array Session information
 */
function get_session_info() {
    return [
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'login_time' => $_SESSION['login_time'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null,
        'session_started' => $_SESSION['session_regenerated'] ?? null
    ];
}

// Auto-check session timeout on every request
if (isset($_SESSION['user_id'])) {
    check_session_timeout();
}
?> 
