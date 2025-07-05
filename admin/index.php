<?php
/**
 * Admin Dashboard
 * File: admin/index.php
 * Description: Main admin dashboard with statistics and quick actions
 */

// Include authentication helper
require_once '../includes/auth.php';

// Require admin role
require_admin();

// Get database connection
$pdo = require_once '../db.php';

// Get statistics
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
    
    // Get recent activities
    $stmt = $pdo->prepare("
        SELECT 'borrowing' as type, b.borrow_date as date, u.full_name, bk.title as book_title
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        WHERE b.borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        ORDER BY b.borrow_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalStudents = 0;
    $totalBooks = 0;
    $activeBorrowings = 0;
    $overdueBooks = 0;
    $recentActivities = [];
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
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .dashboard-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .dashboard-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #666;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin: 0 0 15px 0;
        }
        
        .stat-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .stat-link:hover {
            text-decoration: underline;
        }
        
        .dashboard-actions {
            margin-bottom: 30px;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .action-card h4 {
            color: #667eea;
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .action-card p {
            color: #666;
            margin: 0;
        }
        
        .recent-activity {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .activity-list {
            margin-top: 20px;
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
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
        }
        
        .activity-text {
            color: #333;
            flex: 1;
            margin-right: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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
                    <li><a href="books.php">📚 Kelola Buku</a></li>
                    <li><a href="borrowings.php">📖 Peminjaman</a></li>
                    <li><a href="students.php">👨‍🎓 Kelola Siswa</a></li>
                    <li><a href="visitors.php">👥 Pengunjung</a></li>
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
                <a href="borrowings.php?status=overdue">Lihat detail</a>
            </div>
            <?php endif; ?>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>👨‍🎓 Total Siswa</h3>
                    <p class="stat-number"><?php echo $totalStudents; ?></p>
                    <a href="students.php" class="stat-link">Lihat Detail →</a>
                </div>
                <div class="stat-card">
                    <h3>📚 Total Buku</h3>
                    <p class="stat-number"><?php echo $totalBooks; ?></p>
                    <a href="books.php" class="stat-link">Lihat Detail →</a>
                </div>
                <div class="stat-card">
                    <h3>📖 Sedang Dipinjam</h3>
                    <p class="stat-number"><?php echo $activeBorrowings; ?></p>
                    <a href="borrowings.php?status=borrowed" class="stat-link">Lihat Detail →</a>
                </div>
                <div class="stat-card">
                    <h3>⚠️ Terlambat</h3>
                    <p class="stat-number"><?php echo $overdueBooks; ?></p>
                    <a href="borrowings.php?status=overdue" class="stat-link">Lihat Detail →</a>
                </div>
            </div>

            <div class="dashboard-actions">
                <h3>🚀 Menu Cepat</h3>
                <div class="action-grid">
                    <a href="books.php" class="action-card">
                        <h4>📚 Kelola Buku</h4>
                        <p>Tambah, edit, dan hapus data buku perpustakaan</p>
                    </a>
                    <a href="borrowings.php" class="action-card">
                        <h4>📖 Kelola Peminjaman</h4>
                        <p>Kelola peminjaman dan pengembalian buku</p>
                    </a>
                    <a href="students.php" class="action-card">
                        <h4>👨‍🎓 Kelola Siswa</h4>
                        <p>Tambah, edit, dan hapus data siswa</p>
                    </a>
                    <a href="visitors.php" class="action-card">
                        <h4>👥 Data Pengunjung</h4>
                        <p>Lihat data pengunjung perpustakaan</p>
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
</body>
</html> 