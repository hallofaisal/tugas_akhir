<?php
session_start();
require_once '../includes/database.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Informasi Akademik</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>Dashboard Admin</h1>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="siswa.php">Kelola Siswa</a></li>
                    <li><a href="nilai.php">Kelola Nilai</a></li>
                    <li><a href="laporan.php">Laporan</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-header">
                <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p>Panel administrasi sistem informasi akademik</p>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Siswa</h3>
                    <p class="stat-number">150</p>
                    <a href="siswa.php" class="stat-link">Lihat Detail →</a>
                </div>
                <div class="stat-card">
                    <h3>Kelas Aktif</h3>
                    <p class="stat-number">12</p>
                    <a href="kelas.php" class="stat-link">Lihat Detail →</a>
                </div>
                <div class="stat-card">
                    <h3>Mata Pelajaran</h3>
                    <p class="stat-number">8</p>
                    <a href="mapel.php" class="stat-link">Lihat Detail →</a>
                </div>
                <div class="stat-card">
                    <h3>Guru</h3>
                    <p class="stat-number">25</p>
                    <a href="guru.php" class="stat-link">Lihat Detail →</a>
                </div>
            </div>

            <div class="dashboard-actions">
                <h3>Menu Cepat</h3>
                <div class="action-grid">
                    <a href="siswa.php" class="action-card">
                        <h4>Kelola Data Siswa</h4>
                        <p>Tambah, edit, dan hapus data siswa</p>
                    </a>
                    <a href="nilai.php" class="action-card">
                        <h4>Input Nilai</h4>
                        <p>Masukkan dan kelola nilai siswa</p>
                    </a>
                    <a href="laporan.php" class="action-card">
                        <h4>Buat Laporan</h4>
                        <p>Generate laporan akademik</p>
                    </a>
                    <a href="pengaturan.php" class="action-card">
                        <h4>Pengaturan</h4>
                        <p>Konfigurasi sistem</p>
                    </a>
                </div>
            </div>

            <div class="recent-activity">
                <h3>Aktivitas Terbaru</h3>
                <div class="activity-list">
                    <div class="activity-item">
                        <span class="activity-time">2 jam yang lalu</span>
                        <span class="activity-text">Nilai matematika kelas X-A telah diupdate</span>
                    </div>
                    <div class="activity-item">
                        <span class="activity-time">4 jam yang lalu</span>
                        <span class="activity-text">Siswa baru telah ditambahkan: Ahmad Fadillah</span>
                    </div>
                    <div class="activity-item">
                        <span class="activity-time">1 hari yang lalu</span>
                        <span class="activity-text">Laporan semester ganjil telah dibuat</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Sistem Informasi Akademik. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
</body>
</html> 