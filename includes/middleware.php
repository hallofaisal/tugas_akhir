<?php
/**
 * Middleware System for Role-Based Access Control
 * File: includes/middleware.php
 * Description: Handles authentication, authorization, and security middleware
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Base Middleware Class
 */
abstract class Middleware {
    protected $next;
    
    public function setNext(Middleware $next) {
        $this->next = $next;
        return $next;
    }
    
    public function handle($request) {
        if ($this->next) {
            return $this->next->handle($request);
        }
        return true;
    }
}

/**
 * Authentication Middleware
 * Checks if user is logged in
 */
class AuthMiddleware extends Middleware {
    private $redirectUrl;
    
    public function __construct($redirectUrl = 'login.php') {
        $this->redirectUrl = $redirectUrl;
    }
    
    public function handle($request) {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            
            // Set flash message
            $_SESSION['flash_message'] = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';
            $_SESSION['flash_type'] = 'warning';
            
            header("Location: $this->redirectUrl");
            exit();
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time'])) {
            $timeout = 30 * 60; // 30 minutes
            if (time() - $_SESSION['login_time'] > $timeout) {
                // Session expired
                session_destroy();
                $_SESSION['flash_message'] = 'Sesi Anda telah berakhir. Silakan login kembali.';
                $_SESSION['flash_type'] = 'error';
                header("Location: $this->redirectUrl");
                exit();
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return parent::handle($request);
    }
}

/**
 * Role-Based Authorization Middleware
 * Checks if user has required role
 */
class RoleMiddleware extends Middleware {
    private $requiredRole;
    private $redirectUrl;
    
    public function __construct($requiredRole, $redirectUrl = 'index.php') {
        $this->requiredRole = $requiredRole;
        $this->redirectUrl = $redirectUrl;
    }
    
    public function handle($request) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $this->requiredRole) {
            // Log unauthorized access attempt
            error_log("Unauthorized access attempt: User ID " . ($_SESSION['user_id'] ?? 'unknown') . 
                     " tried to access " . $_SERVER['REQUEST_URI'] . 
                     " (required role: {$this->requiredRole}, user role: " . ($_SESSION['role'] ?? 'none') . ")");
            
            $_SESSION['flash_message'] = 'Anda tidak memiliki akses ke halaman ini.';
            $_SESSION['flash_type'] = 'error';
            
            header("Location: $this->redirectUrl");
            exit();
        }
        
        return parent::handle($request);
    }
}

/**
 * Permission-Based Authorization Middleware
 * Checks if user has specific permissions
 */
class PermissionMiddleware extends Middleware {
    private $requiredPermissions;
    private $redirectUrl;
    
    public function __construct($requiredPermissions, $redirectUrl = 'index.php') {
        $this->requiredPermissions = is_array($requiredPermissions) ? $requiredPermissions : [$requiredPermissions];
        $this->redirectUrl = $redirectUrl;
    }
    
    public function handle($request) {
        $userPermissions = $this->getUserPermissions();
        
        foreach ($this->requiredPermissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                error_log("Permission denied: User ID " . ($_SESSION['user_id'] ?? 'unknown') . 
                         " tried to access " . $_SERVER['REQUEST_URI'] . 
                         " (required permission: $permission)");
                
                $_SESSION['flash_message'] = 'Anda tidak memiliki izin untuk mengakses fitur ini.';
                $_SESSION['flash_type'] = 'error';
                
                header("Location: $this->redirectUrl");
                exit();
            }
        }
        
        return parent::handle($request);
    }
    
    private function getUserPermissions() {
        // Get user permissions based on role
        $role = $_SESSION['role'] ?? '';
        
        $permissions = [
            'admin' => [
                'user_manage',
                'student_manage',
                'book_manage',
                'borrowing_manage',
                'visitor_manage',
                'report_view',
                'system_config',
                'data_export',
                'data_import'
            ],
            'guru' => [
                'student_view',
                'grade_manage',
                'report_view',
                'book_view'
            ],
            'siswa' => [
                'profile_view',
                'grade_view',
                'book_borrow',
                'borrowing_view'
            ]
        ];
        
        return $permissions[$role] ?? [];
    }
}

/**
 * CSRF Protection Middleware
 * Validates CSRF tokens for POST requests
 */
class CSRFMiddleware extends Middleware {
    public function handle($request) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!$token || !$this->validateCSRFToken($token)) {
                error_log("CSRF token validation failed for user ID " . ($_SESSION['user_id'] ?? 'unknown'));
                
                $_SESSION['flash_message'] = 'Token keamanan tidak valid. Silakan coba lagi.';
                $_SESSION['flash_type'] = 'error';
                
                header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
                exit();
            }
        }
        
        return parent::handle($request);
    }
    
    private function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Rate Limiting Middleware
 * Prevents abuse by limiting request frequency
 */
class RateLimitMiddleware extends Middleware {
    private $maxRequests;
    private $timeWindow;
    
    public function __construct($maxRequests = 100, $timeWindow = 3600) {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function handle($request) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userId = $_SESSION['user_id'] ?? 'guest';
        $key = "rate_limit:{$ip}:{$userId}";
        
        if ($this->isRateLimited($key)) {
            error_log("Rate limit exceeded for IP: $ip, User ID: $userId");
            
            http_response_code(429);
            $_SESSION['flash_message'] = 'Terlalu banyak permintaan. Silakan coba lagi nanti.';
            $_SESSION['flash_type'] = 'error';
            
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit();
        }
        
        $this->incrementRequestCount($key);
        
        return parent::handle($request);
    }
    
    private function isRateLimited($key) {
        // Simple file-based rate limiting (in production, use Redis or database)
        $file = sys_get_temp_dir() . "/$key.txt";
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && (time() - $data['timestamp']) < $this->timeWindow) {
                return $data['count'] >= $this->maxRequests;
            }
        }
        
        return false;
    }
    
    private function incrementRequestCount($key) {
        $file = sys_get_temp_dir() . "/$key.txt";
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && (time() - $data['timestamp']) < $this->timeWindow) {
                $data['count']++;
            } else {
                $data = ['count' => 1, 'timestamp' => time()];
            }
        } else {
            $data = ['count' => 1, 'timestamp' => time()];
        }
        
        file_put_contents($file, json_encode($data));
    }
}

/**
 * Security Headers Middleware
 * Sets security headers for all responses
 */
class SecurityHeadersMiddleware extends Middleware {
    public function handle($request) {
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline'; " .
               "style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data:; " .
               "font-src 'self'; " .
               "connect-src 'self';";
        header("Content-Security-Policy: $csp");
        
        return parent::handle($request);
    }
}

/**
 * Middleware Chain Builder
 * Allows chaining multiple middleware
 */
class MiddlewareChain {
    private $middlewares = [];
    
    public function add(Middleware $middleware) {
        $this->middlewares[] = $middleware;
        return $this;
    }
    
    public function execute($request = null) {
        if (empty($this->middlewares)) {
            return true;
        }
        
        // Chain middlewares
        for ($i = 0; $i < count($this->middlewares) - 1; $i++) {
            $this->middlewares[$i]->setNext($this->middlewares[$i + 1]);
        }
        
        // Execute first middleware
        return $this->middlewares[0]->handle($request);
    }
}

/**
 * Helper Functions for Easy Middleware Usage
 */

/**
 * Require authentication
 * @param string $redirectUrl
 */
function requireAuth($redirectUrl = 'login.php') {
    $chain = new MiddlewareChain();
    $chain->add(new SecurityHeadersMiddleware())
          ->add(new AuthMiddleware($redirectUrl))
          ->execute();
}

/**
 * Require specific role
 * @param string $role
 * @param string $redirectUrl
 */
function requireRole($role, $redirectUrl = 'index.php') {
    $chain = new MiddlewareChain();
    $chain->add(new SecurityHeadersMiddleware())
          ->add(new AuthMiddleware())
          ->add(new RoleMiddleware($role, $redirectUrl))
          ->execute();
}

/**
 * Require admin role
 * @param string $redirectUrl
 */
function requireAdmin($redirectUrl = 'index.php') {
    requireRole('admin', $redirectUrl);
}

/**
 * Require siswa role
 * @param string $redirectUrl
 */
function requireSiswa($redirectUrl = 'index.php') {
    requireRole('siswa', $redirectUrl);
}

/**
 * Require guru role
 * @param string $redirectUrl
 */
function requireGuru($redirectUrl = 'index.php') {
    requireRole('guru', $redirectUrl);
}

/**
 * Require specific permissions
 * @param array|string $permissions
 * @param string $redirectUrl
 */
function requirePermission($permissions, $redirectUrl = 'index.php') {
    $chain = new MiddlewareChain();
    $chain->add(new SecurityHeadersMiddleware())
          ->add(new AuthMiddleware())
          ->add(new PermissionMiddleware($permissions, $redirectUrl))
          ->execute();
}

/**
 * Apply CSRF protection
 */
function requireCSRF() {
    $chain = new MiddlewareChain();
    $chain->add(new SecurityHeadersMiddleware())
          ->add(new AuthMiddleware())
          ->add(new CSRFMiddleware())
          ->execute();
}

/**
 * Apply rate limiting
 * @param int $maxRequests
 * @param int $timeWindow
 */
function applyRateLimit($maxRequests = 100, $timeWindow = 3600) {
    $chain = new MiddlewareChain();
    $chain->add(new SecurityHeadersMiddleware())
          ->add(new RateLimitMiddleware($maxRequests, $timeWindow))
          ->execute();
}

/**
 * Get user permissions
 * @return array
 */
function getUserPermissions() {
    $role = $_SESSION['role'] ?? '';
    
    $permissions = [
        'admin' => [
            'user_manage',
            'student_manage',
            'book_manage',
            'borrowing_manage',
            'visitor_manage',
            'report_view',
            'system_config',
            'data_export',
            'data_import'
        ],
        'guru' => [
            'student_view',
            'grade_manage',
            'report_view',
            'book_view'
        ],
        'siswa' => [
            'profile_view',
            'grade_view',
            'book_borrow',
            'borrowing_view'
        ]
    ];
    
    return $permissions[$role] ?? [];
}

/**
 * Check if user has permission
 * @param string $permission
 * @return bool
 */
function hasPermission($permission) {
    $permissions = getUserPermissions();
    return in_array($permission, $permissions);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Display flash messages
 */
function displayFlashMessages() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $message = $_SESSION['flash_message'];
        
        echo "<div class='alert alert-$type'>";
        echo htmlspecialchars($message);
        echo "</div>";
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Log user activity
 * @param string $action
 * @param array $data
 */
function logUserActivity($action, $data = []) {
    $userId = $_SESSION['user_id'] ?? 'unknown';
    $username = $_SESSION['username'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $timestamp = date('Y-m-d H:i:s');
    
    $logEntry = [
        'timestamp' => $timestamp,
        'user_id' => $userId,
        'username' => $username,
        'action' => $action,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'data' => $data
    ];
    
    error_log("User Activity: " . json_encode($logEntry));
}
?> 
