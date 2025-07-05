<?php
/**
 * Visitor Log Form
 * File: visitor_log.php
 * Description: Simple form for visitors to log their visit with automatic timestamp
 */

require_once 'db.php';

$success = $error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validation
    if (empty($name)) {
        $error = 'Nama harus diisi.';
    } elseif (strlen($name) < 2) {
        $error = 'Nama minimal 2 karakter.';
    } elseif (strlen($name) > 100) {
        $error = 'Nama maksimal 100 karakter.';
    } else {
        try {
            $pdo = getConnection();
            
            // Insert visitor record
            $stmt = $pdo->prepare("
                INSERT INTO visitors (name, purpose, visit_date, check_in, notes) 
                VALUES (?, ?, CURDATE(), CURTIME(), ?)
            ");
            
            $notes = !empty($purpose) ? "Tujuan: $purpose" : '';
            $stmt->execute([$name, $purpose, $notes]);
            
            $success = 'Terima kasih! Kunjungan Anda telah dicatat.';
            
            // Clear form data after successful submission
            $name = $purpose = '';
            
        } catch (PDOException $e) {
            $error = 'Gagal mencatat kunjungan: ' . $e->getMessage();
        }
    }
}

// Get today's visitors count
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visit_date = CURDATE()");
    $stmt->execute();
    $today_visitors = $stmt->fetchColumn();
} catch (PDOException $e) {
    $today_visitors = 0;
}

// CSRF token
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Pengunjung - Sistem Informasi Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .visitor-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .visitor-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .visitor-body {
            padding: 40px;
        }
        .form-floating {
            margin-bottom: 20px;
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .stats-card {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .time-display {
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
        }
        .back-link {
            color: white;
            text-decoration: none;
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
            backdrop-filter: blur(10px);
        }
        .back-link:hover {
            color: white;
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="visitor-card">
                    <div class="visitor-header">
                        <h2><i class="bi bi-person-check"></i></h2>
                        <h3>Log Pengunjung</h3>
                        <p class="mb-0">Silakan isi data kunjungan Anda</p>
                    </div>
                    
                    <div class="visitor-body">
                        <!-- Today's Stats -->
                        <div class="stats-card">
                            <div class="time-display" id="current-time"></div>
                            <small class="text-muted">
                                Pengunjung hari ini: <strong><?= $today_visitors ?></strong>
                            </small>
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

                        <form method="post" id="visitorForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Masukkan nama lengkap"
                                       value="<?= htmlspecialchars($name ?? '') ?>"
                                       required 
                                       maxlength="100">
                                <label for="name">
                                    <i class="bi bi-person"></i> Nama Lengkap
                                </label>
                            </div>

                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control" 
                                       id="purpose" 
                                       name="purpose" 
                                       placeholder="Tujuan kunjungan (opsional)"
                                       value="<?= htmlspecialchars($purpose ?? '') ?>"
                                       maxlength="200">
                                <label for="purpose">
                                    <i class="bi bi-info-circle"></i> Tujuan Kunjungan (Opsional)
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-submit">
                                    <i class="bi bi-check-circle"></i> Catat Kunjungan
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Waktu kunjungan akan dicatat otomatis
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            document.getElementById('current-time').innerHTML = 
                `<i class="bi bi-clock"></i> ${timeString}<br><small>${dateString}</small>`;
        }

        // Update time every second
        updateTime();
        setInterval(updateTime, 1000);

        // Form validation
        document.getElementById('visitorForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            
            if (name.length < 2) {
                e.preventDefault();
                alert('Nama minimal 2 karakter.');
                document.getElementById('name').focus();
                return false;
            }
            
            if (name.length > 100) {
                e.preventDefault();
                alert('Nama maksimal 100 karakter.');
                document.getElementById('name').focus();
                return false;
            }
        });

        // Auto-focus on name field
        document.getElementById('name').focus();
    </script>
</body>
</html> 