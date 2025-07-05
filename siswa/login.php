<?php
/**
 * Student Login Page
 * File: siswa/login.php
 * Description: Login khusus siswa
 */

session_start();

// Redirect if already logged in as student
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    header('Location: index.php');
    exit;
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
            $pdo = require_once '../db.php';
            
            if (!$pdo) {
                throw new Exception('Database connection failed');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && $user['role'] === 'student' && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            error_log("Student login error: " . $e->getMessage());
        }
    }
}

// Include middleware for CSRF protection
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';

// Generate CSRF token
$csrf_token = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Siswa - Sistem Informasi Akademik</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
        .btn-success {
            background: #10b981;
            border-color: #10b981;
        }
        .btn-success:hover {
            background: #059669;
            border-color: #059669;
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
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Login Siswa</h1>
            <p>Masuk ke panel siswa</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                    <label for="username">Username</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                <button type="submit" class="btn btn-success w-100 mb-2">Masuk</button>
                <a href="../login.php" class="btn btn-outline-secondary w-100">Kembali ke Login Utama</a>
            </form>
            <div class="mt-3 text-center" style="font-size:0.95rem;color:#64748b;">
                <b>Demo Akun Siswa:</b><br>
                Username: <code>siswa</code><br>
                Password: <code>siswa123</code>
            </div>
        </div>
    </div>
</body>
</html> 