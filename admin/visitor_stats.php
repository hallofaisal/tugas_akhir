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
$logger->logVisitor('admin/visitor_stats.php');

// Get visitor statistics
function getVisitorStats($pdo) {
    $stats = [];
    
    // Daily stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_visitors,
            COUNT(DISTINCT name) as unique_visitors,
            DATE(visit_date) as visit_date
        FROM visitors 
        WHERE DATE(visit_date) = CURDATE()
        GROUP BY DATE(visit_date)
    ");
    $stmt->execute();
    $stats['daily'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_visitors' => 0, 'unique_visitors' => 0, 'visit_date' => date('Y-m-d')];
    
    // Weekly stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_visitors,
            COUNT(DISTINCT name) as unique_visitors,
            MIN(visit_date) as week_start,
            MAX(visit_date) as week_end
        FROM visitors 
        WHERE YEARWEEK(visit_date, 1) = YEARWEEK(CURDATE(), 1)
    ");
    $stmt->execute();
    $stats['weekly'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_visitors' => 0, 'unique_visitors' => 0, 'week_start' => date('Y-m-d'), 'week_end' => date('Y-m-d')];
    
    // Monthly stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_visitors,
            COUNT(DISTINCT name) as unique_visitors,
            YEAR(visit_date) as year,
            MONTH(visit_date) as month
        FROM visitors 
        WHERE YEAR(visit_date) = YEAR(CURDATE()) AND MONTH(visit_date) = MONTH(CURDATE())
        GROUP BY YEAR(visit_date), MONTH(visit_date)
    ");
    $stmt->execute();
    $stats['monthly'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_visitors' => 0, 'unique_visitors' => 0, 'year' => date('Y'), 'month' => date('m')];
    
    // Recent visitors (last 10)
    $stmt = $pdo->prepare("
        SELECT name, email, institution, purpose, visit_date, check_in_time
        FROM visitors 
        ORDER BY visit_date DESC, check_in_time DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $stats['recent_visitors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top institutions
    $stmt = $pdo->prepare("
        SELECT institution, COUNT(*) as visit_count
        FROM visitors 
        WHERE institution IS NOT NULL AND institution != ''
        GROUP BY institution 
        ORDER BY visit_count DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $stats['top_institutions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

$stats = getVisitorStats($pdo);

// Get date range for filter
$filter = $_GET['filter'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Get filtered data if date range is specified
$filtered_visitors = [];
if ($start_date && $end_date) {
    $stmt = $pdo->prepare("
        SELECT name, email, institution, purpose, visit_date, check_in_time
        FROM visitors 
        WHERE visit_date BETWEEN ? AND ?
        ORDER BY visit_date DESC, check_in_time DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $filtered_visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Pengunjung - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
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
        
        .btn-filter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }
        
        .btn-filter:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 20px;
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
                            <i class="fas fa-chart-line me-2 text-primary"></i>
                            Statistik Pengunjung
                        </h1>
                        <p class="text-muted mb-0">Dashboard statistik pengunjung perpustakaan</p>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                        <a href="../visitor_log_form.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Pengunjung
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-gradient-primary me-3">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-muted mb-1">Hari Ini</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['daily']['total_visitors']); ?></h3>
                                <small class="text-success">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo number_format($stats['daily']['unique_visitors']); ?> unik
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-gradient-success me-3">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-muted mb-1">Minggu Ini</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['weekly']['total_visitors']); ?></h3>
                                <small class="text-success">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo number_format($stats['weekly']['unique_visitors']); ?> unik
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-gradient-info me-3">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-muted mb-1">Bulan Ini</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['monthly']['total_visitors']); ?></h3>
                                <small class="text-success">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo number_format($stats['monthly']['unique_visitors']); ?> unik
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-gradient-warning me-3">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <h6 class="card-title text-muted mb-1">Institusi Teratas</h6>
                                <h3 class="mb-0"><?php echo count($stats['top_institutions']); ?></h3>
                                <small class="text-muted">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Berdasarkan kunjungan
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter me-2"></i>Filter Data
                        </h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-filter">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                    <a href="visitor_stats.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-times me-2"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-pie me-2"></i>Distribusi Kunjungan
                    </h5>
                    <canvas id="visitorChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Institusi Teratas
                    </h5>
                    <canvas id="institutionChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Visitors Table -->
        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            <?php if ($start_date && $end_date): ?>
                                Data Pengunjung (<?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>)
                            <?php else: ?>
                                Pengunjung Terbaru
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Institusi</th>
                                    <th>Tujuan</th>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $visitors_to_show = $start_date && $end_date ? $filtered_visitors : $stats['recent_visitors'];
                                if (empty($visitors_to_show)): 
                                ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-3"></i>
                                            <br>Tidak ada data pengunjung
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($visitors_to_show as $index => $visitor): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($visitor['name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($visitor['email'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($visitor['institution'] ?: '-'); ?></td>
                                            <td>
                                                <span class="text-muted">
                                                    <?php echo htmlspecialchars(mb_strimwidth($visitor['purpose'], 0, 50, '...')); ?>
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
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Visitor Distribution Chart
        const visitorCtx = document.getElementById('visitorChart').getContext('2d');
        new Chart(visitorCtx, {
            type: 'doughnut',
            data: {
                labels: ['Hari Ini', 'Minggu Ini', 'Bulan Ini'],
                datasets: [{
                    data: [
                        <?php echo $stats['daily']['total_visitors']; ?>,
                        <?php echo $stats['weekly']['total_visitors']; ?>,
                        <?php echo $stats['monthly']['total_visitors']; ?>
                    ],
                    backgroundColor: [
                        '#667eea',
                        '#28a745',
                        '#17a2b8'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Top Institutions Chart
        const institutionCtx = document.getElementById('institutionChart').getContext('2d');
        new Chart(institutionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($stats['top_institutions'], 'institution')); ?>,
                datasets: [{
                    label: 'Jumlah Kunjungan',
                    data: <?php echo json_encode(array_column($stats['top_institutions'], 'visit_count')); ?>,
                    backgroundColor: '#667eea',
                    borderColor: '#5a6fd8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

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
    </script>
</body>
</html> 
