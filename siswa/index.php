<?php
session_start();
/**
 * Student Dashboard
 * File: siswa/index.php
 * Description: Main student dashboard with academic information and grades
 */

// Include middleware system
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
require_once '../includes/visitor_logger.php';

// Apply middleware protection
requireSiswa();

// Get database connection
$pdo = require_once '../db.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('siswa/index.php');

// Get current user data
$currentUser = get_current_user_data();

// Get student data
$studentData = get_student_by_user_id($currentUser['id']);

// Get student grades
try {
    $stmt = $pdo->prepare("
        SELECT subject, assignment_score, mid_exam_score, final_exam_score, average_score, grade
        FROM grades 
        WHERE student_id = ? 
        ORDER BY subject
    ");
    $stmt->execute([$studentData['id']]);
    $grades = $stmt->fetchAll();
    
    // Calculate overall average
    $totalAverage = 0;
    $subjectCount = count($grades);
    foreach($grades as $grade) {
        $totalAverage += $grade['average_score'];
    }
    $overallAverage = $subjectCount > 0 ? round($totalAverage / $subjectCount, 2) : 0;
    
    // Get borrowing history
    $stmt = $pdo->prepare("
        SELECT b.*, bk.title as book_title, bk.author
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.user_id = ?
        ORDER BY b.borrow_date DESC
        LIMIT 5
    ");
    $stmt->execute([$currentUser['id']]);
    $borrowings = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Student dashboard error: " . $e->getMessage());
    $grades = [];
    $overallAverage = 0;
    $borrowings = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Sistem Informasi Akademik</title>
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
        
        .welcome-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15);
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
        
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #1e293b;
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
        
        .badge-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        
        .grade-score {
            font-weight: 600;
            font-size: 1.125rem;
        }
        
        .grade-score.excellent { color: #16a34a; }
        .grade-score.good { color: #3b82f6; }
        .grade-score.average { color: #d97706; }
        .grade-score.poor { color: #dc2626; }
        
        .borrowing-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .borrowing-item:last-child {
            border-bottom: none;
        }
        
        .borrowing-icon {
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
        
        .borrowing-icon.active { background: #eff6ff; color: #3b82f6; }
        .borrowing-icon.returned { background: #f0fdf4; color: #16a34a; }
        .borrowing-icon.overdue { background: #fef2f2; color: #dc2626; }
        
        .borrowing-content {
            flex: 1;
        }
        
        .borrowing-title {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .borrowing-meta {
            color: #64748b;
            font-size: 0.75rem;
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
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .student-info-grid {
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
                        <i class="bi bi-mortarboard me-2"></i>Dashboard Siswa
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
                    <p class="welcome-subtitle">Lihat informasi akademik dan riwayat peminjaman buku Anda</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex flex-column align-items-end">
                        <small class="text-white-50">NIS</small>
                        <span class="text-white"><?= htmlspecialchars($studentData['student_id'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Information -->
        <div class="content-card mb-3">
            <div class="content-header">
                <h5 class="content-title">
                    <i class="bi bi-person-badge me-2"></i>Informasi Siswa
                </h5>
            </div>
            <div class="content-body">
                <div class="student-info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">NIS</div>
                        <div class="info-value"><?= htmlspecialchars($studentData['student_id'] ?? '-') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Kelas</div>
                        <div class="info-value"><?= htmlspecialchars($studentData['class'] ?? '-') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($currentUser['email'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= count($grades) ?></h3>
                        <p class="stat-label">Total Mata Pelajaran</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= $overallAverage ?></h3>
                        <p class="stat-label">Rata-rata Nilai</p>
                    </div>
                    <div class="stat-icon success">
                        <i class="bi bi-graph-up"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number"><?= count($borrowings) ?></h3>
                        <p class="stat-label">Riwayat Peminjaman</p>
                    </div>
                    <div class="stat-icon info">
                        <i class="bi bi-journal-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <h3 class="stat-number">
                            <?php
                            $activeBorrowings = 0;
                            foreach ($borrowings as $borrowing) {
                                if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'active') {
                                    $activeBorrowings++;
                                }
                            }
                            echo $activeBorrowings;
                            ?>
                        </h3>
                        <p class="stat-label">Sedang Dipinjam</p>
                    </div>
                    <div class="stat-icon warning">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Grades Table -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-list-check me-2"></i>Nilai Akademik
                    </h5>
                </div>
                <div class="content-body">
                    <?php if (empty($grades)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-6 d-block mb-2"></i>
                            Belum ada data nilai
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mata Pelajaran</th>
                                        <th>Tugas</th>
                                        <th>UTS</th>
                                        <th>UAS</th>
                                        <th>Rata-rata</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($grade['subject']) ?></strong>
                                            </td>
                                            <td><?= $grade['assignment_score'] ?? '-' ?></td>
                                            <td><?= $grade['mid_exam_score'] ?? '-' ?></td>
                                            <td><?= $grade['final_exam_score'] ?? '-' ?></td>
                                            <td>
                                                <?php
                                                $avg = $grade['average_score'];
                                                $scoreClass = '';
                                                if ($avg >= 85) $scoreClass = 'excellent';
                                                elseif ($avg >= 75) $scoreClass = 'good';
                                                elseif ($avg >= 60) $scoreClass = 'average';
                                                else $scoreClass = 'poor';
                                                ?>
                                                <span class="grade-score <?= $scoreClass ?>"><?= $avg ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $gradeLetter = $grade['grade'];
                                                $badgeClass = '';
                                                if (in_array($gradeLetter, ['A', 'A+'])) $badgeClass = 'badge-success';
                                                elseif (in_array($gradeLetter, ['B', 'B+'])) $badgeClass = 'badge-secondary';
                                                elseif (in_array($gradeLetter, ['C', 'C+'])) $badgeClass = 'badge-warning';
                                                else $badgeClass = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= $gradeLetter ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Borrowings -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="content-title">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Peminjaman
                    </h5>
                </div>
                <div class="content-body">
                    <?php if (empty($borrowings)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-book display-6 d-block mb-2"></i>
                            Belum ada riwayat peminjaman
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($borrowings, 0, 5) as $borrowing): ?>
                            <div class="borrowing-item">
                                <?php
                                $status = $borrowing['status'];
                                $iconClass = '';
                                if ($status === 'borrowed' || $status === 'active') {
                                    $iconClass = 'active';
                                } elseif ($status === 'returned') {
                                    $iconClass = 'returned';
                                } else {
                                    $iconClass = 'overdue';
                                }
                                ?>
                                <div class="borrowing-icon <?= $iconClass ?>">
                                    <i class="bi bi-journal-check"></i>
                                </div>
                                <div class="borrowing-content">
                                    <div class="borrowing-title"><?= htmlspecialchars($borrowing['book_title']) ?></div>
                                    <div class="borrowing-meta">
                                        <?= htmlspecialchars($borrowing['author']) ?> • 
                                        <?= date('d/m/Y', strtotime($borrowing['borrow_date'])) ?>
                                        <?php if ($borrowing['return_date']): ?>
                                            • Dikembalikan: <?= date('d/m/Y', strtotime($borrowing['return_date'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($borrowings) > 5): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">Dan <?= count($borrowings) - 5 ?> peminjaman lainnya</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
