<?php
/**
 * Authentication Helper Functions
 * File: includes/auth.php
 * Description: Handles authentication, authorization, and session management
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function get_current_user_role() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function has_role($role) {
    return get_current_user_role() === $role;
}

/**
 * Check if user is admin
 * @return bool
 */
function is_admin() {
    return has_role('admin');
}

/**
 * Check if user is siswa
 * @return bool
 */
function is_siswa() {
    return has_role('siswa');
}

/**
 * Check if user is guru
 * @return bool
 */
function is_guru() {
    return has_role('guru');
}

/**
 * Require login - redirect if not logged in
 * @param string $redirect_url
 */
function require_login($redirect_url = 'login.php') {
    if (!is_logged_in()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Require specific role - redirect if user doesn't have role
 * @param string $role
 * @param string $redirect_url
 */
function require_role($role, $redirect_url = 'index.php') {
    require_login();
    if (!has_role($role)) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Require admin role
 * @param string $redirect_url
 */
function require_admin($redirect_url = 'index.php') {
    require_role('admin', $redirect_url);
}

/**
 * Require siswa role
 * @param string $redirect_url
 */
function require_siswa($redirect_url = 'index.php') {
    require_role('siswa', $redirect_url);
}

/**
 * Require guru role
 * @param string $redirect_url
 */
function require_guru($redirect_url = 'index.php') {
    require_role('guru', $redirect_url);
}

/**
 * Logout user
 */
function logout() {
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
}

/**
 * Validate user credentials
 * @param string $username
 * @param string $password
 * @param string $role
 * @return array|false User data if valid, false otherwise
 */
function validate_credentials($username, $password, $role) {
    try {
        $pdo = require_once __DIR__ . '/../db.php';
        
        $stmt = $pdo->prepare("
            SELECT id, username, password, email, full_name, role, is_active 
            FROM users 
            WHERE username = ? AND role = ? AND is_active = 1
        ");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Credential validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create user session
 * @param array $user
 */
function create_user_session($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
}

/**
 * Update last login time
 * @param int $user_id
 */
function update_last_login($user_id) {
    try {
        $pdo = require_once __DIR__ . '/../db.php';
        $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

/**
 * Check session timeout (optional security feature)
 * @param int $timeout_minutes
 * @return bool
 */
function check_session_timeout($timeout_minutes = 30) {
    if (!isset($_SESSION['login_time'])) {
        return true; // No login time set, consider expired
    }
    
    $timeout_seconds = $timeout_minutes * 60;
    $current_time = time();
    $login_time = $_SESSION['login_time'];
    
    if (($current_time - $login_time) > $timeout_seconds) {
        logout();
        return true; // Session expired
    }
    
    // Update login time
    $_SESSION['login_time'] = $current_time;
    return false; // Session still valid
}

/**
 * Generate CSRF token
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get user by ID
 * @param int $user_id
 * @return array|false
 */
function get_user_by_id($user_id) {
    try {
        $pdo = require_once __DIR__ . '/../db.php';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Failed to get user by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Get student data by user ID
 * @param int $user_id
 * @return array|false
 */
function get_student_by_user_id($user_id) {
    try {
        $pdo = require_once __DIR__ . '/../db.php';
        $stmt = $pdo->prepare("
            SELECT s.*, u.username, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.user_id = ? AND s.status = 'active'
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Failed to get student data: " . $e->getMessage());
        return false;
    }
}
?> 