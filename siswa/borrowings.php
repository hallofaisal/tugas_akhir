<?php
session_start();
/**
 * Student Borrowings Page
 * File: siswa/borrowings.php
 * Description: Library borrowing management for students with modern design
 */

// Include middleware system
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';

// Apply middleware protection
requireSiswa();

// Get current user data from session
$currentUser = get_current_user_data();

// Sample data for borrowings (no database queries)
$activeBorrowings = 2;
$maxBorrowings = 3;
$availableSlots = $maxBorrowings - $activeBorrowings;

// Sample available books
$availableBooks = [
    [
        'id' => 1,
        'title' => 'Fisika Modern',
        'author' => 'Dr. Sarah Wilson',
        'category' => 'Pelajaran',
        'isbn' => '978-1234567890',
        'publisher' => 'Penerbit Sains',
        'available_copies' => 3,
        'location' => 'Rak A-1'
    ],
    [
        'id' => 2,
        'title' => 'Kimia Dasar',
        'author' => 'Prof. Michael Brown',
        'category' => 'Pelajaran',
        'isbn' => '978-1234567891',
        'publisher' => 'Penerbit Kimia',
        'available_copies' => 2,
        'location' => 'Rak A-2'
    ],
    [
        'id' => 3,
        'title' => 'Biologi Sel',
        'author' => 'Dr. Lisa Chen',
        'category' => 'Pelajaran',
        'isbn' => '978-1234567892',
        'publisher' => 'Penerbit Biologi',
        'available_copies' => 4,
        'location' => 'Rak A-3'
    ],
    [
        'id' => 4,
        'title' => 'Ekonomi Mikro',
        'author' => 'Prof. John Smith',
        'category' => 'Pelajaran',
        'isbn' => '978-1234567893',
        'publisher' => 'Penerbit Ekonomi',
        'available_copies' => 1,
        'location' => 'Rak B-1'
    ],
    [
        'id' => 5,
        'title' => 'Sejarah Indonesia',
        'author' => 'Dr. Budi Santoso',
        'category' => 'Pelajaran',
        'isbn' => '978-1234567894',
        'publisher' => 'Penerbit Sejarah',
        'available_copies' => 2,
        'location' => 'Rak B-2'
    ]
];

// Sample borrowing history
$borrowingHistory = [
    [
        'id' => 1,
        'book_title' => 'Matematika Dasar',
        'author' => 'Prof. Dr. Ahmad',
        'isbn' => '978-1234567880',
        'borrow_date' => '2024-01-15',
        'due_date' => '2024-01-29',
        'return_date' => '2024-01-22',
        'status' => 'returned',
        'fine_amount' => 0,
        'notes' => 'Buku sangat bermanfaat'
    ],
    [
        'id' => 2,
        'book_title' => 'Sejarah Indonesia',
        'author' => 'Dr. Budi Santoso',
        'isbn' => '978-1234567881',
        'borrow_date' => '2024-01-20',
        'due_date' => '2024-02-03',
        'return_date' => null,
        'status' => 'borrowed',
        'fine_amount' => 0,
        'notes' => 'Untuk tugas sejarah'
    ],
    [
        'id' => 3,
        'book_title' => 'Fisika Modern',
        'author' => 'Dr. Sarah Wilson',
        'isbn' => '978-1234567882',
        'borrow_date' => '2024-01-25',
        'due_date' => '2024-02-08',
        'return_date' => null,
        'status' => 'borrowed',
        'fine_amount' => 0,
        'notes' => 'Referensi fisika'
    ],
    [
        'id' => 4,
        'book_title' => 'Bahasa Indonesia',
        'author' => 'Dr. Siti Aminah',
        'isbn' => '978-1234567883',
        'borrow_date' => '2024-01-10',
        'due_date' => '2024-01-24',
        'return_date' => '2024-01-18',
        'status' => 'returned',
        'fine_amount' => 0,
        'notes' => 'Buku pelajaran'
    ]
];

// Process form submission (simulated)
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['borrow_book'])) {
        if ($availableSlots > 0) {
            $success = 'Buku berhasil dipinjam! Silakan ambil buku di perpustakaan.';
        } else {
            $error = 'Anda telah mencapai batas maksimal peminjaman (3 buku).';
        }
    } elseif (isset($_POST['return_book'])) {
        $success = 'Buku berhasil dikembalikan!';
    }
}

// Student data
$fullName = $currentUser['full_name'] ?? 'Siswa Demo';
$username = $currentUser['username'] ?? 'siswa';
$nis = '2024001';
$kelas = 'X-A';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Buku - Sistem Informasi Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        
        .status-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }
        
        .status-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .book-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
            height: 100%;
        }
        
        .book-card:hover {
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
            transform: translateY(-2px);
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
        
        .btn-warning {
            background: #f59e0b;
            border-color: #f59e0b;
        }
        
        .btn-warning:hover {
            background: #d97706;
            border-color: #d97706;
        }
        
        .btn-danger {
            background: #ef4444;
            border-color: #ef4444;
        }
        
        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
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
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
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
        
        .search-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem;
        }
        
        .search-input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 0.875rem;
        }
        
        .search-input::placeholder {
            color: #9ca3af;
        }
        
        @media (max-width: 768px) {
            .book-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="bi bi-journal-check me-2"></i>Peminjaman Buku
                    </h1>
                    <p class="page-subtitle">Kelola peminjaman dan pengembalian buku perpustakaan</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Dashboard
                    </a>
                    <a href="../logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Borrowing Status -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="status-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-semibold mb-1">
                                <i class="bi bi-book me-2"></i>Status Peminjaman
                            </h6>
                            <p class="text-muted mb-2">
                                Anda telah meminjam <strong><?= $activeBorrowings ?></strong> dari <strong><?= $maxBorrowings ?></strong> buku maksimal
                            </p>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-<?= $activeBorrowings >= 3 ? 'danger' : ($activeBorrowings >= 2 ? 'warning' : 'success') ?>" 
                                     style="width: <?= ($activeBorrowings / $maxBorrowings) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="h3 mb-0 text-<?= $activeBorrowings >= 3 ? 'danger' : ($activeBorrowings >= 2 ? 'warning' : 'success') ?>">
                                <?= $activeBorrowings ?>/<?= $maxBorrowings ?>
                            </div>
                            <small class="text-muted">Buku Aktif</small>
                        </div>
                    </div>
                    <?php if ($activeBorrowings >= 3): ?>
                        <div class="alert alert-danger mt-3 mb-0 py-2">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <small>Anda telah mencapai batas maksimal peminjaman. Silakan kembalikan buku terlebih dahulu.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="status-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-semibold mb-1">
                                <i class="bi bi-person me-2"></i>Informasi Siswa
                            </h6>
                            <p class="text-muted mb-0">
                                <strong><?= htmlspecialchars($fullName) ?></strong><br>
                                NIS: <?= htmlspecialchars($nis) ?> • Kelas: <?= htmlspecialchars($kelas) ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="h3 mb-0 text-success">
                                <?= $availableSlots ?>
                            </div>
                            <small class="text-muted">Slot Tersedia</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Books -->
        <div class="content-card mb-4">
            <div class="content-header">
                <h5 class="content-title">
                    <i class="bi bi-book me-2"></i>Buku Tersedia
                </h5>
            </div>
            <div class="content-body">
                <!-- Search Box -->
                <div class="search-box mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-search text-muted me-2"></i>
                        <input type="text" class="search-input" placeholder="Cari judul buku, penulis, atau kategori..." id="searchBooks">
                    </div>
                </div>

                <div class="row" id="booksContainer">
                    <?php foreach ($availableBooks as $book): ?>
                        <div class="col-md-6 col-lg-4 mb-3 book-item">
                            <div class="book-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-semibold mb-1"><?= htmlspecialchars($book['title']) ?></h6>
                                    <span class="badge badge-success"><?= $book['available_copies'] ?> tersedia</span>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-person me-1"></i><?= htmlspecialchars($book['author']) ?>
                                </p>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-tag me-1"></i><?= htmlspecialchars($book['category']) ?>
                                </p>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($book['location']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">ISBN: <?= htmlspecialchars($book['isbn']) ?></small>
                                    <?php if ($availableSlots > 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                            <button type="submit" name="borrow_book" class="btn btn-sm btn-success">
                                                <i class="bi bi-plus-circle me-1"></i>Pinjam
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="bi bi-x-circle me-1"></i>Penuh
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Borrowing History -->
        <div class="content-card">
            <div class="content-header">
                <h5 class="content-title">
                    <i class="bi bi-clock-history me-2"></i>Riwayat Peminjaman
                </h5>
            </div>
            <div class="content-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Judul Buku</th>
                                <th>Penulis</th>
                                <th>Tanggal Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowingHistory as $borrowing): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($borrowing['book_title']) ?></strong>
                                        <br><small class="text-muted">ISBN: <?= htmlspecialchars($borrowing['isbn']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($borrowing['author']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($borrowing['borrow_date'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($borrowing['due_date'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= $borrowing['status'] ?>">
                                            <?= ucfirst($borrowing['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($borrowing['notes']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($borrowing['status'] === 'borrowed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="borrowing_id" value="<?= $borrowing['id'] ?>">
                                                <button type="submit" name="return_book" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-arrow-return-left me-1"></i>Kembalikan
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchBooks').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const bookItems = document.querySelectorAll('.book-item');
            
            bookItems.forEach(item => {
                const title = item.querySelector('h6').textContent.toLowerCase();
                const author = item.querySelector('.text-muted').textContent.toLowerCase();
                const category = item.querySelectorAll('.text-muted')[1].textContent.toLowerCase();
                
                if (title.includes(searchTerm) || author.includes(searchTerm) || category.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 