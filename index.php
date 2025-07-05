<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Akademik</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>Sistem Informasi Akademik</h1>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="middleware_demo.php">🔒 Middleware Demo</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                            <li><a href="admin/">Dashboard Admin</a></li>
                        <?php elseif($_SESSION['role'] == 'siswa'): ?>
                            <li><a href="siswa/">Dashboard Siswa</a></li>
                        <?php endif; ?>
                        <li><a href="logout_confirm.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <section class="hero">
                <h2>Selamat Datang di Sistem Informasi Akademik</h2>
                <p>Sistem informasi untuk mengelola data akademik siswa dan administrasi sekolah.</p>
                
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <div class="cta-buttons">
                        <a href="login.php" class="btn btn-primary">Login</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="features">
                <h3>Fitur Utama</h3>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4>Manajemen Siswa</h4>
                        <p>Kelola data siswa, nilai, dan informasi akademik</p>
                    </div>
                    <div class="feature-card">
                        <h4>Dashboard Admin</h4>
                        <p>Akses penuh untuk administrasi sistem</p>
                    </div>
                    <div class="feature-card">
                        <h4>Dashboard Siswa</h4>
                        <p>Lihat nilai dan informasi akademik pribadi</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Sistem Informasi Akademik. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html> 