<?php
session_start();
/**
 * Student Dashboard (Admin-style)
 * File: siswa/index.php
 * Description: Student dashboard with design and layout matching the admin dashboard
 */

// Include middleware system
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';

// Apply middleware protection
requireSiswa();

// Get current user data from session
$currentUser = get_current_user_data();

// Sample statistics for student
$activeBorrowings = 2;
$totalBooks = 12;
$overdueBooks = 0;
$totalBorrowed = 5;
$totalRead = 8;
$favoriteCategory = 'Pelajaran';
$readingStreak = 3;

// Sample recent borrowings
$recentBorrowings = [
    [
        'book_title' => 'Matematika Dasar',
        'author' => 'Prof. Dr. Ahmad',
        'category' => 'Pelajaran',
        'borrow_date' => '2024-01-15',
        'status' => 'returned'
    ],
    [
        'book_title' => 'Sejarah Indonesia',
        'author' => 'Dr. Budi Santoso',
        'category' => 'Pelajaran',
        'borrow_date' => '2024-01-20',
        'status' => 'borrowed'
    ],
    [
        'book_title' => 'Fisika Modern',
        'author' => 'Dr. Sarah Wilson',
        'category' => 'Pelajaran',
        'borrow_date' => '2024-01-25',
        'status' => 'borrowed'
    ]
];

// Sample available books
$availableBooks = [
    [
        'title' => 'Fisika Modern',
        'author' => 'Dr. Sarah Wilson',
        'category' => 'Pelajaran',
        'available_copies' => 3
    ],
    [
        'title' => 'Kimia Dasar',
        'author' => 'Prof. Michael Brown',
        'category' => 'Pelajaran',
        'available_copies' => 2
    ],
    [
        'title' => 'Biologi Sel',
        'author' => 'Dr. Lisa Chen',
        'category' => 'Pelajaran',
        'available_copies' => 4
    ],
    [
        'title' => 'Ekonomi Mikro',
        'author' => 'Prof. John Smith',
        'category' => 'Pelajaran',
        'available_copies' => 1
    ]
];

// Student data from session or sample
$fullName = $currentUser['full_name'] ?? 'Siswa Demo';
$username = $currentUser['username'] ?? 'siswa';
$email = $currentUser['email'] ?? 'siswa@demo.com';
$nis = '2024001';
$kelas = 'X-A';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Sistem Informasi Perpustakaan</title>
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
        
        .activity-icon.borrowed { background: #eff6ff; color: #3b82f6; }
        .activity-icon.returned { background: #f0fdf4; color: #16a34a; }
        .activity-icon.overdue { background: #fef2f2; color: #dc2626; }
        .activity-icon.returned { background: #f0fdf4; color: #16a34a; }
        
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
        
        .btn-success {
            background: #10b981;
            border-color: #10b981;
        }
        
        .btn-success:hover {
            background: #059669;
            border-color: #059669;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge.borrowed { background: #eff6ff; color: #3b82f6; }
        .status-badge.returned { background: #f0fdf4; color: #16a34a; }
        .status-badge.overdue { background: #fef2f2; color: #dc2626; }
        
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
        
        .rating-stars {
            color: #fbbf24;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard Siswa
                    </h1>
                    <p class="page-subtitle">Selamat datang di Sistem Informasi Perpustakaan</p>
                </div>
                <div class="d-flex gap-2">
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
                    <h2 class="welcome-title">Selamat datang, <?= htmlspecialchars($fullName) ?>!</h2>
                    <p class="welcome-subtitle">Kelola sistem perpustakaan dengan mudah</p>
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
                        <h3 class="stat-number"><?= $activeBorrowings ?></h3>
                        <p class="stat-label">Peminjaman Aktif</p>
                    </div>
                    <div class="stat-icon success">
                        <i class="bi bi-book"></i>
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
                        <h3 class="stat-number"><?= $totalBorrowed ?></h3>
                        <p class="stat-label">Total Dipinjam</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $totalRead ?></h3>
                        <p class="stat-label">Buku Dibaca</p>
                    </div>
                    <div class="stat-icon info">
                        <i class="bi bi-eye"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $readingStreak ?></h3>
                        <p class="stat-label">Hari Membaca</p>
                    </div>
                    <div class="stat-icon warning">
                        <i class="bi bi-fire"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $favoriteCategory ?></h3>
                        <p class="stat-label">Kategori Favorit</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-heart"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="borrowings.php" class="action-card">
                <div class="action-icon primary">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div class="action-title">Peminjaman</div>
                <div class="action-desc">Kelola peminjaman dan pengembalian</div>
            </a>
            
            <a href="profile.php" class="action-card">
                <div class="action-icon success">
                    <i class="bi bi-person"></i>
                </div>
                <div class="action-title">Profil</div>
                <div class="action-desc">Lihat dan edit profil</div>
            </a>
            
            <a href="catalog.php" class="action-card">
                <div class="action-icon info">
                    <i class="bi bi-search"></i>
                </div>
                <div class="action-title">Katalog</div>
                <div class="action-desc">Jelajahi koleksi buku</div>
            </a>
            
            <a href="../logout.php" class="action-card">
                <div class="action-icon warning">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <div class="action-title">Logout</div>
                <div class="action-desc">Keluar dari sistem</div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Activities -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru
                    </h5>
                </div>
                <div class="content-body">
                    <?php foreach ($recentBorrowings as $borrowing): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $borrowing['status']; ?>">
                                <i class="bi bi-<?php echo $borrowing['status'] === 'returned' ? 'check-circle' : 'book'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($borrowing['book_title']); ?></div>
                                <div class="activity-meta">
                                    <?php echo htmlspecialchars($borrowing['author']); ?> • 
                                    <?php echo htmlspecialchars($borrowing['category']); ?> • 
                                    <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?>
                                    <span class="status-badge <?php echo $borrowing['status']; ?> ms-2">
                                        <?php echo ucfirst($borrowing['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Student Info -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-person-badge me-2"></i>Informasi Siswa
                    </h5>
                </div>
                <div class="content-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">NIS</label>
                        <div class="fw-semibold"><?php echo htmlspecialchars($nis); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Kelas</label>
                        <div class="fw-semibold"><?php echo htmlspecialchars($kelas); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Email</label>
                        <div class="fw-semibold"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Status</label>
                        <div><span class="badge badge-success">Aktif</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Content Grid -->
        <div class="content-grid">
            <!-- Available Books -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-book me-2"></i>Buku Tersedia
                    </h5>
                </div>
                <div class="content-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Judul Buku</th>
                                    <th>Penulis</th>
                                    <th>Kategori</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availableBooks as $book): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                                        <td>
                                            <span class="badge badge-success"><?php echo $book['available_copies']; ?> tersedia</span>
                                        </td>
                                        <td>
                                            <a href="borrowings.php" class="btn btn-sm btn-success">Pinjam</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
