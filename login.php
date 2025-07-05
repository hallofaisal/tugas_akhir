<?php
session_start();

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') {
        header('Location: admin/');
    } else {
        header('Location: siswa/');
    }
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if(empty($username) || empty($password) || empty($role)) {
        $error = 'Semua field harus diisi!';
    } else {
        // Include database connection
        require_once 'includes/database.php';
        
        // Simple authentication (you should implement proper authentication)
        if($role == 'admin') {
            // Admin credentials (in real app, use database)
            if($username == 'admin' && $password == 'admin123') {
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'admin';
                header('Location: admin/');
                exit();
            } else {
                $error = 'Username atau password salah!';
            }
        } elseif($role == 'siswa') {
            // Student credentials (in real app, use database)
            if($username == 'siswa' && $password == 'siswa123') {
                $_SESSION['user_id'] = 2;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'siswa';
                header('Location: siswa/');
                exit();
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Role tidak valid!';
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
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Login Sistem Informasi Akademik</h2>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="">Pilih Role</option>
                        <option value="admin">Admin</option>
                        <option value="siswa">Siswa</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="login-info">
                <p><strong>Demo Credentials:</strong></p>
                <p><strong>Admin:</strong> username: admin, password: admin123</p>
                <p><strong>Siswa:</strong> username: siswa, password: siswa123</p>
            </div>

            <div class="back-link">
                <a href="index.php">← Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</body>
</html> 