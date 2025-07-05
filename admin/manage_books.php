<?php
// Proteksi admin
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
require_once '../includes/visitor_logger.php';
requireAdmin();

require_once '../db.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('admin/manage_books.php');

$success = $error = '';

// Proses Tambah Buku
if (isset($_GET['add']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year = intval($_POST['publication_year'] ?? 0);
    $total = intval($_POST['total_copies'] ?? 1);
    $available = intval($_POST['available_copies'] ?? $total);
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($title === '' || $author === '' || $total < 1) {
        $error = 'Judul, penulis, dan stok total wajib diisi.';
    } elseif ($year && ($year < 1900 || $year > 2100)) {
        $error = 'Tahun tidak valid.';
    } else {
        try {
            if (!isset($pdo) || !$pdo) {
                $pdo = require_once '../db.php';
            }
            $stmt = $pdo->prepare("INSERT INTO books (isbn, title, author, publisher, publication_year, category, total_copies, available_copies, location, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$isbn, $title, $author, $publisher, $year ?: null, $category, $total, $available, $location]);
            $success = 'Buku berhasil ditambahkan.';
            header('Location: manage_books.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menambah buku: ' . $e->getMessage();
        }
    }
}

// Proses Edit Buku
if (isset($_GET['edit']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_GET['edit']);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year = intval($_POST['publication_year'] ?? 0);
    $total = intval($_POST['total_copies'] ?? 1);
    $available = intval($_POST['available_copies'] ?? $total);
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($title === '' || $author === '' || $total < 1) {
        $error = 'Judul, penulis, dan stok total wajib diisi.';
    } elseif ($year && ($year < 1900 || $year > 2100)) {
        $error = 'Tahun tidak valid.';
    } else {
        try {
            if (!isset($pdo) || !$pdo) {
                $pdo = require_once '../db.php';
            }
            $stmt = $pdo->prepare("UPDATE books SET isbn=?, title=?, author=?, publisher=?, publication_year=?, category=?, total_copies=?, available_copies=?, location=? WHERE id=?");
            $stmt->execute([$isbn, $title, $author, $publisher, $year ?: null, $category, $total, $available, $location, $id]);
            $success = 'Buku berhasil diupdate.';
            header('Location: manage_books.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal mengupdate buku: ' . $e->getMessage();
        }
    }
}

// Proses Hapus Buku
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_GET['delete']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        try {
            if (!isset($pdo) || !$pdo) {
                $pdo = require_once '../db.php';
            }
            $stmt = $pdo->prepare("DELETE FROM books WHERE id=?");
            $stmt->execute([$id]);
            $success = 'Buku berhasil dihapus.';
            header('Location: manage_books.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menghapus buku: ' . $e->getMessage();
        }
    }
}

// Pagination settings
$books_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $books_per_page;

// Get total books count
try {
    if (!isset($pdo) || !$pdo) {
        $pdo = require_once '../db.php';
    }
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM books");
    $total_books = $count_stmt->fetchColumn();
    $total_pages = ceil($total_books / $books_per_page);
    
    // Validate current page
    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $books_per_page;
    }
} catch (PDOException $e) {
    $total_books = 0;
    $total_pages = 0;
    $error = 'Gagal mengambil data buku.';
}

// Ambil daftar buku dengan pagination
try {
    if (!isset($pdo) || !$pdo) {
        $pdo = require_once '../db.php';
    }
    $stmt = $pdo->prepare("SELECT * FROM books ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->execute([$books_per_page, $offset]);
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $books = [];
    $error = 'Gagal mengambil data buku.';
}

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku - Admin</title>
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
        
        .toolbar {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
        }
        
        .search-box .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
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
        
        .table {
            margin: 0;
        }
        
        .table th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
            padding: 1rem;
        }
        
        .table td {
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem;
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
        
        .badge-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .btn-edit {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fed7aa;
        }
        
        .btn-edit:hover {
            background: #fde68a;
            color: #b45309;
        }
        
        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .btn-delete:hover {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
            color: #1e293b;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.25rem;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #16a34a;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .pagination {
            margin: 0;
        }
        
        .page-link {
            border: 1px solid #e2e8f0;
            color: #64748b;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .page-link:hover {
            background: #f1f5f9;
            color: #374151;
        }
        
        .page-item.active .page-link {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .toolbar .row {
                flex-direction: column;
                gap: 1rem;
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
                        <i class="bi bi-book me-2"></i>Manajemen Buku
                    </h1>
                    <p class="page-subtitle">Kelola koleksi buku perpustakaan dengan mudah</p>
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $total_books ?></h3>
                        <p class="stat-label">Total Buku</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?php 
                            $available_books = 0;
                            foreach ($books as $book) {
                                if ($book['available_copies'] > 0) $available_books++;
                            }
                            echo $available_books;
                        ?></h3>
                        <p class="stat-label">Tersedia</p>
                    </div>
                    <div class="stat-icon success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?php 
                            $low_stock = 0;
                            foreach ($books as $book) {
                                if ($book['available_copies'] <= 2 && $book['available_copies'] > 0) $low_stock++;
                            }
                            echo $low_stock;
                        ?></h3>
                        <p class="stat-label">Stok Menipis</p>
                    </div>
                    <div class="stat-icon warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?php 
                            $out_of_stock = 0;
                            foreach ($books as $book) {
                                if ($book['available_copies'] == 0) $out_of_stock++;
                            }
                            echo $out_of_stock;
                        ?></h3>
                        <p class="stat-label">Habis Stok</p>
                    </div>
                    <div class="stat-icon danger">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="Cari buku berdasarkan judul, penulis, ISBN, atau kategori..." 
                               onkeyup="filterBooks()">
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus me-1"></i>Tambah Buku
                    </button>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted" id="searchResults">Menampilkan semua buku</small>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-header">
                <h5 class="content-title">
                    <i class="bi bi-list me-2"></i>Daftar Buku
                </h5>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ISBN</th>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Penerbit</th>
                            <th>Tahun</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                Tidak ada data buku
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-search display-6 d-block mb-2"></i>
                                Tidak ada buku yang cocok dengan pencarian
                            </td>
                        </tr>
                        <?php foreach ($books as $i => $b): ?>
                        <tr class="book-row" data-title="<?= htmlspecialchars(strtolower($b['title'])) ?>" data-author="<?= htmlspecialchars(strtolower($b['author'])) ?>" data-isbn="<?= htmlspecialchars(strtolower($b['isbn'])) ?>" data-category="<?= htmlspecialchars(strtolower($b['category'])) ?>" data-publisher="<?= htmlspecialchars(strtolower($b['publisher'])) ?>">
                            <td><?= $offset + $i + 1 ?></td>
                            <td><code class="text-secondary"><?= htmlspecialchars($b['isbn']) ?></code></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($b['title']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($b['author']) ?></td>
                            <td><?= htmlspecialchars($b['publisher']) ?: '-' ?></td>
                            <td><?= htmlspecialchars($b['publication_year']) ?: '-' ?></td>
                            <td>
                                <?php if ($b['category']): ?>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($b['category']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $available = $b['available_copies'];
                                $total = $b['total_copies'];
                                
                                if ($available == 0) {
                                    $badge_class = 'badge-danger';
                                    $status_text = 'Habis';
                                } elseif ($available <= 2) {
                                    $badge_class = 'badge-warning';
                                    $status_text = 'Menipis';
                                } else {
                                    $badge_class = 'badge-success';
                                    $status_text = 'Tersedia';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>">
                                    <?= $available ?>/<?= $total ?>
                                </span>
                                <br><small class="text-muted"><?= $status_text ?></small>
                            </td>
                            <td><?= htmlspecialchars($b['location']) ?: '-' ?></td>
                            <td>
                                <button class="btn btn-sm btn-edit me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $b['id'] ?>" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-delete" onclick="confirmDelete(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['title'])) ?>')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Modal Edit Buku -->
                        <div class="modal fade" id="modalEdit<?= $b['id'] ?>" tabindex="-1">
                          <div class="modal-dialog modal-lg">
                            <form class="modal-content" method="post" action="?edit=<?= $b['id'] ?>">
                              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                              <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-pencil me-2"></i>Edit Buku
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ISBN</label>
                                            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($b['isbn']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Judul</label>
                                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($b['title']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Penulis</label>
                                            <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($b['author']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Penerbit</label>
                                            <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($b['publisher']) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tahun Publikasi</label>
                                            <input type="number" name="publication_year" class="form-control" value="<?= htmlspecialchars($b['publication_year']) ?>" min="1900" max="2100">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($b['category']) ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Stok Total</label>
                                            <input type="number" name="total_copies" class="form-control" value="<?= htmlspecialchars($b['total_copies']) ?>" min="1">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Stok Tersedia</label>
                                            <input type="number" name="available_copies" class="form-control" value="<?= htmlspecialchars($b['available_copies']) ?>" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Lokasi</label>
                                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($b['location']) ?>">
                                        </div>
                                    </div>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check me-1"></i>Simpan Perubahan
                                </button>
                              </div>
                            </form>
                          </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="text-muted">
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + count($books), $total_books) ?> dari <?= $total_books ?> buku
                </div>
                <nav aria-label="Pagination buku">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Previous button -->
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&laquo;</span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Page numbers -->
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next button -->
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link" aria-hidden="true">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hidden form for delete action -->
<form id="deleteForm" method="post" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
</form>

<!-- Modal Tambah Buku -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="?add=1">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-header">
        <h5 class="modal-title">
            <i class="bi bi-plus-circle me-2"></i>Tambah Buku Baru
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Penulis</label>
                    <input type="text" name="author" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Penerbit</label>
                    <input type="text" name="publisher" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Tahun Publikasi</label>
                    <input type="number" name="publication_year" class="form-control" min="1900" max="2100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="category" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Stok Total</label>
                    <input type="number" name="total_copies" class="form-control" min="1" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Stok Tersedia</label>
                    <input type="number" name="available_copies" class="form-control" min="0" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="location" class="form-control">
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-plus me-1"></i>Tambah Buku
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(bookId, bookTitle) {
    if (confirm('Yakin ingin menghapus buku "' + bookTitle + '"?\n\nTindakan ini tidak dapat dibatalkan.')) {
        const form = document.getElementById('deleteForm');
        form.action = '?delete=' + bookId;
        form.submit();
    }
}

// Real-time search functionality
function filterBooks() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const bookRows = document.querySelectorAll('.book-row');
    const noResultsRow = document.getElementById('noResultsRow');
    const searchResults = document.getElementById('searchResults');
    const paginationDiv = document.querySelector('.d-flex.justify-content-between.align-items-center.p-3');
    
    let visibleCount = 0;
    let totalCount = bookRows.length;
    
    // Hide pagination when searching
    if (searchTerm !== '') {
        if (paginationDiv) paginationDiv.style.display = 'none';
    } else {
        if (paginationDiv) paginationDiv.style.display = 'flex';
    }
    
    bookRows.forEach((row, index) => {
        const title = row.getAttribute('data-title') || '';
        const author = row.getAttribute('data-author') || '';
        const isbn = row.getAttribute('data-isbn') || '';
        const category = row.getAttribute('data-category') || '';
        const publisher = row.getAttribute('data-publisher') || '';
        
        // Check if search term matches any field
        const matches = title.includes(searchTerm) || 
                       author.includes(searchTerm) || 
                       isbn.includes(searchTerm) || 
                       category.includes(searchTerm) || 
                       publisher.includes(searchTerm);
        
        if (matches || searchTerm === '') {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    if (visibleCount === 0 && searchTerm !== '') {
        noResultsRow.style.display = '';
    } else {
        noResultsRow.style.display = 'none';
    }
    
    // Update results counter
    if (searchTerm === '') {
        searchResults.textContent = `Menampilkan semua buku (${totalCount})`;
    } else {
        searchResults.textContent = `Menampilkan ${visibleCount} dari ${totalCount} buku`;
    }
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    filterBooks();
    // Restore pagination display
    const paginationDiv = document.querySelector('.d-flex.justify-content-between.align-items-center.p-3');
    if (paginationDiv) paginationDiv.style.display = 'flex';
}

// Add keyboard shortcuts
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    
    // Focus search box when pressing Ctrl+F
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
        }
    });
    
    // Clear search when pressing Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            clearSearch();
        }
    });
});
</script>
</body>
</html> 
