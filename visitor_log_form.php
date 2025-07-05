<?php
require_once 'db.php';
require_once 'includes/visitor_logger.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('visitor_log_form.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Nama harus diisi";
    }
    
    if (empty($purpose)) {
        $errors[] = "Tujuan kunjungan harus diisi";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $current_date = date('Y-m-d');
            $current_time = date('H:i:s');
            
            $stmt = $pdo->prepare("
                INSERT INTO visitors (name, email, phone, institution, purpose, visit_date, check_in_time, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name,
                $email,
                $phone,
                $institution,
                $purpose,
                $current_date,
                $current_time,
                'Log pengunjung otomatis'
            ]);
            
            $success_message = "Data pengunjung berhasil dicatat!";
            
            // Clear form data after successful submission
            $name = $email = $phone = $institution = $purpose = '';
            
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Pengunjung - Sistem Informasi Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .visitor-form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 30px;
            text-align: center;
        }
        
        .form-body {
            padding: 40px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .current-time {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .required {
            color: #dc3545;
        }
        
        .success-animation {
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="visitor-form-container">
                    <!-- Header -->
                    <div class="form-header">
                        <h2><i class="fas fa-user-clock me-2"></i>Log Pengunjung</h2>
                        <p class="mb-0">Sistem Informasi Akademik</p>
                    </div>
                    
                    <!-- Form Body -->
                    <div class="form-body">
                        <!-- Current Time Display -->
                        <div class="current-time">
                            <i class="fas fa-clock me-2"></i>
                            <span id="current-time">Loading...</span>
                        </div>
                        
                        <!-- Success Message -->
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success success-animation" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Visitor Form -->
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">
                                        Nama Lengkap <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                           placeholder="Masukkan nama lengkap"
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                           placeholder="contoh@email.com">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Nomor Telepon</label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                           placeholder="081234567890">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="institution" class="form-label">Institusi/Organisasi</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="institution" 
                                           name="institution" 
                                           value="<?php echo htmlspecialchars($institution ?? ''); ?>"
                                           placeholder="Nama institusi">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="purpose" class="form-label">
                                    Tujuan Kunjungan <span class="required">*</span>
                                </label>
                                <textarea class="form-control" 
                                          id="purpose" 
                                          name="purpose" 
                                          rows="3" 
                                          placeholder="Jelaskan tujuan kunjungan Anda"
                                          required><?php echo htmlspecialchars($purpose ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Catat Kunjungan
                                </button>
                            </div>
                        </form>
                        
                        <!-- Back to Home Link -->
                        <div class="text-center mt-4">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
                            </a>
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
            const timeString = now.toLocaleString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        // Update time every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Auto-focus on name field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('name').focus();
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const purpose = document.getElementById('purpose').value.trim();
            
            if (!name) {
                e.preventDefault();
                alert('Nama harus diisi!');
                document.getElementById('name').focus();
                return false;
            }
            
            if (!purpose) {
                e.preventDefault();
                alert('Tujuan kunjungan harus diisi!');
                document.getElementById('purpose').focus();
                return false;
            }
        });
    </script>
</body>
</html> 
