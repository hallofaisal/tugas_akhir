<?php
session_start();
require_once '../includes/database.php';
require_siswa();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Sistem Informasi Akademik</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <h1>Dashboard Siswa</h1>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="nilai.php">Nilai Saya</a></li>
                    <li><a href="jadwal.php">Jadwal</a></li>
                    <li><a href="profil.php">Profil</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-header">
                <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p>Dashboard siswa sistem informasi akademik</p>
            </div>

            <div class="student-info">
                <div class="info-card">
                    <h3>Informasi Siswa</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>NIS:</label>
                            <span>2024001</span>
                        </div>
                        <div class="info-item">
                            <label>Nama:</label>
                            <span>Budi Santoso</span>
                        </div>
                        <div class="info-item">
                            <label>Kelas:</label>
                            <span>X-A</span>
                        </div>
                        <div class="info-item">
                            <label>Semester:</label>
                            <span>Ganjil 2024/2025</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="academic-summary">
                <h3>Ringkasan Akademik</h3>
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>Rata-rata Nilai</h4>
                        <p class="summary-number">85.5</p>
                        <span class="summary-label">Sangat Baik</span>
                    </div>
                    <div class="summary-card">
                        <h4>Mata Pelajaran</h4>
                        <p class="summary-number">8</p>
                        <span class="summary-label">Aktif</span>
                    </div>
                    <div class="summary-card">
                        <h4>Kehadiran</h4>
                        <p class="summary-number">95%</p>
                        <span class="summary-label">Sangat Baik</span>
                    </div>
                    <div class="summary-card">
                        <h4>Ranking</h4>
                        <p class="summary-number">5</p>
                        <span class="summary-label">dari 35 siswa</span>
                    </div>
                </div>
            </div>

            <div class="recent-grades">
                <h3>Nilai Terbaru</h3>
                <div class="grades-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Mata Pelajaran</th>
                                <th>Nilai Tugas</th>
                                <th>Nilai UTS</th>
                                <th>Nilai UAS</th>
                                <th>Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Matematika</td>
                                <td>88</td>
                                <td>85</td>
                                <td>90</td>
                                <td>87.7</td>
                            </tr>
                            <tr>
                                <td>Bahasa Indonesia</td>
                                <td>92</td>
                                <td>88</td>
                                <td>85</td>
                                <td>88.3</td>
                            </tr>
                            <tr>
                                <td>Bahasa Inggris</td>
                                <td>85</td>
                                <td>90</td>
                                <td>88</td>
                                <td>87.7</td>
                            </tr>
                            <tr>
                                <td>IPA</td>
                                <td>90</td>
                                <td>87</td>
                                <td>92</td>
                                <td>89.7</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="upcoming-events">
                <h3>Jadwal Terdekat</h3>
                <div class="events-list">
                    <div class="event-item">
                        <div class="event-date">
                            <span class="day">15</span>
                            <span class="month">Des</span>
                        </div>
                        <div class="event-details">
                            <h4>Ujian Semester</h4>
                            <p>Matematika - 08:00 WIB</p>
                        </div>
                    </div>
                    <div class="event-item">
                        <div class="event-date">
                            <span class="day">18</span>
                            <span class="month">Des</span>
                        </div>
                        <div class="event-details">
                            <h4>Pengumuman Nilai</h4>
                            <p>Semester Ganjil 2024/2025</p>
                        </div>
                    </div>
                    <div class="event-item">
                        <div class="event-date">
                            <span class="day">20</span>
                            <span class="month">Des</span>
                        </div>
                        <div class="event-details">
                            <h4>Libur Semester</h4>
                            <p>Libur akhir semester</p>
                        </div>
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