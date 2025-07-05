<?php
session_start();
/**
 * Admin Dashboard
 * File: admin/index.php
 * Description: Main admin dashboard with statistics and quick actions
 */

// Include middleware system
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
require_once '../includes/visitor_logger.php';

// Apply middleware protection - allow both admin and teacher
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header('Location: ../login.php');
    exit;
}

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
    $result = $stmt->fetch();
    $totalStudents = $result && isset($result['total']) ? $result['total'] : 0;
    
    // Count total books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $totalBooks = $result && isset($result['total']) ? $result['total'] : 0;
    
    // Count active borrowings
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrowings WHERE status = 'borrowed'");
    $stmt->execute();
    $result = $stmt->fetch();
    $activeBorrowings = $result && isset($result['total']) ? $result['total'] : 0;
    
    // Count overdue books
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrowings WHERE status = 'overdue'");
    $stmt->execute();
    $result = $stmt->fetch();
    $overdueBooks = $result && isset($result['total']) ? $result['total'] : 0;
    
    // Count today's visitors
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM visitors WHERE visit_date = CURRENT_DATE");
    $stmt->execute();
    $result = $stmt->fetch();
    $todayVisitors = $result && isset($result['total']) ? $result['total'] : 0;
    
    // Count unique visitors today
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) as total FROM visitors WHERE visit_date = CURRENT_DATE");
    $stmt->execute();
    $result = $stmt->fetch();
    $uniqueVisitorsToday = $result && isset($result['total']) ? $result['total'] : 0;
    
    // Count total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    $totalUsers = $result && isset($result['total']) ? $result['total'] : 0;
    
    // Count books by category
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM books WHERE is_active = 1 GROUP BY category ORDER BY count DESC LIMIT 5");
    $stmt->execute();
    $booksByCategory = $stmt ? $stmt->fetchAll() : [];
    
    // Get weekly visitor trend (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(visit_date) as date, COUNT(*) as count 
        FROM visitors 
        WHERE visit_date >= DATE('now', '-7 days')
        GROUP BY DATE(visit_date) 
        ORDER BY date
    ");
    $stmt->execute();
    $weeklyVisitors = $stmt ? $stmt->fetchAll() : [];
    
    // Get recent activities
    $stmt = $pdo->prepare("
        SELECT 'borrowing' as type, b.borrow_date as date, u.full_name, bk.title as book_title
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        WHERE b.borrow_date >= DATE('now', '-7 days')
        ORDER BY b.borrow_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivities = $stmt ? $stmt->fetchAll() : [];
    
    // Get top borrowed books
    $stmt = $pdo->prepare("
        SELECT bk.title, COUNT(*) as borrow_count
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.borrow_date >= DATE('now', '-30 days')
        GROUP BY bk.id, bk.title
        ORDER BY borrow_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topBorrowedBooks = $stmt ? $stmt->fetchAll() : [];
    
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

// Get current user data from session
$currentUser = [
    'id' => $_SESSION['user_id'] ?? 0,
    'username' => $_SESSION['username'] ?? '',
    'full_name' => $_SESSION['full_name'] ?? '',
    'role' => $_SESSION['role'] ?? '',
    'email' => $_SESSION['email'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?php echo $_SESSION['role'] === 'admin' ? 'Admin' : 'Guru'; ?> - Sistem Informasi Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body { 
            background: #f8fafc; 
            color: #334155;
            line-height: 1.6;
        }
        
        .page-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.875rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .page-subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin: 0.5rem 0 0 0;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
        }
        
        .welcome-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .stat-icon.primary { background: #eff6ff; color: #3b82f6; }
        .stat-icon.success { background: #f0fdf4; color: #16a34a; }
        .stat-icon.warning { background: #fffbeb; color: #d97706; }
        .stat-icon.danger { background: #fef2f2; color: #dc2626; }
        .stat-icon.info { background: #f0f9ff; color: #0ea5e9; }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .content-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .content-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
        }
        
        .content-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .content-body {
            padding: 1.5rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
            text-decoration: none;
            color: inherit;
        }
        
        .action-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem auto;
        }
        
        .action-icon.primary { background: #eff6ff; color: #3b82f6; }
        .action-icon.success { background: #f0fdf4; color: #16a34a; }
        .action-icon.warning { background: #fffbeb; color: #d97706; }
        .action-icon.info { background: #f0f9ff; color: #0ea5e9; }
        
        .action-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .action-desc {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }
        
        .activity-icon.borrowing { background: #eff6ff; color: #3b82f6; }
        .activity-icon.return { background: #f0fdf4; color: #16a34a; }
        .activity-icon.overdue { background: #fef2f2; color: #dc2626; }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .activity-meta {
            color: #64748b;
            font-size: 0.75rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .btn {
            font-weight: 500;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            border-color: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            border-color: #4b5563;
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
            padding: 0.75rem;
        }
        
        .table td {
            border-bottom: 1px solid #f1f5f9;
            padding: 0.75rem;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
        }
        
        .badge-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .badge-warning {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fed7aa;
        }
        
        .badge-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-0">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard <?php echo $_SESSION['role'] === 'admin' ? 'Admin' : 'Guru'; ?>
                    </h1>
                    <p class="page-subtitle">Selamat datang di Sistem Informasi Akademik</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="../visitor_log.php" class="btn btn-secondary">
                        <i class="bi bi-people me-1"></i>Log Pengunjung
                    </a>
                    <a href="../logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="welcome-title">Selamat datang, <?= htmlspecialchars($currentUser['full_name']) ?>!</h2>
                    <p class="welcome-subtitle">Kelola sistem akademik dan perpustakaan dengan mudah</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex flex-column align-items-end">
                        <small class="text-white-50">Terakhir login</small>
                        <span class="text-white"><?= date('d/m/Y H:i') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $totalStudents ?></h3>
                        <p class="stat-label">Total Siswa</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $totalBooks ?></h3>
                        <p class="stat-label">Total Buku</p>
                    </div>
                    <div class="stat-icon success">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $activeBorrowings ?></h3>
                        <p class="stat-label">Peminjaman Aktif</p>
                    </div>
                    <div class="stat-icon info">
                        <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $overdueBooks ?></h3>
                        <p class="stat-label">Buku Terlambat</p>
                    </div>
                    <div class="stat-icon danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $todayVisitors ?></h3>
                        <p class="stat-label">Pengunjung Hari Ini</p>
                    </div>
                    <div class="stat-icon warning">
                        <i class="bi bi-eye"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $totalUsers ?></h3>
                        <p class="stat-label">Total Pengguna</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-person-badge"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="manage_books.php" class="action-card">
                <div class="action-icon primary">
                    <i class="bi bi-book"></i>
                </div>
                <div class="action-title">Manajemen Buku</div>
                <div class="action-desc">Kelola koleksi buku perpustakaan</div>
            </a>
            
            <a href="borrowings.php" class="action-card">
                <div class="action-icon success">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div class="action-title">Peminjaman</div>
                <div class="action-desc">Kelola peminjaman dan pengembalian</div>
            </a>
            
            <a href="../register.php" class="action-card">
                <div class="action-icon warning">
                    <i class="bi bi-person-plus"></i>
                </div>
                <div class="action-title">Tambah Siswa</div>
                <div class="action-desc">Daftarkan siswa baru</div>
            </a>
            
            <a href="../visitor_log_form.php" class="action-card">
                <div class="action-icon info">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <div class="action-title">Log Pengunjung</div>
                <div class="action-desc">Catat kunjungan perpustakaan</div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Charts and Analytics -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-graph-up me-2"></i>Analisis Pengunjung
                    </h5>
                </div>
                <div class="content-body">
                    <div class="chart-container">
                        <canvas id="visitorChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru
                    </h5>
                </div>
                <div class="content-body">
                    <?php if (empty($recentActivities)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-6 d-block mb-2"></i>
                            Tidak ada aktivitas terbaru
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($recentActivities, 0, 5) as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon borrowing">
                                    <i class="bi bi-journal-check"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?= htmlspecialchars($activity['full_name']) ?></div>
                                    <div class="activity-meta">
                                        Meminjam "<?= htmlspecialchars($activity['book_title']) ?>" • 
                                        <?= date('d/m/Y H:i', strtotime($activity['date'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bottom Content Grid -->
        <div class="content-grid">
            <!-- Top Borrowed Books -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-star me-2"></i>Buku Terpopuler
                    </h5>
                </div>
                <div class="content-body">
                    <?php if (empty($topBorrowedBooks)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-book display-6 d-block mb-2"></i>
                            Tidak ada data peminjaman
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Judul Buku</th>
                                        <th>Jumlah Dipinjam</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topBorrowedBooks as $book): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($book['title']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-success"><?= $book['borrow_count'] ?>x</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">Populer</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Books by Category -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-tags me-2"></i>Buku per Kategori
                    </h5>
                </div>
                <div class="content-body">
                    <?php if (empty($booksByCategory)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-tag display-6 d-block mb-2"></i>
                            Tidak ada data kategori
                        </div>
                    <?php else: ?>
                        <?php foreach ($booksByCategory as $category): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <strong><?= htmlspecialchars($category['category'] ?: 'Tanpa Kategori') ?></strong>
                                </div>
                                <span class="badge badge-secondary"><?= $category['count'] ?> buku</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Visitor Chart
const ctx = document.getElementById('visitorChart').getContext('2d');
const visitorData = <?= json_encode($weeklyVisitors) ?>;

const labels = visitorData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
});

const data = visitorData.map(item => item.count);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Pengunjung',
            data: data,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
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
                    color: '#e2e8f0'
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

// Auto refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>
</body>
</html> 
