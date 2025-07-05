<?php
/**
 * Admin Visitors Management
 * File: admin/visitors.php
 * Description: Admin page to view and manage visitor data
 */

// Proteksi admin
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
requireAdmin();

require_once '../db.php';

$success = $error = '';

// Process visitor checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_visitor'])) {
    $visitor_id = intval($_POST['visitor_id']);
    $notes = trim($_POST['notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validation
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($visitor_id <= 0) {
        $error = 'Data tidak valid.';
    } else {
        try {
            $pdo = getConnection();
            
            // Check if visitor exists and hasn't checked out
            $stmt = $pdo->prepare("
                SELECT id, name, check_in, check_out 
                FROM visitors 
                WHERE id = ? AND check_out IS NULL
            ");
            $stmt->execute([$visitor_id]);
            $visitor = $stmt->fetch();
            
            if (!$visitor) {
                $error = 'Data pengunjung tidak ditemukan atau sudah checkout.';
            } else {
                // Update checkout time
                $stmt = $pdo->prepare("
                    UPDATE visitors 
                    SET check_out = CURTIME(), notes = CONCAT(IFNULL(notes, ''), ' | Checkout: ', CURTIME())
                    WHERE id = ?
                ");
                $stmt->execute([$visitor_id]);
                
                $success = 'Pengunjung berhasil checkout.';
                
                // Redirect to prevent form resubmission
                header('Location: visitors.php?success=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Gagal memproses checkout: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($date_filter) {
    $where_conditions[] = "visit_date = ?";
    $params[] = $date_filter;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR purpose LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter === 'active') {
    $where_conditions[] = "check_out IS NULL";
} elseif ($status_filter === 'checked_out') {
    $where_conditions[] = "check_out IS NOT NULL";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get visitors with pagination
$visitors_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $visitors_per_page;

try {
    $pdo = getConnection();
    
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM visitors $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_visitors = $stmt->fetchColumn();
    $total_pages = ceil($total_visitors / $visitors_per_page);
    
    // Validate current page
    if ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $visitors_per_page;
    }
    
    // Get visitors
    $sql = "
        SELECT id, name, purpose, visit_date, check_in, check_out, notes
        FROM visitors 
        $where_clause
        ORDER BY visit_date DESC, check_in DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $visitors_per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $visitors = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $visitors = [];
    $total_visitors = 0;
    $total_pages = 0;
    $error = 'Gagal mengambil data pengunjung.';
}

// Get statistics
try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visit_date = CURDATE()");
    $stmt->execute();
    $today_visitors = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visit_date = CURDATE() AND check_out IS NULL");
    $stmt->execute();
    $active_visitors = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visit_date = CURDATE() AND check_out IS NOT NULL");
    $stmt->execute();
    $checked_out_visitors = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute();
    $weekly_visitors = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $today_visitors = $active_visitors = $checked_out_visitors = $weekly_visitors = 0;
}

// CSRF token
$csrf_token = generateCSRFToken();

// Set default date filter to today
if (empty($date_filter)) {
    $date_filter = date('Y-m-d');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengunjung - Admin</title>
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
        <h1><i class="bi bi-people"></i> Data Pengunjung</h1>
        <div>
            <a href="../visitor_log.php" class="btn btn-success me-2" target="_blank">
                <i class="bi bi-plus-circle"></i> Tambah Pengunjung
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
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
                            <h4 class="mb-0"><?= $today_visitors ?></h4>
                            <small>Hari Ini</small>
                        </div>
                        <i class="bi bi-calendar-day display-6"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $active_visitors ?></h4>
                            <small>Masih Ada</small>
                        </div>
                        <i class="bi bi-person-check display-6"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $checked_out_visitors ?></h4>
                            <small>Sudah Pulang</small>
                        </div>
                        <i class="bi bi-person-x display-6"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $weekly_visitors ?></h4>
                            <small>Minggu Ini</small>
                        </div>
                        <i class="bi bi-calendar-week display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Masih Ada</option>
                        <option value="checked_out" <?= $status_filter === 'checked_out' ? 'selected' : '' ?>>Sudah Pulang</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pencarian</label>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama atau tujuan..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Visitors Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list"></i> Daftar Pengunjung</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Tujuan</th>
                            <th>Tanggal</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($visitors)): ?>
                        <tr><td colspan="9" class="text-center text-muted">Tidak ada data pengunjung.</td></tr>
                    <?php else: foreach ($visitors as $i => $v): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($v['name']) ?></strong></td>
                            <td><?= htmlspecialchars($v['purpose'] ?: '-') ?></td>
                            <td><?= date('d/m/Y', strtotime($v['visit_date'])) ?></td>
                            <td><?= $v['check_in'] ?></td>
                            <td><?= $v['check_out'] ?: '-' ?></td>
                            <td>
                                <?php
                                if ($v['check_out']) {
                                    $check_in = new DateTime($v['check_in']);
                                    $check_out = new DateTime($v['check_out']);
                                    $duration = $check_in->diff($check_out);
                                    echo $duration->format('%H:%I:%S');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($v['check_out']): ?>
                                    <span class="badge bg-success status-badge">Sudah Pulang</span>
                                <?php else: ?>
                                    <span class="badge bg-warning status-badge">Masih Ada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$v['check_out']): ?>
                                    <button class="btn btn-sm btn-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#checkoutModal<?= $v['id'] ?>">
                                        <i class="bi bi-box-arrow-right"></i> Checkout
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Sudah checkout</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Checkout Modal for each visitor -->
                        <?php if (!$v['check_out']): ?>
                        <div class="modal fade" id="checkoutModal<?= $v['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form class="modal-content" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="visitor_id" value="<?= $v['id'] ?>">
                                    <input type="hidden" name="checkout_visitor" value="1">
                                    
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-box-arrow-right"></i> Checkout Pengunjung
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <h6><?= htmlspecialchars($v['name']) ?></h6>
                                            <small class="text-muted">
                                                Tujuan: <?= htmlspecialchars($v['purpose'] ?: 'Tidak disebutkan') ?><br>
                                                Tanggal: <?= date('d/m/Y', strtotime($v['visit_date'])) ?><br>
                                                Check In: <?= $v['check_in'] ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Catatan Checkout</label>
                                            <textarea name="notes" class="form-control" rows="3" 
                                                      placeholder="Catatan tambahan..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-box-arrow-right"></i> Konfirmasi Checkout
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
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + count($visitors), $total_visitors) ?> dari <?= $total_visitors ?> pengunjung
                </div>
                <nav aria-label="Pagination pengunjung">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Previous button -->
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page - 1 ?>&date=<?= $date_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
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
                                <a class="page-link" href="?page=1&date=<?= $date_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&date=<?= $date_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&date=<?= $date_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next button -->
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $current_page + 1 ?>&date=<?= $date_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" aria-label="Next">
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
</body>
</html> 