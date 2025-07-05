<?php
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .dashboard-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .dashboard-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .student-info {
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item label {
            font-weight: 600;
            color: #666;
        }
        
        .info-item span {
            color: #333;
            font-weight: 500;
        }
        
        .academic-summary {
            margin-bottom: 30px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
        }
        
        .summary-card h4 {
            color: #666;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .summary-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin: 0 0 10px 0;
        }
        
        .summary-label {
            color: #999;
            font-size: 14px;
        }
        
        .recent-grades {
            margin-bottom: 30px;
        }
        
        .grades-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .grades-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .grades-table th,
        .grades-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .grades-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .grades-table tr:hover {
            background: #f8f9fa;
        }
        
        .borrowings-section {
            margin-bottom: 30px;
        }
        
        .borrowings-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .borrowing-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .borrowing-item:last-child {
            border-bottom: none;
        }
        
        .borrowing-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .borrowing-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .borrowing-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-borrowed {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-returned {
            background: #e8f5e8;
            color: #388e3c;
        }
        
        .status-overdue {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body class="logged-in" data-user-id="<?php echo $currentUser['id']; ?>" data-user-role="<?php echo $currentUser['role']; ?>">
    <header>
        <nav>
            <div class="container">
                <h1>👨‍🎓 Dashboard Siswa</h1>
                <ul>
                    <li><a href="index.php">📊 Dashboard</a></li>
                    <li><a href="grades.php">📝 Nilai Saya</a></li>
                    <li><a href="borrowings.php">📚 Peminjaman</a></li>
                    <li><a href="profile.php">👤 Profil</a></li>
                    <li><a href="../logout_confirm.php">🚪 Logout</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-header">
                <h2>🎉 Selamat Datang, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h2>
                <p>Dashboard siswa sistem informasi akademik</p>
            </div>

            <div class="student-info">
                <div class="info-card">
                    <h3>📋 Informasi Siswa</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>NIS:</label>
                            <span><?php echo htmlspecialchars($studentData['nis'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Nama Lengkap:</label>
                            <span><?php echo htmlspecialchars($studentData['full_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Kelas:</label>
                            <span><?php echo htmlspecialchars($studentData['class'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Tahun Akademik:</label>
                            <span><?php echo htmlspecialchars($studentData['academic_year'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Status:</label>
                            <span><?php echo htmlspecialchars(ucfirst($studentData['status'] ?? 'N/A')); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Tanggal Masuk:</label>
                            <span><?php echo $studentData['enrollment_date'] ? date('d/m/Y', strtotime($studentData['enrollment_date'])) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="academic-summary">
                <h3>📈 Ringkasan Akademik</h3>
                <div class="summary-grid">
                    <div class="summary-card">
                        <h4>📊 Rata-rata Nilai</h4>
                        <p class="summary-number"><?php echo $overallAverage; ?></p>
                        <span class="summary-label">
                            <?php 
                            if($overallAverage >= 90) echo 'Sangat Baik (A+)';
                            elseif($overallAverage >= 85) echo 'Sangat Baik (A)';
                            elseif($overallAverage >= 80) echo 'Baik (B+)';
                            elseif($overallAverage >= 75) echo 'Baik (B)';
                            elseif($overallAverage >= 70) echo 'Cukup (C+)';
                            else echo 'Perlu Perbaikan';
                            ?>
                        </span>
                    </div>
                    <div class="summary-card">
                        <h4>📚 Mata Pelajaran</h4>
                        <p class="summary-number"><?php echo count($grades); ?></p>
                        <span class="summary-label">Aktif</span>
                    </div>
                    <div class="summary-card">
                        <h4>📅 Semester</h4>
                        <p class="summary-number">1</p>
                        <span class="summary-label">Ganjil 2024/2025</span>
                    </div>
                    <div class="summary-card">
                        <h4>📖 Peminjaman Aktif</h4>
                        <p class="summary-number"><?php echo count(array_filter($borrowings, function($b) { return $b['status'] == 'borrowed'; })); ?></p>
                        <span class="summary-label">Buku dipinjam</span>
                    </div>
                </div>
            </div>

            <div class="recent-grades">
                <h3>📝 Nilai Akademik</h3>
                <div class="grades-table">
                    <?php if(empty($grades)): ?>
                        <div class="empty-state">
                            <p>Belum ada data nilai tersedia</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Mata Pelajaran</th>
                                    <th>Nilai Tugas</th>
                                    <th>Nilai UTS</th>
                                    <th>Nilai UAS</th>
                                    <th>Rata-rata</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($grades as $grade): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['subject']); ?></td>
                                    <td><?php echo $grade['assignment_score']; ?></td>
                                    <td><?php echo $grade['mid_exam_score']; ?></td>
                                    <td><?php echo $grade['final_exam_score']; ?></td>
                                    <td><?php echo $grade['average_score']; ?></td>
                                    <td><strong><?php echo $grade['grade']; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="borrowings-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>📚 Riwayat Peminjaman</h3>
                    <a href="borrowings.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Pinjam Buku Baru
                    </a>
                </div>
                <div class="borrowings-list">
                    <?php if(empty($borrowings)): ?>
                        <div class="empty-state">
                            <p>Belum ada riwayat peminjaman</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($borrowings as $borrowing): ?>
                        <div class="borrowing-item">
                            <div class="borrowing-info">
                                <h4><?php echo htmlspecialchars($borrowing['book_title']); ?></h4>
                                <p>Oleh: <?php echo htmlspecialchars($borrowing['author']); ?> | 
                                   Dipinjam: <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?> | 
                                   Jatuh tempo: <?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?></p>
                            </div>
                            <span class="borrowing-status status-<?php echo $borrowing['status']; ?>">
                                <?php 
                                switch($borrowing['status']) {
                                    case 'borrowed': echo 'Dipinjam'; break;
                                    case 'returned': echo 'Dikembalikan'; break;
                                    case 'overdue': echo 'Terlambat'; break;
                                    default: echo ucfirst($borrowing['status']);
                                }
                                ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Sistem Informasi Akademik. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/session.js"></script>
</body>
</html> 