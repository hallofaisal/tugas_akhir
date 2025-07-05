<?php
session_start();
require_once '../db.php';
require_once '../includes/visitor_logger.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('admin/visitor_report.php');

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=visitor_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    fputcsv($output, ['No', 'Nama', 'Email', 'Telepon', 'Institusi', 'Tujuan', 'Tanggal', 'Waktu Masuk', 'Waktu Keluar', 'Durasi (Menit)', 'Catatan']);
    
    // Get filtered data
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT name, email, phone, institution, purpose, visit_date, check_in_time, check_out_time, duration_minutes, notes
        FROM visitors 
        WHERE visit_date BETWEEN ? AND ?
        ORDER BY visit_date DESC, check_in_time DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    
    $no = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $no++,
            $row['name'],
            $row['email'],
            $row['phone'],
            $row['institution'],
            $row['purpose'],
            $row['visit_date'],
            $row['check_in_time'],
            $row['check_out_time'],
            $row['duration_minutes'],
            $row['notes']
        ]);
    }
    
    fclose($output);
    exit();
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$institution = $_GET['institution'] ?? '';
$purpose = $_GET['purpose'] ?? '';
$page = $_GET['page'] ?? 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["visit_date BETWEEN ? AND ?"];
$params = [$start_date, $end_date];

if ($institution) {
    $where_conditions[] = "institution LIKE ?";
    $params[] = "%$institution%";
}

if ($purpose) {
    $where_conditions[] = "purpose LIKE ?";
    $params[] = "%$purpose%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE $where_clause");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get visitors data
$stmt = $pdo->prepare("
    SELECT name, email, phone, institution, purpose, visit_date, check_in_time, check_out_time, duration_minutes, notes
    FROM visitors 
    WHERE $where_clause
    ORDER BY visit_date DESC, check_in_time DESC
    LIMIT ? OFFSET ?
");

$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$summary_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_visitors,
        COUNT(DISTINCT name) as unique_visitors,
        COUNT(DISTINCT institution) as total_institutions,
        AVG(duration_minutes) as avg_duration
    FROM visitors 
    WHERE $where_clause
");
$summary_stmt->execute(array_slice($params, 0, -2));
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

// Get top institutions
$top_institutions_stmt = $pdo->prepare("
    SELECT institution, COUNT(*) as visit_count
    FROM visitors 
    WHERE $where_clause AND institution IS NOT NULL AND institution != ''
    GROUP BY institution 
    ORDER BY visit_count DESC 
    LIMIT 5
");
$top_institutions_stmt->execute(array_slice($params, 0, -2));
$top_institutions = $top_institutions_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengunjung - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .report-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .table th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }
        
        .table td {
            border: none;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }
        
        .btn-export:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            color: white;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .filter-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fas fa-file-alt me-2 text-primary"></i>
                            Laporan Pengunjung
                        </h1>
                        <p class="text-muted mb-0">Laporan detail kunjungan perpustakaan</p>
                    </div>
                    <div>
                        <a href="visitor_stats.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-chart-line me-2"></i>Statistik
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="summary-card text-center">
                    <div class="stats-number"><?php echo number_format($summary['total_visitors']); ?></div>
                    <div class="stats-label">Total Kunjungan</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="summary-card text-center">
                    <div class="stats-number"><?php echo number_format($summary['unique_visitors']); ?></div>
                    <div class="stats-label">Pengunjung Unik</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="summary-card text-center">
                    <div class="stats-number"><?php echo number_format($summary['total_institutions']); ?></div>
                    <div class="stats-label">Institusi</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="summary-card text-center">
                    <div class="stats-number"><?php echo round($summary['avg_duration'] ?? 0); ?></div>
                    <div class="stats-label">Rata-rata Durasi (Menit)</div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="filter-form">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="institution" class="form-label">Institusi</label>
                    <input type="text" class="form-control" id="institution" name="institution" value="<?php echo htmlspecialchars($institution); ?>" placeholder="Cari institusi...">
                </div>
                <div class="col-md-3">
                    <label for="purpose" class="form-label">Tujuan</label>
                    <input type="text" class="form-control" id="purpose" name="purpose" value="<?php echo htmlspecialchars($purpose); ?>" placeholder="Cari tujuan...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Export Button -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-export">
                    <i class="fas fa-download me-2"></i>Export ke CSV
                </a>
                <span class="text-muted ms-2">
                    Menampilkan <?php echo number_format($total_records); ?> data
                </span>
            </div>
        </div>

        <!-- Top Institutions -->
        <?php if (!empty($top_institutions)): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="report-container">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2"></i>Institusi Teratas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Institusi</th>
                                        <th>Jumlah Kunjungan</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_institutions as $inst): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inst['institution']); ?></td>
                                        <td><?php echo number_format($inst['visit_count']); ?></td>
                                        <td>
                                            <?php 
                                            $percentage = ($inst['visit_count'] / $summary['total_visitors']) * 100;
                                            echo number_format($percentage, 1) . '%';
                                            ?>
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
        <?php endif; ?>

        <!-- Visitors Table -->
        <div class="report-container">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Data Pengunjung
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Institusi</th>
                            <th>Tujuan</th>
                            <th>Tanggal</th>
                            <th>Waktu Masuk</th>
                            <th>Waktu Keluar</th>
                            <th>Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitors)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-3"></i>
                                    <br>Tidak ada data pengunjung untuk periode yang dipilih
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visitors as $index => $visitor): ?>
                                <tr>
                                    <td><?php echo $offset + $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($visitor['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($visitor['email'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['phone'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($visitor['institution'] ?: '-'); ?></td>
                                    <td>
                                        <span class="text-muted" title="<?php echo htmlspecialchars($visitor['purpose']); ?>">
                                            <?php echo htmlspecialchars(mb_strimwidth($visitor['purpose'], 0, 30, '...')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo date('d/m/Y', strtotime($visitor['visit_date'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?php echo date('H:i', strtotime($visitor['check_in_time'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?php echo $visitor['check_out_time'] ? date('H:i', strtotime($visitor['check_out_time'])) : '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($visitor['duration_minutes']): ?>
                                            <span class="badge bg-info">
                                                <?php echo $visitor['duration_minutes']; ?> menit
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="row">
            <div class="col-12">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when dates change
        document.getElementById('start_date').addEventListener('change', function() {
            if (this.value && document.getElementById('end_date').value) {
                this.form.submit();
            }
        });

        document.getElementById('end_date').addEventListener('change', function() {
            if (this.value && document.getElementById('start_date').value) {
                this.form.submit();
            }
        });

        // Confirm export
        document.querySelector('a[href*="export=csv"]').addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin mengexport data ke CSV?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html> 
