<?php
/**
 * Middleware Demo Page
 * File: middleware_demo.php
 * Description: Demonstrates the middleware system with different protection levels
 */

// Include middleware system
require_once 'includes/middleware.php';
require_once 'includes/middleware_config.php';
require_once 'includes/middleware_router.php';

// Apply automatic middleware protection
$router = applyMiddlewareProtection();

// Get current user info
$userRole = $_SESSION['role'] ?? 'guest';
$userPermissions = getUserPermissions();
$currentRoute = $router->getRoute();
$protectionInfo = $router->getProtectionInfo();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Middleware Demo - Sistem Informasi Sekolah</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .demo-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .permission-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
        }
        .permission-card.has-permission {
            border-color: #28a745;
            background: #f8fff9;
        }
        .permission-card.no-permission {
            border-color: #dc3545;
            background: #fff8f8;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-allowed {
            background: #d4edda;
            color: #155724;
        }
        .status-denied {
            background: #f8d7da;
            color: #721c24;
        }
        .status-protected {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔒 Middleware Demo</h1>
            <p>Demonstrasi sistem middleware untuk Role-Based Access Control</p>
        </header>

        <nav>
            <a href="index.php">🏠 Beranda</a>
            <a href="login.php">🔐 Login</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout_confirm.php">🚪 Logout</a>
            <?php endif; ?>
        </nav>

        <main>
            <!-- Current User Info -->
            <div class="demo-section">
                <h2>👤 Informasi Pengguna Saat Ini</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <p><strong>User ID:</strong> <?= htmlspecialchars($_SESSION['user_id']) ?></p>
                    <p><strong>Username:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
                    <p><strong>Role:</strong> 
                        <span class="status-badge status-allowed">
                            <?= htmlspecialchars(ucfirst($userRole)) ?>
                        </span>
                    </p>
                    <p><strong>Login Time:</strong> <?= date('Y-m-d H:i:s', $_SESSION['login_time'] ?? time()) ?></p>
                <?php else: ?>
                    <p><em>Belum login</em></p>
                <?php endif; ?>
            </div>

            <!-- Current Route Info -->
            <div class="demo-section">
                <h2>📍 Informasi Route Saat Ini</h2>
                <p><strong>Route:</strong> <?= htmlspecialchars($currentRoute) ?></p>
                <p><strong>Method:</strong> <?= htmlspecialchars($_SERVER['REQUEST_METHOD']) ?></p>
                <p><strong>Protected:</strong> 
                    <?php if ($router->isProtected()): ?>
                        <span class="status-badge status-protected">Ya</span>
                    <?php else: ?>
                        <span class="status-badge status-allowed">Tidak</span>
                    <?php endif; ?>
                </p>
                
                <?php if ($router->isProtected()): ?>
                    <h3>Middleware yang Diterapkan:</h3>
                    <ul>
                        <?php foreach ($protectionInfo['middleware'] as $middleware): ?>
                            <li><?= htmlspecialchars($middleware) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php if (!empty($protectionInfo['permissions'])): ?>
                        <h3>Permission yang Diperlukan:</h3>
                        <ul>
                            <?php foreach ($protectionInfo['permissions'] as $permission): ?>
                                <li><?= htmlspecialchars($permission) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Permission Overview -->
            <div class="demo-section">
                <h2>🔑 Overview Permission</h2>
                <div class="permission-grid">
                    <?php foreach (PERMISSIONS as $permission => $info): ?>
                        <?php 
                        $hasPermission = in_array($permission, $userPermissions);
                        $cardClass = $hasPermission ? 'has-permission' : 'no-permission';
                        ?>
                        <div class="permission-card <?= $cardClass ?>">
                            <h4><?= htmlspecialchars($info['name']) ?></h4>
                            <p><em><?= htmlspecialchars($info['description']) ?></em></p>
                            <p><strong>Permission:</strong> <code><?= htmlspecialchars($permission) ?></code></p>
                            <p><strong>Roles:</strong> <?= htmlspecialchars(implode(', ', $info['roles'])) ?></p>
                            <p><strong>Status:</strong> 
                                <?php if ($hasPermission): ?>
                                    <span class="status-badge status-allowed">Memiliki Akses</span>
                                <?php else: ?>
                                    <span class="status-badge status-denied">Tidak Memiliki Akses</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Route Access Test -->
            <div class="demo-section">
                <h2>🧪 Test Akses Route</h2>
                <p>Klik link di bawah untuk menguji akses ke berbagai route:</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 20px 0;">
                    <a href="/" class="btn">🏠 Beranda</a>
                    <a href="/login.php" class="btn">🔐 Login</a>
                    <a href="/admin/" class="btn">👨‍💼 Admin Dashboard</a>
                    <a href="/siswa/" class="btn">👨‍🎓 Siswa Dashboard</a>
                    <a href="/logout.php" class="btn">🚪 Logout</a>
                </div>
                
                <h3>Status Akses Route:</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                    <?php
                    $testRoutes = [
                        '/' => 'Beranda',
                        '/login.php' => 'Login',
                        '/admin/' => 'Admin Dashboard',
                        '/siswa/' => 'Siswa Dashboard',
                        '/logout.php' => 'Logout'
                    ];
                    
                    foreach ($testRoutes as $route => $name):
                        $canAccess = canAccessRoute($route);
                    ?>
                        <div style="border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                            <strong><?= htmlspecialchars($name) ?></strong><br>
                            <code><?= htmlspecialchars($route) ?></code><br>
                            <span class="status-badge <?= $canAccess ? 'status-allowed' : 'status-denied' ?>">
                                <?= $canAccess ? 'Dapat Diakses' : 'Tidak Dapat Diakses' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Middleware Functions Demo -->
            <div class="demo-section">
                <h2>⚙️ Demo Fungsi Middleware</h2>
                
                <h3>Generate CSRF Token:</h3>
                <p><code><?= htmlspecialchars(generateCSRFToken()) ?></code></p>
                
                <h3>Test Permission Check:</h3>
                <p>Has permission 'book_view': 
                    <?php if (hasPermission('book_view')): ?>
                        <span class="status-badge status-allowed">Ya</span>
                    <?php else: ?>
                        <span class="status-badge status-denied">Tidak</span>
                    <?php endif; ?>
                </p>
                
                <h3>Test Action Check:</h3>
                <p>Can perform 'view_students': 
                    <?php if (canPerformAction('view_students')): ?>
                        <span class="status-badge status-allowed">Ya</span>
                    <?php else: ?>
                        <span class="status-badge status-denied">Tidak</span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Flash Messages -->
            <?php displayFlashMessages(); ?>
        </main>

        <footer>
            <p>&copy; 2024 Sistem Informasi Sekolah. Middleware Demo.</p>
        </footer>
    </div>

    <script src="assets/js/script.js"></script>
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="assets/js/session.js"></script>
    <?php endif; ?>
</body>
</html> 