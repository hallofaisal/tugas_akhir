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
            $pdo = getConnection();
            
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
            $pdo = getConnection();
            
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
    $pdo = getConnection();
    
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
    $pdo = getConnection();
    
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
    <style>
        body { background: #f8f9fa; }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .table-responsive { margin-top: 1rem; }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-book"></i> Manajemen Peminjaman</h1>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $active_borrowings ?></h4>
                            <small>Aktif</small>
                        </div>
                        <i class="bi bi-book display-6"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $overdue_borrowings ?></h4>
                            <small>Terlambat</small>
                        </div>
                        <i class="bi bi-exclamation-triangle display-6"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $returned_borrowings ?></h4>
                            <small>Dikembalikan</small>
                        </div>
                        <i class="bi bi-check-circle display-6"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">Rp <?= number_format($total_fines, 0, ',', '.') ?></h4>
                            <small>Total Denda</small>
                        </div>
                        <i class="bi bi-cash display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="borrowed" <?= $status_filter === 'borrowed' ? 'selected' : '' ?>>Dipinjam</option>
                                <option value="overdue" <?= $status_filter === 'overdue' ? 'selected' : '' ?>>Terlambat</option>
                                <option value="returned" <?= $status_filter === 'returned' ? 'selected' : '' ?>>Dikembalikan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" placeholder="Tanggal Mulai">
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" placeholder="Tanggal Akhir">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama/judul/penulis..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-12 mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="borrowings.php" class="btn btn-outline-secondary ms-2">Reset</a>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" name="update_overdue" class="btn btn-warning" onclick="return confirm('Update status keterlambatan untuk semua peminjaman yang melewati batas waktu?')">
                            <i class="bi bi-clock"></i> Update Keterlambatan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Borrowings Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list"></i> Daftar Peminjaman</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Denda</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($borrowings)): ?>
                        <tr><td colspan="8" class="text-center text-muted">Tidak ada data peminjaman.</td></tr>
                    <?php else: foreach ($borrowings as $i => $b): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($b['borrower_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($b['username']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($b['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($b['author']) ?></small>
                            </td>
                            <td><?= date('d/m/Y', strtotime($b['borrow_date'])) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($b['due_date'])) ?>
                                <?php if ($b['status'] === 'overdue'): ?>
                                    <br><small class="text-danger">Terlambat <?= date_diff(new DateTime($b['due_date']), new DateTime())->days ?> hari</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch ($b['status']) {
                                    case 'borrowed':
                                        $status_class = 'bg-primary';
                                        $status_text = 'Dipinjam';
                                        break;
                                    case 'returned':
                                        $status_class = 'bg-success';
                                        $status_text = 'Dikembalikan';
                                        break;
                                    case 'overdue':
                                        $status_class = 'bg-danger';
                                        $status_text = 'Terlambat';
                                        break;
                                    case 'lost':
                                        $status_class = 'bg-dark';
                                        $status_text = 'Hilang';
                                        break;
                                }
                                ?>
                                <span class="badge <?= $status_class ?> status-badge"><?= $status_text ?></span>
                            </td>
                            <td>
                                <?php if ($b['fine_amount'] > 0): ?>
                                    <span class="text-danger">Rp <?= number_format($b['fine_amount'], 0, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (in_array($b['status'], ['borrowed', 'overdue'])): ?>
                                    <button class="btn btn-sm btn-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#returnModal<?= $b['id'] ?>">
                                        <i class="bi bi-check-circle"></i> Kembalikan
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Sudah dikembalikan</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Return Modal for each borrowing -->
                        <?php if (in_array($b['status'], ['borrowed', 'overdue'])): ?>
                        <div class="modal fade" id="returnModal<?= $b['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form class="modal-content" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="borrowing_id" value="<?= $b['id'] ?>">
                                    <input type="hidden" name="return_book" value="1">
                                    
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-check-circle"></i> Proses Pengembalian
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <h6><?= htmlspecialchars($b['title']) ?></h6>
                                            <small class="text-muted">
                                                Peminjam: <?= htmlspecialchars($b['borrower_name']) ?><br>
                                                Penulis: <?= htmlspecialchars($b['author']) ?><br>
                                                Dipinjam: <?= date('d/m/Y', strtotime($b['borrow_date'])) ?><br>
                                                Jatuh tempo: <?= date('d/m/Y', strtotime($b['due_date'])) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Pengembalian</label>
                                            <input type="date" name="return_date" class="form-control" 
                                                   value="<?= $today ?>" max="<?= $today ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Denda (Rp)</label>
                                            <input type="number" name="fine_amount" class="form-control" 
                                                   value="0" min="0" step="1000">
                                            <small class="text-muted">Biarkan 0 jika tidak ada denda</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Catatan</label>
                                            <textarea name="notes" class="form-control" rows="3" 
                                                      placeholder="Catatan pengembalian..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> Konfirmasi Pengembalian
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + count($borrowings), $total_borrowings) ?> dari <?= $total_borrowings ?> peminjaman
                </div>
                <nav aria-label="Pagination peminjaman">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Previous button -->
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
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
                                <a class="page-link" href="?page=1&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next button -->
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" aria-label="Next">
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
// Auto-calculate fine when return date changes
document.querySelectorAll('input[name="return_date"]').forEach(function(input) {
    input.addEventListener('change', function() {
        const modal = this.closest('.modal');
        const dueDateText = modal.querySelector('.alert-info small').textContent;
        const dueDateMatch = dueDateText.match(/Jatuh tempo: (\d{2}\/\d{2}\/\d{4})/);
        
        if (dueDateMatch) {
            const dueDate = new Date(dueDateMatch[1].split('/').reverse().join('-'));
            const returnDate = new Date(this.value);
            
            if (returnDate > dueDate) {
                const daysOverdue = Math.ceil((returnDate - dueDate) / (1000 * 60 * 60 * 24));
                const fineAmount = daysOverdue * 1000;
                
                const fineInput = modal.querySelector('input[name="fine_amount"]');
                fineInput.value = fineAmount;
            }
        }
    });
});
</script>
</body>
</html> 