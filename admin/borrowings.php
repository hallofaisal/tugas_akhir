<?php
// Proteksi admin
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
require_once '../includes/visitor_logger.php';
requireAdmin();

require_once '../db.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('admin/borrowings.php');

$success = $error = '';

// Process book return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $borrowing_id = intval($_POST['borrowing_id']);
    $return_date = $_POST['return_date'];
    $fine_amount = floatval($_POST['fine_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validation
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($borrowing_id <= 0 || empty($return_date)) {
        $error = 'Data tidak valid.';
    } else {
        try {
            if (!isset($pdo) || !$pdo) {
                $pdo = require_once '../db.php';
            }
            
            // Check if borrowing exists and is active
            $stmt = $pdo->prepare("
                SELECT b.id, b.book_id, b.borrow_date, b.due_date, b.status, bk.title, bk.available_copies
                FROM borrowings b
                JOIN books bk ON b.book_id = bk.id
                WHERE b.id = ? AND b.status IN ('borrowed', 'overdue')
            ");
            $stmt->execute([$borrowing_id]);
            $borrowing = $stmt->fetch();
            
            if (!$borrowing) {
                $error = 'Data peminjaman tidak ditemukan atau sudah dikembalikan.';
            } else {
                // Calculate fine if overdue
                $due_date = new DateTime($borrowing['due_date']);
                $return_date_obj = new DateTime($return_date);
                $calculated_fine = 0;
                
                if ($return_date_obj > $due_date) {
                    $days_overdue = $return_date_obj->diff($due_date)->days;
                    $calculated_fine = $days_overdue * 1000; // Rp 1.000 per hari
                }
                
                // Use calculated fine if not manually set
                if ($fine_amount == 0) {
                    $fine_amount = $calculated_fine;
                }
                
                // Begin transaction
                $pdo->beginTransaction();
                
                try {
                    // Update borrowing status
                    $stmt = $pdo->prepare("
                        UPDATE borrowings 
                        SET return_date = ?, status = 'returned', fine_amount = ?, notes = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$return_date, $fine_amount, $notes, $borrowing_id]);
                    
                    // Update book available copies
                    $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                    $stmt->execute([$borrowing['book_id']]);
                    
                    $pdo->commit();
                    $success = 'Buku berhasil dikembalikan.';
                    
                    // Redirect to prevent form resubmission
                    header('Location: borrowings.php?success=1');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollback();
                    throw $e;
                }
            }
        } catch (PDOException $e) {
            $error = 'Gagal memproses pengembalian: ' . $e->getMessage();
        }
    }
}

// Process overdue status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_overdue'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        try {
            if (!isset($pdo) || !$pdo) {
                $pdo = require_once '../db.php';
            }
            
            // Update overdue borrowings
            $stmt = $pdo->prepare("
                UPDATE borrowings 
                SET status = 'overdue', 
                    fine_amount = DATEDIFF(CURDATE(), due_date) * 1000,
                    updated_at = NOW()
                WHERE status = 'borrowed' 
                AND due_date < CURDATE()
            ");
            $stmt->execute();
            
            $updated_count = $stmt->rowCount();
            $success = "Berhasil memperbarui $updated_count peminjaman yang terlambat.";
            
            header('Location: borrowings.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui status keterlambatan: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR bk.title LIKE ? OR bk.author LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($start_date && $end_date) {
    $where_conditions[] = "b.borrow_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
} elseif ($start_date) {
    $where_conditions[] = "b.borrow_date >= ?";
    $params[] = $start_date;
} elseif ($end_date) {
    $where_conditions[] = "b.borrow_date <= ?";
    $params[] = $end_date;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get borrowings with pagination
$books_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $books_per_page;

try {
    if (!isset($pdo) || !$pdo) {
        $pdo = require_once '../db.php';
    }
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) 
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_borrowings = $stmt->fetchColumn();
    $total_pages = ceil($total_borrowings / $books_per_page);
    
    // Validate current page
    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $books_per_page;
    }
    
    // Get borrowings
    $sql = "
        SELECT b.id, b.borrow_date, b.due_date, b.return_date, b.status, b.fine_amount, b.notes,
               u.full_name as borrower_name, u.username,
               bk.title, bk.author, bk.isbn, bk.available_copies
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        $where_clause
        ORDER BY 
            CASE 
                WHEN b.status = 'overdue' THEN 1
                WHEN b.status = 'borrowed' THEN 2
                ELSE 3
            END,
            b.due_date ASC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $books_per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $borrowings = [];
    $total_borrowings = 0;
    $total_pages = 0;
    $error = 'Gagal mengambil data peminjaman.';
}

// Get statistics
try {
    if (!isset($pdo) || !$pdo) {
        $pdo = require_once '../db.php';
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE status = 'borrowed'");
    $stmt->execute();
    $active_borrowings = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE status = 'overdue'");
    $stmt->execute();
    $overdue_borrowings = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE status = 'returned'");
    $stmt->execute();
    $returned_borrowings = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT SUM(fine_amount) FROM borrowings WHERE fine_amount > 0");
    $stmt->execute();
    $total_fines = $stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    $active_borrowings = $overdue_borrowings = $returned_borrowings = $total_fines = 0;
}

// CSRF token
$csrf_token = generateCSRFToken();

// Set default return date
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Peminjaman - Admin</title>
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
        
        .btn-success {
            background: #16a34a;
            border-color: #16a34a;
        }
        
        .btn-success:hover {
            background: #15803d;
            border-color: #15803d;
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
        
        .btn-return {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .btn-return:hover {
            background: #dcfce7;
            color: #15803d;
        }
        
        .btn-view {
            background: #eff6ff;
            color: #3b82f6;
            border: 1px solid #bfdbfe;
        }
        
        .btn-view:hover {
            background: #dbeafe;
            color: #2563eb;
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
        
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .toolbar .row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .filter-row {
                flex-direction: column;
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
                        <i class="bi bi-journal-check me-2"></i>Manajemen Peminjaman
                    </h1>
                    <p class="page-subtitle">Kelola peminjaman buku dan pengembalian</p>
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
                        <h3 class="stat-number"><?= $active_borrowings ?></h3>
                        <p class="stat-label">Peminjaman Aktif</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $overdue_borrowings ?></h3>
                        <p class="stat-label">Terlambat</p>
                    </div>
                    <div class="stat-icon danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $returned_borrowings ?></h3>
                        <p class="stat-label">Dikembalikan</p>
                    </div>
                    <div class="stat-icon success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number">Rp <?= number_format($total_fines, 0, ',', '.') ?></h3>
                        <p class="stat-label">Total Denda</p>
                    </div>
                    <div class="stat-icon warning">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="row align-items-center mb-3">
                <div class="col-md-8">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="Cari berdasarkan nama peminjam, judul buku, atau ID peminjaman..." 
                               onkeyup="filterBorrowings()">
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-success me-2" onclick="updateOverdue()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Update Keterlambatan
                    </button>
                    <button class="btn btn-primary" onclick="exportPDF()">
                        <i class="bi bi-file-pdf me-1"></i>Export PDF
                    </button>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="statusFilter" onchange="filterBorrowings()">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="overdue">Terlambat</option>
                        <option value="returned">Dikembalikan</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" id="startDate" onchange="filterBorrowings()">
                </div>
                <div class="filter-group">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" id="endDate" onchange="filterBorrowings()">
                </div>
                <div class="filter-group">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="bi bi-x-circle me-1"></i>Reset Filter
                    </button>
                </div>
            </div>
            
            <div class="mt-2">
                <small class="text-muted" id="searchResults">Menampilkan semua peminjaman</small>
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
                    <i class="bi bi-list me-2"></i>Daftar Peminjaman
                </h5>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID</th>
                            <th>Peminjam</th>
                            <th>Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($borrowings)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                Tidak ada data peminjaman
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-search display-6 d-block mb-2"></i>
                                Tidak ada peminjaman yang cocok dengan pencarian
                            </td>
                        </tr>
                        <?php foreach ($borrowings as $i => $b): ?>
                        <tr class="borrowing-row" 
                            data-borrower="<?= htmlspecialchars(strtolower($b['borrower_name'])) ?>" 
                            data-book="<?= htmlspecialchars(strtolower($b['title'])) ?>" 
                            data-status="<?= $b['status'] ?>"
                            data-date="<?= $b['borrow_date'] ?>">
                            <td><?= $offset + $i + 1 ?></td>
                            <td><code class="text-secondary">#<?= $b['id'] ?></code></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($b['borrower_name']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($b['username']) ?></small>
                            </td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($b['title']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($b['isbn']) ?></small>
                            </td>
                            <td><?= date('d/m/Y', strtotime($b['borrow_date'])) ?></td>
                            <td>
                                <?php if ($b['return_date']): ?>
                                    <?= date('d/m/Y', strtotime($b['return_date'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status = $b['status'];
                                if ($status == 'active') {
                                    $badge_class = 'badge-success';
                                    $status_text = 'Aktif';
                                } elseif ($status == 'overdue') {
                                    $badge_class = 'badge-danger';
                                    $status_text = 'Terlambat';
                                } else {
                                    $badge_class = 'badge-secondary';
                                    $status_text = 'Dikembalikan';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                            </td>
                            <td>
                                <?php if ($b['fine_amount'] > 0): ?>
                                    <span class="text-danger fw-bold">Rp <?= number_format($b['fine_amount'], 0, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($b['status'] != 'returned'): ?>
                                    <button class="btn btn-sm btn-return me-1" data-bs-toggle="modal" data-bs-target="#modalReturn<?= $b['id'] ?>" title="Kembalikan">
                                        <i class="bi bi-arrow-return-left"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-view" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $b['id'] ?>" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Modal Return Book -->
                        <div class="modal fade" id="modalReturn<?= $b['id'] ?>" tabindex="-1">
                          <div class="modal-dialog">
                            <form class="modal-content" method="post" action="?return=<?= $b['id'] ?>">
                              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                              <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-arrow-return-left me-2"></i>Kembalikan Buku
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <p>Apakah Anda yakin ingin mengembalikan buku ini?</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Peminjam:</strong><br>
                                        <?= htmlspecialchars($b['borrower_name']) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Buku:</strong><br>
                                        <?= htmlspecialchars($b['title']) ?>
                                    </div>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label class="form-label">Kondisi Buku</label>
                                    <select name="condition" class="form-control" required>
                                        <option value="good">Baik</option>
                                        <option value="damaged">Rusak Ringan</option>
                                        <option value="lost">Hilang</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catatan (Opsional)</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check me-1"></i>Kembalikan
                                </button>
                              </div>
                            </form>
                          </div>
                        </div>
                        
                        <!-- Modal Detail -->
                        <div class="modal fade" id="modalDetail<?= $b['id'] ?>" tabindex="-1">
                          <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-info-circle me-2"></i>Detail Peminjaman
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Informasi Peminjam</h6>
                                        <p><strong>Nama:</strong> <?= htmlspecialchars($b['borrower_name']) ?></p>
                                        <p><strong>ID:</strong> <?= htmlspecialchars($b['username']) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($b['borrower_email'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Informasi Buku</h6>
                                        <p><strong>Judul:</strong> <?= htmlspecialchars($b['title']) ?></p>
                                        <p><strong>ISBN:</strong> <?= htmlspecialchars($b['isbn']) ?></p>
                                        <p><strong>Penulis:</strong> <?= htmlspecialchars($b['author'] ?? '-') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Informasi Peminjaman</h6>
                                        <p><strong>Tanggal Pinjam:</strong> <?= date('d/m/Y H:i', strtotime($b['borrow_date'])) ?></p>
                                        <p><strong>Jatuh Tempo:</strong> <?= date('d/m/Y', strtotime($b['due_date'])) ?></p>
                                        <?php if ($b['return_date']): ?>
                                            <p><strong>Tanggal Kembali:</strong> <?= date('d/m/Y H:i', strtotime($b['return_date'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold">Status & Denda</h6>
                                        <p><strong>Status:</strong> 
                                            <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                        </p>
                                        <?php if ($b['fine_amount'] > 0): ?>
                                            <p><strong>Denda:</strong> <span class="text-danger fw-bold">Rp <?= number_format($b['fine_amount'], 0, ',', '.') ?></span></p>
                                        <?php endif; ?>
                                        <?php if ($b['condition']): ?>
                                            <p><strong>Kondisi:</strong> <?= ucfirst($b['condition']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($b['notes']): ?>
                                    <hr>
                                    <div>
                                        <h6 class="fw-bold">Catatan</h6>
                                        <p class="text-muted"><?= htmlspecialchars($b['notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                              </div>
                            </div>
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
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + count($borrowings), $total_borrowings) ?> dari <?= $total_borrowings ?> peminjaman
                </div>
                <nav aria-label="Pagination peminjaman">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterBorrowings() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const statusFilter = document.getElementById('statusFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    const borrowingRows = document.querySelectorAll('.borrowing-row');
    const noResultsRow = document.getElementById('noResultsRow');
    const searchResults = document.getElementById('searchResults');
    const paginationDiv = document.querySelector('.d-flex.justify-content-between.align-items-center.p-3');
    
    let visibleCount = 0;
    let totalCount = borrowingRows.length;
    
    // Hide pagination when filtering
    if (searchTerm !== '' || statusFilter !== '' || startDate !== '' || endDate !== '') {
        if (paginationDiv) paginationDiv.style.display = 'none';
    } else {
        if (paginationDiv) paginationDiv.style.display = 'flex';
    }
    
    borrowingRows.forEach((row) => {
        const borrower = row.getAttribute('data-borrower') || '';
        const book = row.getAttribute('data-book') || '';
        const status = row.getAttribute('data-status') || '';
        const date = row.getAttribute('data-date') || '';
        
        // Check search term
        const matchesSearch = searchTerm === '' || 
                             borrower.includes(searchTerm) || 
                             book.includes(searchTerm);
        
        // Check status filter
        const matchesStatus = statusFilter === '' || status === statusFilter;
        
        // Check date range
        let matchesDate = true;
        if (startDate && date < startDate) matchesDate = false;
        if (endDate && date > endDate) matchesDate = false;
        
        if (matchesSearch && matchesStatus && matchesDate) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    if (visibleCount === 0 && (searchTerm !== '' || statusFilter !== '' || startDate !== '' || endDate !== '')) {
        noResultsRow.style.display = '';
    } else {
        noResultsRow.style.display = 'none';
    }
    
    // Update results counter
    if (searchTerm === '' && statusFilter === '' && startDate === '' && endDate === '') {
        searchResults.textContent = `Menampilkan semua peminjaman (${totalCount})`;
    } else {
        searchResults.textContent = `Menampilkan ${visibleCount} dari ${totalCount} peminjaman`;
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    filterBorrowings();
    
    // Restore pagination display
    const paginationDiv = document.querySelector('.d-flex.justify-content-between.align-items-center.p-3');
    if (paginationDiv) paginationDiv.style.display = 'flex';
}

function updateOverdue() {
    if (confirm('Update status keterlambatan untuk semua peminjaman?')) {
        window.location.href = '?update_overdue=1';
    }
}

function exportPDF() {
    window.open('export_borrowings.php', '_blank');
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
    
    // Clear filters when pressing Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            clearFilters();
        }
    });
});
</script>
</body>
</html> 
