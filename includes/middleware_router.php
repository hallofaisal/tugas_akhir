<?php
/**
 * Middleware Router
 * File: includes/middleware_router.php
 * Description: Automatically applies middleware protection to routes
 */

// Include required files
require_once 'middleware.php';
require_once 'middleware_config.php';

/**
 * Middleware Router Class
 * Automatically applies middleware based on route configuration
 */
class MiddlewareRouter {
    private $currentRoute;
    private $middlewareChain;
    
    public function __construct() {
        $this->currentRoute = $this->getCurrentRoute();
        $this->middlewareChain = new MiddlewareChain();
        $this->applyRouteProtection();
    }
    
    /**
     * Get current route
     * @return string
     */
    private function getCurrentRoute() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Remove base path if exists
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($scriptName);
        
        if ($basePath !== '/') {
            $path = str_replace($basePath, '', $path);
        }
        
        // Ensure path starts with /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        
        return $path;
    }
    
    /**
     * Apply route protection based on configuration
     */
    private function applyRouteProtection() {
        $protection = getRouteProtection($this->currentRoute);
        
        // Always add security headers
        $this->middlewareChain->add(new SecurityHeadersMiddleware());
        
        // Apply configured middleware
        foreach ($protection['middleware'] as $middleware) {
            $this->addMiddleware($middleware);
        }
        
        // Apply permission checks
        if (!empty($protection['permissions'])) {
            $this->middlewareChain->add(new PermissionMiddleware($protection['permissions']));
        }
        
        // Apply CSRF protection for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->middlewareChain->add(new CSRFMiddleware());
        }
        
        // Apply rate limiting
        $this->middlewareChain->add(new RateLimitMiddleware(
            getMiddlewareConfig('rate_limit.max_requests', 100),
            getMiddlewareConfig('rate_limit.time_window', 3600)
        ));
    }
    
    /**
     * Add middleware based on string configuration
     * @param string $middleware
     */
    private function addMiddleware($middleware) {
        switch ($middleware) {
            case 'auth':
                $this->middlewareChain->add(new AuthMiddleware());
                break;
                
            case (preg_match('/^role:(.+)$/', $middleware, $matches) ? true : false):
                $role = $matches[1];
                $this->middlewareChain->add(new RoleMiddleware($role));
                break;
                
            case 'csrf':
                $this->middlewareChain->add(new CSRFMiddleware());
                break;
                
            case 'rate_limit':
                $this->middlewareChain->add(new RateLimitMiddleware());
                break;
        }
    }
    
    /**
     * Execute middleware chain
     */
    public function execute() {
        return $this->middlewareChain->execute();
    }
    
    /**
     * Get current route
     * @return string
     */
    public function getRoute() {
        return $this->currentRoute;
    }
    
    /**
     * Check if route is protected
     * @return bool
     */
    public function isProtected() {
        $protection = getRouteProtection($this->currentRoute);
        return !empty($protection['middleware']) || !empty($protection['permissions']);
    }
    
    /**
     * Get route protection info
     * @return array
     */
    public function getProtectionInfo() {
        return getRouteProtection($this->currentRoute);
    }
}

/**
 * Auto-apply middleware protection
 * Call this at the beginning of each page
 */
function applyMiddlewareProtection() {
    $router = new MiddlewareRouter();
    $router->execute();
    
    // Log access if user is logged in
    if (isset($_SESSION['user_id'])) {
        logUserActivity('page_access', [
            'route' => $router->getRoute(),
            'method' => $_SERVER['REQUEST_METHOD'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    return $router;
}

/**
 * Check if current user can access route
 * @param string $route
 * @return bool
 */
function canAccessRoute($route) {
    $protection = getRouteProtection($route);
    
    // If no protection, allow access
    if (empty($protection['middleware']) && empty($protection['permissions'])) {
        return true;
    }
    
    // Check authentication
    if (in_array('auth', $protection['middleware'])) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
    }
    
    // Check role
    foreach ($protection['middleware'] as $middleware) {
        if (preg_match('/^role:(.+)$/', $middleware, $matches)) {
            $requiredRole = $matches[1];
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
                return false;
            }
        }
    }
    
    // Check permissions
    if (!empty($protection['permissions'])) {
        $userPermissions = getUserPermissions();
        foreach ($protection['permissions'] as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Generate navigation menu based on user permissions
 * @return array
 */
function generateNavigationMenu() {
    $menu = [];
    $userRole = $_SESSION['role'] ?? '';
    
    // Public menu items
    $menu[] = [
        'url' => '/',
        'title' => 'Beranda',
        'icon' => '🏠'
    ];
    
    if (!isset($_SESSION['user_id'])) {
        $menu[] = [
            'url' => '/login.php',
            'title' => 'Login',
            'icon' => '🔐'
        ];
        return $menu;
    }
    
    // Admin menu
    if ($userRole === 'admin') {
        $menu[] = [
            'url' => '/admin/',
            'title' => 'Dashboard Admin',
            'icon' => '👨‍💼'
        ];
        
        if (hasPermission('book_manage')) {
            $menu[] = [
                'url' => '/admin/books.php',
                'title' => 'Kelola Buku',
                'icon' => '📚'
            ];
        }
        
        if (hasPermission('borrowing_manage')) {
            $menu[] = [
                'url' => '/admin/borrowings.php',
                'title' => 'Kelola Peminjaman',
                'icon' => '📖'
            ];
        }
        
        if (hasPermission('student_manage')) {
            $menu[] = [
                'url' => '/admin/students.php',
                'title' => 'Kelola Siswa',
                'icon' => '👨‍🎓'
            ];
        }
        
        if (hasPermission('visitor_manage')) {
            $menu[] = [
                'url' => '/admin/visitors.php',
                'title' => 'Data Pengunjung',
                'icon' => '👥'
            ];
        }
    }
    
    // Student menu
    if ($userRole === 'siswa') {
        $menu[] = [
            'url' => '/siswa/',
            'title' => 'Dashboard Siswa',
            'icon' => '👨‍🎓'
        ];
        
        if (hasPermission('grade_view')) {
            $menu[] = [
                'url' => '/siswa/grades.php',
                'title' => 'Nilai Saya',
                'icon' => '📝'
            ];
        }
        
        if (hasPermission('borrowing_view')) {
            $menu[] = [
                'url' => '/siswa/borrowings.php',
                'title' => 'Peminjaman',
                'icon' => '📚'
            ];
        }
        
        if (hasPermission('profile_view')) {
            $menu[] = [
                'url' => '/siswa/profile.php',
                'title' => 'Profil',
                'icon' => '👤'
            ];
        }
    }
    
    // Common menu items for logged-in users
    $menu[] = [
        'url' => '/logout_confirm.php',
        'title' => 'Logout',
        'icon' => '🚪'
    ];
    
    return $menu;
}

/**
 * Display navigation menu
 */
function displayNavigationMenu() {
    $menu = generateNavigationMenu();
    
    echo '<ul>';
    foreach ($menu as $item) {
        $isActive = ($_SERVER['REQUEST_URI'] === $item['url']) ? ' class="active"' : '';
        echo '<li><a href="' . htmlspecialchars($item['url']) . '"' . $isActive . '>';
        echo $item['icon'] . ' ' . htmlspecialchars($item['title']);
        echo '</a></li>';
    }
    echo '</ul>';
}

/**
 * Check if user can perform action
 * @param string $action
 * @return bool
 */
function canPerformAction($action) {
    $userRole = $_SESSION['role'] ?? '';
    $userPermissions = getUserPermissions();
    
    // Check if action is a permission
    if (in_array($action, $userPermissions)) {
        return true;
    }
    
    // Check role-based actions
    $roleActions = [
        'admin' => ['all'],
        'guru' => ['view_students', 'manage_grades', 'view_reports'],
        'siswa' => ['view_own_profile', 'view_own_grades', 'borrow_books']
    ];
    
    if (isset($roleActions[$userRole])) {
        return in_array($action, $roleActions[$userRole]) || in_array('all', $roleActions[$userRole]);
    }
    
    return false;
}

/**
 * Require action permission
 * @param string $action
 * @param string $redirectUrl
 */
function requireAction($action, $redirectUrl = 'index.php') {
    if (!canPerformAction($action)) {
        $_SESSION['flash_message'] = 'Anda tidak memiliki izin untuk melakukan aksi ini.';
        $_SESSION['flash_type'] = 'error';
        
        logUserActivity('unauthorized_action', [
            'action' => $action,
            'user_id' => $_SESSION['user_id'] ?? 'unknown',
            'role' => $_SESSION['role'] ?? 'none'
        ]);
        
        header("Location: $redirectUrl");
        exit();
    }
}
?> 
