<?php
/**
 * Login Page with Bootstrap Responsive Design
 * File: login.php
 * Description: Modern responsive login page using Bootstrap
 */

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin/');
        exit;
    } elseif ($role === 'student') {
        header('Location: siswa/');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            $pdo = require_once 'db.php';
            
            if (!$pdo) {
                throw new Exception('Database connection failed');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                // Redirect based on role
                if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
                    header('Location: admin/');
                    exit;
                } elseif ($user['role'] === 'student') {
                    header('Location: siswa/');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Include middleware for CSRF protection
require_once 'includes/middleware.php';
require_once 'includes/middleware_config.php';

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Display flash messages
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .form-floating .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-floating label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .btn {
            font-weight: 500;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }
        
        .btn-outline-secondary {
            border-color: #d1d5db;
            color: #6b7280;
        }
        
        .btn-outline-secondary:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            color: #374151;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #16a34a;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert-info {
            background: #f0f9ff;
            color: #0c4a6e;
            border-left: 4px solid #0ea5e9;
        }
        
        .demo-accounts {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.5rem;
        }
        
        .demo-accounts h6 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .demo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .demo-item:last-child {
            border-bottom: none;
        }
        
        .demo-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .demo-value {
            font-size: 0.875rem;
            color: #374151;
            font-weight: 500;
        }
        
        .login-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            text-align: center;
        }
        
        .login-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #374151;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
            }
            
            .login-header,
            .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>
                <i class="bi bi-mortarboard me-2"></i>Sistem Informasi Akademik
            </h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($flash_message)): ?>
                <div class="alert alert-<?= $flash_type ?> alert-dismissible fade show">
                    <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($flash_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required autocomplete="username">
                    <label for="username">
                        <i class="bi bi-person me-1"></i>Username
                    </label>
                </div>
                
                <div class="form-floating position-relative">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required autocomplete="current-password">
                    <label for="password">
                        <i class="bi bi-lock me-1"></i>Password
                    </label>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                </div>
            </form>
            
            <div class="demo-accounts">
                <h6>
                    <i class="bi bi-info-circle me-1"></i>Akun Demo
                </h6>
                <div class="demo-item">
                    <span class="demo-label">Admin:</span>
                    <span class="demo-value">admin / admin123</span>
                </div>
                <div class="demo-item">
                    <span class="demo-label">Siswa:</span>
                    <span class="demo-value">siswa / siswa123</span>
                </div>
            </div>
        </div>
        
        <div class="login-footer">
            <a href="register.php">
                <i class="bi bi-person-plus me-1"></i>Daftar Akun Baru
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('passwordToggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'bi bi-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'bi bi-eye';
        }
    }

    // Auto focus on username field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('username').focus();
    });

    // Handle form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            e.preventDefault();
            alert('Username dan password harus diisi!');
            return false;
        }
    });
    </script>
</body>
</html> 
