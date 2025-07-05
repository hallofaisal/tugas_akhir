<?php
/**
 * Login Page with Bootstrap Responsive Design
 * File: login.php
 * Description: Modern responsive login page using Bootstrap
 */

session_start();

// Include database connection
require_once 'db.php';

// Include middleware for CSRF protection
require_once 'includes/middleware.php';
require_once 'includes/middleware_config.php';

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        // Validate input
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi.';
        } else {
            try {
                $pdo = getConnection();
                
                // Get user from database
                $stmt = $pdo->prepare("SELECT id, username, password, role, nama_lengkap FROM users WHERE username = ? AND status = 'active'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Set remember me cookie if requested
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                        
                        // Store token in database (you might want to add a remember_tokens table)
                        // For now, we'll just set the session
                    }
                    
                    // Log successful login
                    logUserActivity('login_success', [
                        'username' => $username,
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ]);
                    
                    // Redirect based on role
                    $redirect_url = $_SESSION['intended_url'] ?? 'index.php';
                    unset($_SESSION['intended_url']);
                    
                    header("Location: $redirect_url");
                    exit();
                } else {
                    $error = 'Username atau password salah.';
                    
                    // Log failed login attempt
                    logUserActivity('login_failed', [
                        'username' => $username,
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ]);
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
            }
        }
    }
}

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
    <title>Login - Sistem Informasi Sekolah</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 450px;
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
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1rem;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1edff, #bee5eb);
            color: #0c5460;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }
        
        .login-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .demo-accounts {
            background: rgba(13, 110, 253, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .demo-accounts h6 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .demo-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(13, 110, 253, 0.1);
        }
        
        .demo-account:last-child {
            border-bottom: none;
        }
        
        .demo-account .role {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }
        
        .demo-account .credentials {
            font-family: monospace;
            font-size: 0.85rem;
        }
        
        .copy-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #0056b3;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 576px) {
            .login-container {
                padding: 10px;
            }
            
            .login-card {
                border-radius: 15px;
            }
            
            .login-header {
                padding: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
            
            .login-body {
                padding: 1.5rem;
            }
            
            .demo-accounts {
                margin-top: 0.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-header h1 {
                font-size: 1.3rem;
            }
            
            .login-body {
                padding: 1rem;
            }
            
            .demo-account {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
        }
        
        /* Loading Animation */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <h1><i class="bi bi-shield-lock"></i> Login</h1>
                <p>Sistem Informasi Sekolah</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <!-- Flash Messages -->
                <?php if ($flash_message): ?>
                    <div class="alert alert-<?= $flash_type === 'error' ? 'danger' : $flash_type ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $flash_type === 'error' ? 'exclamation-triangle' : ($flash_type === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                        <?= htmlspecialchars($flash_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Error Messages -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <!-- Username Field -->
                    <div class="form-floating">
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required>
                        <label for="username">
                            <i class="bi bi-person"></i> Username
                        </label>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-floating position-relative">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Password"
                               required>
                        <label for="password">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                    
                    <!-- Remember Me -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            <i class="bi bi-clock"></i> Ingat saya
                        </label>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
                
                <!-- Demo Accounts -->
                <div class="demo-accounts">
                    <h6><i class="bi bi-info-circle"></i> Akun Demo</h6>
                    <div class="demo-account">
                        <div>
                            <strong>Admin</strong>
                            <div class="role">Administrator</div>
                        </div>
                        <div class="credentials">
                            admin / admin123
                            <button class="copy-btn" onclick="copyCredentials('admin', 'admin123')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                    <div class="demo-account">
                        <div>
                            <strong>Siswa</strong>
                            <div class="role">Student</div>
                        </div>
                        <div class="credentials">
                            siswa / siswa123
                            <button class="copy-btn" onclick="copyCredentials('siswa', 'siswa123')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>
                    <i class="bi bi-shield-check"></i> 
                    Sistem aman dengan enkripsi SSL
                </p>
                <p>
                    <a href="index.php">
                        <i class="bi bi-house"></i> Kembali ke Beranda
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'bi bi-eye';
            }
        }
        
        // Copy credentials to form
        function copyCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Show feedback
            const btn = event.target.closest('.copy-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i>';
            btn.style.background = '#198754';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '';
            }, 1000);
        }
        
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const originalText = btn.innerHTML;
            
            btn.classList.add('btn-loading');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
            btn.disabled = true;
            
            // Re-enable after 5 seconds if no response
            setTimeout(() => {
                btn.classList.remove('btn-loading');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 5000);
        });
        
        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
            
            // Tab navigation enhancement
            if (e.key === 'Tab') {
                const activeElement = document.activeElement;
                if (activeElement && activeElement.classList.contains('form-control')) {
                    activeElement.parentElement.classList.add('focused');
                }
            }
        });
        
        // Form validation enhancement
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
            
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.classList.remove('is-invalid');
                }
            });
        });
    </script>
</body>
</html> 