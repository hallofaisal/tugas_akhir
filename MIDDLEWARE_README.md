# 🔒 Middleware System - Role-Based Access Control

## Overview

Sistem middleware yang komprehensif untuk mengimplementasikan Role-Based Access Control (RBAC) pada aplikasi PHP. Sistem ini menyediakan berbagai lapisan keamanan dan otorisasi yang dapat dikonfigurasi dengan mudah.

## 🏗️ Arsitektur Sistem

### File Struktur
```
includes/
├── middleware.php          # Core middleware classes dan functions
├── middleware_config.php   # Konfigurasi roles, permissions, dan routes
├── middleware_router.php   # Auto-router untuk middleware protection
├── auth.php               # Authentication helper (legacy)
└── session_handler.php    # Session management

middleware_demo.php        # Demo page untuk testing middleware
```

## 🔧 Komponen Utama

### 1. Middleware Classes

#### Base Middleware
```php
abstract class Middleware {
    protected $next;
    
    public function setNext(Middleware $next);
    public function handle($request);
}
```

#### AuthMiddleware
- Memvalidasi apakah user sudah login
- Mengecek session timeout
- Redirect ke login page jika belum authenticated

#### RoleMiddleware
- Memvalidasi role user
- Log unauthorized access attempts
- Redirect jika role tidak sesuai

#### PermissionMiddleware
- Memvalidasi permission user
- Mendukung multiple permissions
- Role-based permission mapping

#### CSRFMiddleware
- Validasi CSRF token untuk POST requests
- Mencegah Cross-Site Request Forgery
- Auto-generate dan validate tokens

#### RateLimitMiddleware
- Membatasi jumlah request per IP/user
- Konfigurasi time window dan max requests
- File-based rate limiting (production: use Redis/DB)

#### SecurityHeadersMiddleware
- Set security headers otomatis
- Content Security Policy
- XSS protection, frame options, dll

### 2. Middleware Chain
```php
class MiddlewareChain {
    public function add(Middleware $middleware);
    public function execute($request = null);
}
```

## 🎯 Cara Penggunaan

### 1. Basic Protection

#### Manual Middleware Application
```php
// Include middleware system
require_once 'includes/middleware.php';
require_once 'includes/middleware_config.php';

// Apply protection
requireAuth();                    // Require login
requireRole('admin');             // Require admin role
requirePermission('user_manage'); // Require specific permission
requireCSRF();                    // Require CSRF protection
```

#### Automatic Route Protection
```php
// Include router
require_once 'includes/middleware_router.php';

// Auto-apply protection based on route config
$router = applyMiddlewareProtection();
```

### 2. Configuration

#### Role Configuration
```php
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
```

#### Permission Configuration
```php
define('PERMISSIONS', [
    'user_manage' => [
        'name' => 'Kelola Pengguna',
        'description' => 'Mengelola data pengguna sistem',
        'roles' => ['admin']
    ],
    'book_view' => [
        'name' => 'Lihat Buku',
        'description' => 'Melihat data buku',
        'roles' => ['admin', 'guru', 'siswa']
    ]
]);
```

#### Route Protection Configuration
```php
define('ROUTE_PROTECTION', [
    '/admin/' => [
        'middleware' => ['auth', 'role:admin'],
        'permissions' => []
    ],
    '/admin/books.php' => [
        'middleware' => ['auth', 'role:admin'],
        'permissions' => ['book_manage']
    ]
]);
```

### 3. Helper Functions

#### Authentication & Authorization
```php
// Check if user is logged in
if (isset($_SESSION['user_id'])) { ... }

// Check user role
if ($_SESSION['role'] === 'admin') { ... }

// Check permissions
if (hasPermission('book_manage')) { ... }

// Get user permissions
$permissions = getUserPermissions();

// Check if can perform action
if (canPerformAction('view_students')) { ... }
```

#### Security Functions
```php
// Generate CSRF token
$token = generateCSRFToken();

// Display flash messages
displayFlashMessages();

// Log user activity
logUserActivity('page_access', ['route' => '/admin/']);
```

#### Route Access
```php
// Check if user can access route
if (canAccessRoute('/admin/books.php')) { ... }

// Generate navigation menu
$menu = generateNavigationMenu();
displayNavigationMenu();
```

## 🛡️ Fitur Keamanan

### 1. Session Security
- Session timeout (30 menit default)
- Session regeneration
- Secure session handling
- Activity logging

### 2. CSRF Protection
- Auto-generate CSRF tokens
- Validate tokens on POST requests
- Secure token comparison
- Token expiration

### 3. Rate Limiting
- Request per IP/user limiting
- Configurable time windows
- Abuse prevention
- Logging of rate limit violations

### 4. Security Headers
- Content Security Policy
- X-Frame-Options
- X-XSS-Protection
- X-Content-Type-Options
- Referrer-Policy

### 5. Input Validation
- XSS prevention
- SQL injection prevention
- Input sanitization
- Output escaping

## 📊 Monitoring & Logging

### Activity Logging
```php
logUserActivity('action_name', [
    'user_id' => $_SESSION['user_id'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'data' => $additionalData
]);
```

### Error Logging
- Unauthorized access attempts
- Permission violations
- CSRF token failures
- Rate limit violations

### Access Monitoring
- Page access logging
- User session tracking
- Security event monitoring

## 🔄 Migration Guide

### From Old Auth System
```php
// Old way
require_once '../includes/auth.php';
require_admin();

// New way
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
requireAdmin();
```

### Adding New Routes
1. Add route to `ROUTE_PROTECTION` in `middleware_config.php`
2. Define required middleware and permissions
3. Use `applyMiddlewareProtection()` in the page

### Adding New Permissions
1. Add permission to `PERMISSIONS` in `middleware_config.php`
2. Define roles that have access
3. Use `hasPermission()` or `requirePermission()`

## 🧪 Testing

### Demo Page
Akses `middleware_demo.php` untuk:
- Melihat informasi user saat ini
- Test akses ke berbagai route
- Melihat permission overview
- Demo fungsi middleware

### Manual Testing
```php
// Test permission
if (hasPermission('book_manage')) {
    echo "User can manage books";
}

// Test role
if ($_SESSION['role'] === 'admin') {
    echo "User is admin";
}

// Test route access
if (canAccessRoute('/admin/books.php')) {
    echo "User can access books page";
}
```

## ⚙️ Konfigurasi

### Environment Variables
```php
// Session timeout (seconds)
define('SESSION_TIMEOUT', 30 * 60);

// Rate limiting
define('RATE_LIMIT_MAX', 100);
define('RATE_LIMIT_WINDOW', 3600);

// CSRF token expiry
define('CSRF_TOKEN_EXPIRY', 3600);
```

### Custom Middleware
```php
class CustomMiddleware extends Middleware {
    public function handle($request) {
        // Custom logic here
        return parent::handle($request);
    }
}

// Usage
$chain = new MiddlewareChain();
$chain->add(new CustomMiddleware())
      ->add(new AuthMiddleware())
      ->execute();
```

## 🚀 Best Practices

### 1. Security
- Always use HTTPS in production
- Regularly rotate session keys
- Monitor access logs
- Implement proper error handling

### 2. Performance
- Use Redis for rate limiting in production
- Cache permission checks
- Optimize database queries
- Use connection pooling

### 3. Maintenance
- Regular security audits
- Update dependencies
- Monitor error logs
- Backup configurations

### 4. Development
- Use consistent naming conventions
- Document custom middleware
- Test thoroughly
- Follow PSR standards

## 📝 Contoh Implementasi

### Admin Page
```php
<?php
require_once 'includes/middleware.php';
require_once 'includes/middleware_config.php';

// Apply protection
requireAdmin();
requirePermission('user_manage');

// Page content
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Admin Dashboard</h1>
    <?php displayFlashMessages(); ?>
    <!-- Content here -->
</body>
</html>
```

### Student Page
```php
<?php
require_once 'includes/middleware.php';
require_once 'includes/middleware_config.php';

// Apply protection
requireSiswa();
requirePermission('grade_view');

// Page content
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Student Dashboard</h1>
    <?php displayFlashMessages(); ?>
    <!-- Content here -->
</body>
</html>
```

## 🔧 Troubleshooting

### Common Issues

#### "Undefined constant PDO::MYSQL_ATTR_INIT_COMMAND"
- Solution: Remove or comment out the problematic line in `db.php`
- Alternative: Use different PDO options

#### Session not working
- Check session configuration
- Verify session storage permissions
- Check session timeout settings

#### Permission denied errors
- Verify user role in database
- Check permission configuration
- Ensure proper session data

#### CSRF token errors
- Check token generation
- Verify form includes token
- Check token expiration

### Debug Mode
```php
// Enable debug logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log middleware execution
logUserActivity('debug', ['middleware' => 'executed']);
```

## 📚 References

- [PHP Session Security](https://www.php.net/manual/en/session.security.php)
- [OWASP Security Guidelines](https://owasp.org/www-project-top-ten/)
- [CSRF Protection](https://owasp.org/www-community/attacks/csrf)
- [Rate Limiting Best Practices](https://cloud.google.com/architecture/rate-limiting-strategies-techniques)

---

**Note**: Sistem middleware ini dirancang untuk memberikan keamanan yang kuat sambil tetap mudah digunakan. Pastikan untuk selalu mengikuti best practices keamanan dan melakukan testing yang menyeluruh sebelum deployment ke production. 