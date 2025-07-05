<?php
// Include session handler
require_once 'includes/session_handler.php';

// Initialize secure session
init_secure_session();

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') {
        header('Location: admin/');
    } elseif($_SESSION['role'] == 'siswa') {
        header('Location: siswa/');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if(empty($username) || empty($password) || empty($role)) {
        $error = 'Semua field harus diisi!';
    } else {
        try {
            // Include database connection
            $pdo = require_once 'db.php';
            
            // Prepare statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT id, username, password, email, full_name, role FROM users WHERE username = ? AND role = ? AND is_active = 1");
            $stmt->execute([$username, $role]);
            $user = $stmt->fetch();
            
            if($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Redirect based on role
                if($user['role'] == 'admin') {
                    header('Location: admin/');
                } elseif($user['role'] == 'siswa') {
                    header('Location: siswa/');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Username, password, atau role salah!';
            }
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
        } catch (Exception $e) {
            error_log("General error: " . $e->getMessage());
            $error = 'Terjadi kesalahan. Silakan coba lagi nanti.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Akademik</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-box h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .login-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .login-info p {
            margin: 5px 0;
            color: #666;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>🔐 Login Sistem Akademik</h2>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['logout_message'])): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($_SESSION['logout_message']); ?>
                    <?php if(isset($_SESSION['logout_user'])): ?>
                        <br><small>Selamat tinggal, <?php echo htmlspecialchars($_SESSION['logout_user']); ?>!</small>
                    <?php endif; ?>
                </div>
                <?php 
                // Clear logout message after displaying
                unset($_SESSION['logout_message']);
                unset($_SESSION['logout_user']);
                unset($_SESSION['logout_role']);
                ?>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">👤 Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">🔒 Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role">👥 Role:</label>
                    <select id="role" name="role" required>
                        <option value="">Pilih Role</option>
                        <option value="admin" <?php echo ($_POST['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="siswa" <?php echo ($_POST['role'] ?? '') == 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                    </select>
                </div>

                <button type="submit" class="btn">🚀 Login</button>
            </form>

            <div class="login-info">
                <p><strong>📋 Demo Credentials:</strong></p>
                <p><strong>👨‍💼 Admin:</strong> admin / 123456</p>
                <p><strong>👨‍🎓 Siswa:</strong> siswa001 / 123456</p>
                <p><strong>👨‍🎓 Siswa:</strong> siswa002 / 123456</p>
                <p><strong>👨‍🎓 Siswa:</strong> siswa003 / 123456</p>
            </div>

            <div class="back-link">
                <a href="index.php">← Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</body>
</html> 