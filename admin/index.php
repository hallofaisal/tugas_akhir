<?php
/**
 * Admin Dashboard
 * File: admin/index.php
 * Description: Main admin dashboard with statistics and quick actions
 */

// Include middleware system
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
require_once '../includes/visitor_logger.php';

// Apply middleware protection
requireAdmin();

// Get database connection
$pdo = require_once '../db.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('admin/index.php');

// Get comprehensive statistics
try {
    // Count total students
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
    $stmt->execute();
    $totalStudents = $stmt->fetch()['total'];
    
    // Count total books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE is_active = 1");
    $stmt->execute();
    $totalBooks = $stmt->fetch()['total'];
    
    // Count active borrowings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrowings WHERE status = 'borrowed'");
    $stmt->execute();
    $activeBorrowings = $stmt->fetch()['total'];
    
    // Count overdue books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrowings WHERE status = 'overdue'");
    $stmt->execute();
    $overdueBooks = $stmt->fetch()['total'];
    
    // Count today's visitors
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM visitors WHERE visit_date = CURDATE()");
    $stmt->execute();
    $todayVisitors = $stmt->fetch()['total'];
    
    // Count unique visitors today
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) as total FROM visitors WHERE visit_date = CURDATE()");
    $stmt->execute();
    $uniqueVisitorsToday = $stmt->fetch()['total'];
    
    // Count total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
    $stmt->execute();
    $totalUsers = $stmt->fetch()['total'];
    
    // Count books by category
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM books WHERE is_active = 1 GROUP BY category ORDER BY count DESC LIMIT 5");
    $stmt->execute();
    $booksByCategory = $stmt->fetchAll();
    
    // Get weekly visitor trend (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(visit_date) as date, COUNT(*) as count 
        FROM visitors 
        WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(visit_date) 
        ORDER BY date
    ");
    $stmt->execute();
    $weeklyVisitors = $stmt->fetchAll();
    
    // Get recent activities
    $stmt = $pdo->prepare("
        SELECT 'borrowing' as type, b.borrow_date as date, u.full_name, bk.title as book_title
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        WHERE b.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        ORDER BY b.borrow_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll();
    
    // Get top borrowed books
    $stmt = $pdo->prepare("
        SELECT bk.title, COUNT(*) as borrow_count
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        GROUP BY bk.id, bk.title
        ORDER BY borrow_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topBorrowedBooks = $stmt->fetchAll();
    
    } catch (Exception $e) {
        error_log("Dashboard error: " . $e->getMessage());
        $totalStudents = 0;
        $totalBooks = 0;
        $activeBorrowings = 0;
        $overdueBooks = 0;
        $todayVisitors = 0;
        $uniqueVisitorsToday = 0;
        $totalUsers = 0;
        $booksByCategory = [];
        $weeklyVisitors = [];
        $recentActivities = [];
        $topBorrowedBooks = [];
    }

// Get current user data
$currentUser = get_current_user_data();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Informasi Akademik</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .dashboard-header h2 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .dashboard-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 18px;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            color: #666;
            margin: 0 0 20px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            color: #667eea;
            margin: 0 0 15px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .stat-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border: 2px solid #667eea;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .stat-link:hover {
            background: #667eea;
            color: white;
            text-decoration: none;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .chart-section h3 {
            color: #333;
            margin: 0 0 25px 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .dashboard-actions {
            margin-bottom: 40px;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .action-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .action-card h4 {
            color: #667eea;
            margin: 0 0 15px 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .action-card p {
            color: #666;
            margin: 0 0 15px 0;
            line-height: 1.6;
        }
        
        .action-card .badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .recent-activity {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .activity-list {
            margin-top: 25px;
        }
        
        .activity-item {
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            color: #999;
            font-size: 14px;
            background: #f8f9fa;
            padding: 5px 12px;
            border-radius: 20px;
        }
        
        .activity-text {
            color: #333;
            flex: 1;
            margin-right: 20px;
            line-height: 1.5;
        }
        
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #17a2b8;
        }
        
        .top-books {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .book-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .book-item:last-child {
            border-bottom: none;
        }
        
        .book-title {
            font-weight: 600;
            color: #333;
        }
        
        .book-count {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .category-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .category-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .category-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .category-count {
            color: #667eea;
            font-size: 18px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stats {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="logged-in" data-user-id="<?php echo $currentUser['id']; ?>" data-user-role="<?php echo $currentUser['role']; ?>">
    <header>
        <nav>
            <div class="container">
                <h1>👨‍💼 Dashboard Admin</h1>
                <ul>
                    <li><a href="index.php">📊 Dashboard</a></li>
                    <li><a href="manage_books.php">📚 Kelola Buku</a></li>
                    <li><a href="borrowings.php">📖 Peminjaman</a></li>
                    <li><a href="visitor_stats.php">👥 Pengunjung</a></li>
                    <li><a href="../logout_confirm.php">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-header">
                <h2>🎉 Selamat Datang, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h2>
                <p>Panel administrasi sistem informasi akademik dan perpustakaan</p>
            </div>

            <?php if($overdueBooks > 0): ?>
            <div class="alert alert-warning">
                ⚠️ Ada <?php echo $overdueBooks; ?> buku yang terlambat dikembalikan. 
                <a href="borrowings.php?status=overdue" style="color: #856404; font-weight: 600;">Lihat detail</a>
            </div>
            <?php endif; ?>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>👨‍🎓 Total Siswa</h3>
                    <p class="stat-number"><?php echo number_format($totalStudents); ?></p>
                    <p class="stat-subtitle">Siswa aktif terdaftar</p>
                    <a href="students.php" class="stat-link">Lihat Detail</a>
                </div>
                <div class="stat-card">
                    <h3>📚 Total Buku</h3>
                    <p class="stat-number"><?php echo number_format($totalBooks); ?></p>
                    <p class="stat-subtitle">Buku tersedia di perpustakaan</p>
                    <a href="manage_books.php" class="stat-link">Lihat Detail</a>
                </div>
                <div class="stat-card">
                    <h3>📖 Sedang Dipinjam</h3>
                    <p class="stat-number"><?php echo number_format($activeBorrowings); ?></p>
                    <p class="stat-subtitle">Buku sedang dipinjam</p>
                    <a href="borrowings.php?status=borrowed" class="stat-link">Lihat Detail</a>
                </div>
                <div class="stat-card">
                    <h3>⚠️ Terlambat</h3>
                    <p class="stat-number"><?php echo number_format($overdueBooks); ?></p>
                    <p class="stat-subtitle">Buku terlambat dikembalikan</p>
                    <a href="borrowings.php?status=overdue" class="stat-link">Lihat Detail</a>
                </div>
                <div class="stat-card">
                    <h3>👥 Pengunjung Hari Ini</h3>
                    <p class="stat-number"><?php echo number_format($todayVisitors); ?></p>
                    <p class="stat-subtitle"><?php echo $uniqueVisitorsToday; ?> pengunjung unik</p>
                    <a href="visitor_stats.php" class="stat-link">Lihat Detail</a>
                </div>
                <div class="stat-card">
                    <h3>👤 Total Pengguna</h3>
                    <p class="stat-number"><?php echo number_format($totalUsers); ?></p>
                    <p class="stat-subtitle">Pengguna terdaftar</p>
                    <a href="students.php" class="stat-link">Lihat Detail</a>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="chart-section">
                    <h3>📈 Tren Pengunjung (7 Hari Terakhir)</h3>
                    <div class="chart-container">
                        <canvas id="visitorChart"></canvas>
                    </div>
                </div>
                
                <div class="top-books">
                    <h3>🏆 Buku Terpopuler</h3>
                    <div class="activity-list">
                        <?php if(empty($topBorrowedBooks)): ?>
                            <div class="activity-item">
                                <span class="activity-text">Belum ada data peminjaman</span>
                            </div>
                        <?php else: ?>
                            <?php foreach($topBorrowedBooks as $book): ?>
                            <div class="book-item">
                                <span class="book-title"><?php echo htmlspecialchars($book['title']); ?></span>
                                <span class="book-count"><?php echo $book['borrow_count']; ?>x</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if(!empty($booksByCategory)): ?>
            <div class="chart-section">
                <h3>📊 Distribusi Buku per Kategori</h3>
                <div class="category-stats">
                    <?php foreach($booksByCategory as $category): ?>
                    <div class="category-item">
                        <div class="category-name"><?php echo htmlspecialchars($category['category']); ?></div>
                        <div class="category-count"><?php echo $category['count']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="dashboard-actions">
                <h3>🚀 Menu Cepat</h3>
                <div class="action-grid">
                    <a href="manage_books.php" class="action-card">
                        <h4>📚 Kelola Buku</h4>
                        <p>Tambah, edit, dan hapus data buku perpustakaan dengan kategori dan deskripsi lengkap</p>
                        <span class="badge"><?php echo $totalBooks; ?> buku</span>
                    </a>
                    <a href="borrowings.php" class="action-card">
                        <h4>📖 Kelola Peminjaman</h4>
                        <p>Kelola peminjaman dan pengembalian buku dengan sistem tracking lengkap</p>
                        <span class="badge"><?php echo $activeBorrowings; ?> aktif</span>
                    </a>
                    <a href="visitor_stats.php" class="action-card">
                        <h4>📊 Statistik Pengunjung</h4>
                        <p>Lihat statistik dan grafik pengunjung dengan filter waktu yang fleksibel</p>
                        <span class="badge"><?php echo $todayVisitors; ?> hari ini</span>
                    </a>
                    <a href="visitor_report.php" class="action-card">
                        <h4>📋 Laporan Pengunjung</h4>
                        <p>Laporan detail pengunjung dengan filter dan export data</p>
                        <span class="badge">Export CSV</span>
                    </a>
                </div>
            </div>

            <div class="recent-activity">
                <h3>📈 Aktivitas Terbaru</h3>
                <div class="activity-list">
                    <?php if(empty($recentActivities)): ?>
                        <div class="activity-item">
                            <span class="activity-text">Belum ada aktivitas terbaru</span>
                        </div>
                    <?php else: ?>
                        <?php foreach($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <span class="activity-text">
                                <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong> 
                                meminjam buku <strong><?php echo htmlspecialchars($activity['book_title']); ?></strong>
                            </span>
                            <span class="activity-time">
                                <?php echo date('d/m/Y', strtotime($activity['date'])); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
    <script src="../assets/js/session.js"></script>
    <script>
        // Visitor Chart
        const visitorCtx = document.getElementById('visitorChart').getContext('2d');
        const visitorData = <?php echo json_encode($weeklyVisitors); ?>;
        
        const labels = visitorData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
        });
        
        const data = visitorData.map(item => item.count);
        
        new Chart(visitorCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pengunjung',
                    data: data,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 