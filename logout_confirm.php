<?php
/**
 * Logout Confirmation Page
 * File: logout_confirm.php
 * Description: Confirms logout action before proceeding
 */

// Include session handler
require_once 'includes/session_handler.php';

// Initialize secure session
init_secure_session();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? '';

// Handle logout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
        // User confirmed logout
        header('Location: logout.php');
        exit();
    } else {
        // User cancelled, redirect back to appropriate dashboard
        if ($userRole === 'admin') {
            header('Location: admin/');
        } elseif ($userRole === 'siswa') {
            header('Location: siswa/');
        } else {
            header('Location: index.php');
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Logout - Sistem Informasi Akademik</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .confirm-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .confirm-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .confirm-box h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .confirm-box p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .user-info h3 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .user-info p {
            color: #666;
            margin: 5px 0;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .warning-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="confirm-container">
        <div class="confirm-box">
            <div class="warning-icon">⚠️</div>
            <h2>Konfirmasi Logout</h2>
            <p>Anda yakin ingin keluar dari sistem?</p>
            
            <div class="user-info">
                <h3>Informasi Pengguna</h3>
                <p><strong>Nama:</strong> <?php echo htmlspecialchars($userName); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($userRole)); ?></p>
                <p><strong>Login sejak:</strong> <?php echo isset($_SESSION['login_time']) ? date('d/m/Y H:i', $_SESSION['login_time']) : 'N/A'; ?></p>
            </div>
            
            <form method="POST" action="logout_confirm.php">
                <div class="button-group">
                    <button type="submit" name="confirm_logout" value="yes" class="btn btn-danger">
                        🚪 Ya, Logout
                    </button>
                    <button type="submit" name="confirm_logout" value="no" class="btn btn-secondary">
                        ❌ Batal
                    </button>
                </div>
            </form>
            
            <div style="margin-top: 30px;">
                <a href="index.php" style="color: #667eea; text-decoration: none;">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html> 