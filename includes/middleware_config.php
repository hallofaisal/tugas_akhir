<?php
/**
 * Middleware Configuration
 * File: includes/middleware_config.php
 * Description: Configuration settings for middleware and permissions
 */

// Middleware Configuration
define('MIDDLEWARE_CONFIG', [
    'session_timeout' => 30 * 60, // 30 minutes in seconds
    'csrf_token_expiry' => 3600, // 1 hour
    'rate_limit' => [
        'max_requests' => 100,
        'time_window' => 3600 // 1 hour
    ],
    'security' => [
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 minutes
    ]
]);

// Role Definitions
define('ROLES', [
    'admin' => [
        'name' => 'Administrator',
        'description' => 'Full system access',
        'level' => 3
    ],
    'guru' => [
        'name' => 'Guru',
        'description' => 'Teacher access',
        'level' => 2
    ],
    'siswa' => [
        'name' => 'Siswa',
        'description' => 'Student access',
        'level' => 1
    ]
]);

// Permission Definitions
define('PERMISSIONS', [
    // User Management
    'user_manage' => [
        'name' => 'Kelola Pengguna',
        'description' => 'Mengelola data pengguna sistem',
        'roles' => ['admin']
    ],
    'user_view' => [
        'name' => 'Lihat Pengguna',
        'description' => 'Melihat data pengguna',
        'roles' => ['admin', 'guru']
    ],
    
    // Student Management
    'student_manage' => [
        'name' => 'Kelola Siswa',
        'description' => 'Mengelola data siswa',
        'roles' => ['admin', 'guru']
    ],
    'student_view' => [
        'name' => 'Lihat Siswa',
        'description' => 'Melihat data siswa',
        'roles' => ['admin', 'guru', 'siswa']
    ],
    
    // Book Management
    'book_manage' => [
        'name' => 'Kelola Buku',
        'description' => 'Mengelola data buku perpustakaan',
        'roles' => ['admin']
    ],
    'book_view' => [
        'name' => 'Lihat Buku',
        'description' => 'Melihat data buku',
        'roles' => ['admin', 'guru', 'siswa']
    ],
    'book_borrow' => [
        'name' => 'Pinjam Buku',
        'description' => 'Meminjam buku dari perpustakaan',
        'roles' => ['admin', 'guru', 'siswa']
    ],
    
    // Borrowing Management
    'borrowing_manage' => [
        'name' => 'Kelola Peminjaman',
        'description' => 'Mengelola peminjaman buku',
        'roles' => ['admin']
    ],
    'borrowing_view' => [
        'name' => 'Lihat Peminjaman',
        'description' => 'Melihat data peminjaman',
        'roles' => ['admin', 'guru', 'siswa']
    ],
    
    // Grade Management
    'grade_manage' => [
        'name' => 'Kelola Nilai',
        'description' => 'Mengelola nilai siswa',
        'roles' => ['admin', 'guru']
    ],
    'grade_view' => [
        'name' => 'Lihat Nilai',
        'description' => 'Melihat nilai',
        'roles' => ['admin', 'guru', 'siswa']
    ],
    
    // Visitor Management
    'visitor_manage' => [
        'name' => 'Kelola Pengunjung',
        'description' => 'Mengelola data pengunjung',
        'roles' => ['admin']
    ],
    'visitor_view' => [
        'name' => 'Lihat Pengunjung',
        'description' => 'Melihat data pengunjung',
        'roles' => ['admin']
    ],
    
    // Reports
    'report_view' => [
        'name' => 'Lihat Laporan',
        'description' => 'Melihat laporan sistem',
        'roles' => ['admin', 'guru']
    ],
    'report_generate' => [
        'name' => 'Generate Laporan',
        'description' => 'Membuat laporan',
        'roles' => ['admin']
    ],
    
    // System Configuration
    'system_config' => [
        'name' => 'Konfigurasi Sistem',
        'description' => 'Mengatur konfigurasi sistem',
        'roles' => ['admin']
    ],
    
    // Data Management
    'data_export' => [
        'name' => 'Export Data',
        'description' => 'Mengekspor data sistem',
        'roles' => ['admin']
    ],
    'data_import' => [
        'name' => 'Import Data',
        'description' => 'Mengimpor data ke sistem',
        'roles' => ['admin']
    ],
    
    // Profile Management
    'profile_view' => [
        'name' => 'Lihat Profil',
        'description' => 'Melihat profil sendiri',
        'roles' => ['admin', 'guru', 'siswa']
    ],
    'profile_edit' => [
        'name' => 'Edit Profil',
        'description' => 'Mengedit profil sendiri',
        'roles' => ['admin', 'guru', 'siswa']
    ]
]);

// Route Protection Configuration
define('ROUTE_PROTECTION', [
    // Admin Routes
    '/admin/' => [
        'middleware' => ['auth', 'role:admin'],
        'permissions' => []
    ],
    '/admin/books.php' => [
        'middleware' => ['auth', 'role:admin'],
        'permissions' => ['book_manage']
    ],
    '/admin/borrowings.php' => [
        'middleware' => ['auth', 'role:admin'],
        'permissions' => ['borrowing_manage']
    ],
    '/admin/students.php' => [
        'middleware' => ['auth', 'role:admin'],
        'permissions' => ['student_manage']
    ],
    '/admin/visitors.php' => [
        'middleware' => ['auth', 'role:admin'],
        'permissions' => ['visitor_manage']
    ],
    
    // Student Routes
    '/siswa/' => [
        'middleware' => ['auth', 'role:siswa'],
        'permissions' => []
    ],
    '/siswa/grades.php' => [
        'middleware' => ['auth', 'role:siswa'],
        'permissions' => ['grade_view']
    ],
    '/siswa/borrowings.php' => [
        'middleware' => ['auth', 'role:siswa'],
        'permissions' => ['borrowing_view']
    ],
    '/siswa/profile.php' => [
        'middleware' => ['auth', 'role:siswa'],
        'permissions' => ['profile_view']
    ],
    
    // Public Routes (no protection needed)
    '/' => [
        'middleware' => [],
        'permissions' => []
    ],
    '/login.php' => [
        'middleware' => [],
        'permissions' => []
    ],
    '/logout.php' => [
        'middleware' => ['auth'],
        'permissions' => []
    ]
]);

// Security Headers Configuration
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self';"
]);

// Helper Functions

/**
 * Get role information
 * @param string $role
 * @return array|null
 */
function getRoleInfo($role) {
    return ROLES[$role] ?? null;
}

/**
 * Get permission information
 * @param string $permission
 * @return array|null
 */
function getPermissionInfo($permission) {
    return PERMISSIONS[$permission] ?? null;
}

/**
 * Check if role has permission
 * @param string $role
 * @param string $permission
 * @return bool
 */
function roleHasPermission($role, $permission) {
    $permissionInfo = getPermissionInfo($permission);
    if (!$permissionInfo) {
        return false;
    }
    
    return in_array($role, $permissionInfo['roles']);
}

/**
 * Get all permissions for a role
 * @param string $role
 * @return array
 */
function getRolePermissions($role) {
    $permissions = [];
    
    foreach (PERMISSIONS as $permission => $info) {
        if (in_array($role, $info['roles'])) {
            $permissions[] = $permission;
        }
    }
    
    return $permissions;
}

/**
 * Get route protection configuration
 * @param string $route
 * @return array
 */
function getRouteProtection($route) {
    return ROUTE_PROTECTION[$route] ?? [
        'middleware' => [],
        'permissions' => []
    ];
}

/**
 * Validate middleware configuration
 * @return array
 */
function validateMiddlewareConfig() {
    $errors = [];
    
    // Validate roles
    foreach (ROLES as $role => $info) {
        if (!isset($info['name']) || !isset($info['level'])) {
            $errors[] = "Invalid role configuration for: $role";
        }
    }
    
    // Validate permissions
    foreach (PERMISSIONS as $permission => $info) {
        if (!isset($info['name']) || !isset($info['roles'])) {
            $errors[] = "Invalid permission configuration for: $permission";
        }
        
        foreach ($info['roles'] as $role) {
            if (!isset(ROLES[$role])) {
                $errors[] = "Permission $permission references undefined role: $role";
            }
        }
    }
    
    return $errors;
}

/**
 * Get middleware configuration value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getMiddlewareConfig($key, $default = null) {
    $keys = explode('.', $key);
    $config = MIDDLEWARE_CONFIG;
    
    foreach ($keys as $k) {
        if (!isset($config[$k])) {
            return $default;
        }
        $config = $config[$k];
    }
    
    return $config;
}
?> 