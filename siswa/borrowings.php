<?php
// Proteksi siswa
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
requireSiswa();

require_once '../db.php';

$success = $error = '';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Process book borrowing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = intval($_POST['book_id']);
    $borrow_date = $_POST['borrow_date'];
    $due_date = $_POST['due_date'];
    $notes = trim($_POST['notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validation
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($book_id <= 0 || empty($borrow_date) || empty($due_date)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strtotime($borrow_date) > strtotime($due_date)) {
        $error = 'Tanggal peminjaman tidak boleh lebih dari tanggal jatuh tempo.';
    } else {
        try {
            $pdo = getConnection();
            
            // Check if book is available
            $stmt = $pdo->prepare("SELECT id, title, available_copies FROM books WHERE id = ? AND available_copies > 0");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
            if (!$book) {
                $error = 'Buku tidak tersedia untuk dipinjam.';
            } else {
                // Check if user already borrowed this book
                $stmt = $pdo->prepare("SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status IN ('borrowed', 'overdue')");
                $stmt->execute([$user_id, $book_id]);
                if ($stmt->fetch()) {
                    $error = 'Anda sudah meminjam buku ini.';
                } else {
                    // Check if user has too many active borrowings (max 3)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status IN ('borrowed', 'overdue')");
                    $stmt->execute([$user_id]);
                    $active_borrowings = $stmt->fetchColumn();
                    
                    if ($active_borrowings >= 3) {
                        $error = 'Anda sudah meminjam maksimal 3 buku. Silakan kembalikan buku yang sudah dipinjam terlebih dahulu.';
                    } else {
                        // Begin transaction
                        $pdo->beginTransaction();
                        
                        try {
                            // Insert borrowing record
                            $stmt = $pdo->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status, notes) VALUES (?, ?, ?, ?, 'borrowed', ?)");
                            $stmt->execute([$user_id, $book_id, $borrow_date, $due_date, $notes]);
                            
                            // Update book available copies
                            $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
                            $stmt->execute([$book_id]);
                            
                            $pdo->commit();
                            $success = 'Buku berhasil dipinjam. Silakan ambil buku di perpustakaan.';
                            
                            // Redirect to prevent form resubmission
                            header('Location: borrowings.php?success=1');
                            exit;
                        } catch (Exception $e) {
                            $pdo->rollback();
                            throw $e;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Gagal meminjam buku: ' . $e->getMessage();
        }
    }
}

// Get available books
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, isbn, title, author, publisher, category, available_copies, location FROM books WHERE available_copies > 0 ORDER BY title");
    $available_books = $stmt->fetchAll();
} catch (PDOException $e) {
    $available_books = [];
    $error = 'Gagal mengambil data buku.';
}

// Get user's borrowing history
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT b.id, b.borrow_date, b.due_date, b.return_date, b.status, b.fine_amount, b.notes,
               bk.title, bk.author, bk.isbn
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.user_id = ?
        ORDER BY b.borrow_date DESC
    ");
    $stmt->execute([$user_id]);
    $borrowings = $stmt->fetchAll();
    
    // Count active borrowings
    $active_borrowings = count(array_filter($borrowings, function($b) {
        return in_array($b['status'], ['borrowed', 'overdue']);
    }));
    
} catch (PDOException $e) {
    $borrowings = [];
    $active_borrowings = 0;
}

// CSRF token
$csrf_token = generateCSRFToken();

// Set default dates
$today = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime('+14 days')); // 14 days from today
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Buku - Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .book-card { transition: transform 0.2s; }
        .book-card:hover { transform: translateY(-2px); }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .borrowing-history {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-book"></i> Peminjaman Buku</h1>
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

    <!-- Borrowing Status Card -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-<?= $active_borrowings >= 3 ? 'danger' : ($active_borrowings >= 2 ? 'warning' : 'success') ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">
                                <i class="bi bi-book"></i> Status Peminjaman
                            </h6>
                            <p class="card-text mb-0">
                                Anda telah meminjam <strong><?= $active_borrowings ?></strong> dari <strong>3</strong> buku maksimal
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="h4 mb-0 text-<?= $active_borrowings >= 3 ? 'danger' : ($active_borrowings >= 2 ? 'warning' : 'success') ?>">
                                <?= $active_borrowings ?>/3
                            </div>
                            <small class="text-muted">Buku Aktif</small>
                        </div>
                    </div>
                    <?php if ($active_borrowings >= 3): ?>
                        <div class="alert alert-danger mt-2 mb-0 py-2">
                            <i class="bi bi-exclamation-triangle"></i>
                            <small>Anda telah mencapai batas maksimal peminjaman. Silakan kembalikan buku terlebih dahulu.</small>
                        </div>
                    <?php elseif ($active_borrowings >= 2): ?>
                        <div class="alert alert-warning mt-2 mb-0 py-2">
                            <i class="bi bi-info-circle"></i>
                            <small>Anda dapat meminjam <?= 3 - $active_borrowings ?> buku lagi.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-title mb-1">
                        <i class="bi bi-info-circle"></i> Peraturan Peminjaman
                    </h6>
                    <ul class="card-text small mb-0">
                        <li>Maksimal 3 buku per siswa</li>
                        <li>Jangka waktu 14 hari</li>
                        <li>Denda keterlambatan Rp 1.000/hari</li>
                        <li>Buku harus dikembalikan dalam kondisi baik</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Available Books Section -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-books"></i> Buku yang Tersedia</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($available_books)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-4"></i>
                            <p class="mt-2">Tidak ada buku yang tersedia untuk dipinjam.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($available_books as $book): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card book-card h-100 border">
                                        <div class="card-body">
                                            <h6 class="card-title text-primary"><?= htmlspecialchars($book['title']) ?></h6>
                                            <p class="card-text small text-muted mb-1">
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($book['author']) ?>
                                            </p>
                                            <p class="card-text small text-muted mb-1">
                                                <i class="bi bi-building"></i> <?= htmlspecialchars($book['publisher']) ?>
                                            </p>
                                            <p class="card-text small text-muted mb-1">
                                                <i class="bi bi-tag"></i> <?= htmlspecialchars($book['category']) ?>
                                            </p>
                                            <p class="card-text small text-muted mb-2">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($book['location']) ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Tersedia (<?= $book['available_copies'] ?>)
                                                </span>
                                                <?php if ($active_borrowings >= 3): ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Anda telah mencapai batas maksimal peminjaman">
                                                        <i class="bi bi-x-circle"></i> Tidak Dapat Pinjam
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#borrowModal<?= $book['id'] ?>">
                                                        <i class="bi bi-plus-circle"></i> Pinjam
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Borrow Modal for each book -->
                                <div class="modal fade" id="borrowModal<?= $book['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form class="modal-content" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                            <input type="hidden" name="borrow_book" value="1">
                                            
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-book"></i> Pinjam Buku
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            
                                            <div class="modal-body">
                                                <?php if ($active_borrowings >= 2): ?>
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        <strong>Peringatan:</strong> Anda telah meminjam <?= $active_borrowings ?> buku. 
                                                        Setelah meminjam buku ini, Anda tidak dapat meminjam buku lain sampai mengembalikan salah satu buku.
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="alert alert-info">
                                                    <h6><?= htmlspecialchars($book['title']) ?></h6>
                                                    <small class="text-muted">
                                                        Penulis: <?= htmlspecialchars($book['author']) ?><br>
                                                        Penerbit: <?= htmlspecialchars($book['publisher']) ?><br>
                                                        Kategori: <?= htmlspecialchars($book['category']) ?>
                                                    </small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Tanggal Peminjaman</label>
                                                    <input type="date" name="borrow_date" class="form-control" 
                                                           value="<?= $today ?>" min="<?= $today ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Tanggal Jatuh Tempo</label>
                                                    <input type="date" name="due_date" class="form-control" 
                                                           value="<?= $default_due_date ?>" min="<?= $today ?>" required>
                                                    <small class="text-muted">Maksimal 14 hari dari tanggal peminjaman</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Catatan (Opsional)</label>
                                                    <textarea name="notes" class="form-control" rows="3" 
                                                              placeholder="Catatan tambahan..."></textarea>
                                                </div>
                                                
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    <strong>Peraturan Peminjaman:</strong>
                                                    <ul class="mb-0 mt-2">
                                                        <li>Maksimal 3 buku per siswa</li>
                                                        <li>Jangka waktu peminjaman 14 hari</li>
                                                        <li>Denda keterlambatan Rp 1.000/hari</li>
                                                        <li>Buku harus dikembalikan dalam kondisi baik</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-check-circle"></i> Konfirmasi Pinjam
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Borrowing History Section -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Peminjaman</h5>
                </div>
                <div class="card-body borrowing-history">
                    <?php if (empty($borrowings)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-inbox display-6"></i>
                            <p class="mt-2">Belum ada riwayat peminjaman.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrowings as $borrowing): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <h6 class="text-primary"><?= htmlspecialchars($borrowing['title']) ?></h6>
                                <p class="small text-muted mb-1">
                                    <i class="bi bi-person"></i> <?= htmlspecialchars($borrowing['author']) ?>
                                </p>
                                <p class="small text-muted mb-1">
                                    <i class="bi bi-calendar"></i> Pinjam: <?= date('d/m/Y', strtotime($borrowing['borrow_date'])) ?>
                                </p>
                                <p class="small text-muted mb-1">
                                    <i class="bi bi-calendar-check"></i> Jatuh tempo: <?= date('d/m/Y', strtotime($borrowing['due_date'])) ?>
                                </p>
                                
                                <?php if ($borrowing['return_date']): ?>
                                    <p class="small text-muted mb-1">
                                        <i class="bi bi-calendar-x"></i> Dikembalikan: <?= date('d/m/Y', strtotime($borrowing['return_date'])) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($borrowing['status']) {
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
                                    
                                    <?php if ($borrowing['fine_amount'] > 0): ?>
                                        <span class="text-danger small">
                                            Denda: Rp <?= number_format($borrowing['fine_amount'], 0, ',', '.') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($borrowing['notes']): ?>
                                    <p class="small text-muted mt-1 mb-0">
                                        <i class="bi bi-chat"></i> <?= htmlspecialchars($borrowing['notes']) ?>
                                    </p>
                                <?php endif; ?>
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
// Auto-calculate due date when borrow date changes
document.querySelectorAll('input[name="borrow_date"]').forEach(function(input) {
    input.addEventListener('change', function() {
        const borrowDate = new Date(this.value);
        const dueDate = new Date(borrowDate);
        dueDate.setDate(dueDate.getDate() + 14);
        
        const dueDateInput = this.closest('form').querySelector('input[name="due_date"]');
        dueDateInput.value = dueDate.toISOString().split('T')[0];
        dueDateInput.min = this.value;
    });
});

// Validate due date
document.querySelectorAll('input[name="due_date"]').forEach(function(input) {
    input.addEventListener('change', function() {
        const borrowDateInput = this.closest('form').querySelector('input[name="borrow_date"]');
        const borrowDate = new Date(borrowDateInput.value);
        const dueDate = new Date(this.value);
        
        if (dueDate <= borrowDate) {
            alert('Tanggal jatuh tempo harus lebih dari tanggal peminjaman.');
            this.value = '';
        }
    });
});
</script>
</body>
</html> 