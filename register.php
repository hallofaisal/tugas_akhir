<?php
/**
 * Student Registration Page
 * File: register.php
 * Description: Registration form for new students with validation
 */

session_start();

// Include database connection and middleware
require_once 'db.php';
require_once 'includes/visitor_logger.php';
require_once 'includes/middleware.php';
require_once 'includes/middleware_config.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('register.php');

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Initialize variables
$errors = [];
$success_message = '';
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        // Get form data
        $form_data = [
            'nis' => trim($_POST['nis'] ?? ''),
            'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'jenis_kelamin' => $_POST['jenis_kelamin'] ?? '',
            'tempat_lahir' => trim($_POST['tempat_lahir'] ?? ''),
            'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
            'alamat' => trim($_POST['alamat'] ?? ''),
            'no_telepon' => trim($_POST['no_telepon'] ?? ''),
            'nama_ortu' => trim($_POST['nama_ortu'] ?? ''),
            'no_telepon_ortu' => trim($_POST['no_telepon_ortu'] ?? '')
        ];

        // Validation
        $errors = validateRegistrationData($form_data);

        // If no errors, proceed with registration
        if (empty($errors)) {
            try {
                $pdo = getConnection();
                
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$form_data['username']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Username sudah digunakan. Silakan pilih username lain.';
                }

                // Check if NIS already exists
                $stmt = $pdo->prepare("SELECT id FROM students WHERE nis = ?");
                $stmt->execute([$form_data['nis']]);
                if ($stmt->fetch()) {
                    $errors[] = 'NIS sudah terdaftar. Silakan periksa kembali.';
                }

                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$form_data['email']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain.';
                }

                // If still no errors, create account
                if (empty($errors)) {
                    // Hash password
                    $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
                    
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Insert into users table
                        $stmt = $pdo->prepare("
                            INSERT INTO users (username, password, email, full_name, role, is_active, created_at) 
                            VALUES (?, ?, ?, ?, 'siswa', TRUE, NOW())
                        ");
                        $stmt->execute([
                            $form_data['username'],
                            $hashed_password,
                            $form_data['email'],
                            $form_data['nama_lengkap']
                        ]);
                        
                        $user_id = $pdo->lastInsertId();
                        
                        // Insert into students table
                        $stmt = $pdo->prepare("
                            INSERT INTO students (
                                user_id, nis, full_name, birth_place, birth_date, gender, 
                                address, phone, parent_name, parent_phone, status, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                        ");
                        $stmt->execute([
                            $user_id,
                            $form_data['nis'],
                            $form_data['nama_lengkap'],
                            $form_data['tempat_lahir'],
                            $form_data['tanggal_lahir'],
                            $form_data['jenis_kelamin'],
                            $form_data['alamat'],
                            $form_data['no_telepon'],
                            $form_data['nama_ortu'],
                            $form_data['no_telepon_ortu']
                        ]);
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        // Log registration
                        logUserActivity('student_registration', [
                            'user_id' => $user_id,
                            'nis' => $form_data['nis'],
                            'username' => $form_data['username'],
                            'email' => $form_data['email']
                        ]);
                        
                        $success_message = 'Registrasi berhasil! Akun Anda telah dibuat. Silakan login dengan username dan password yang telah Anda buat.';
                        $form_data = []; // Clear form data
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
            }
        }
    }
}

/**
 * Validate registration form data
 * @param array $data
 * @return array
 */
function validateRegistrationData($data) {
    $errors = [];
    
    // NIS validation
    if (empty($data['nis'])) {
        $errors[] = 'NIS harus diisi.';
    } elseif (!preg_match('/^\d{8,12}$/', $data['nis'])) {
        $errors[] = 'NIS harus berupa angka dengan 8-12 digit.';
    }
    
    // Nama lengkap validation
    if (empty($data['nama_lengkap'])) {
        $errors[] = 'Nama lengkap harus diisi.';
    } elseif (strlen($data['nama_lengkap']) < 3) {
        $errors[] = 'Nama lengkap minimal 3 karakter.';
    } elseif (strlen($data['nama_lengkap']) > 100) {
        $errors[] = 'Nama lengkap maksimal 100 karakter.';
    }
    
    // Email validation
    if (empty($data['email'])) {
        $errors[] = 'Email harus diisi.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    
    // Username validation
    if (empty($data['username'])) {
        $errors[] = 'Username harus diisi.';
    } elseif (strlen($data['username']) < 4) {
        $errors[] = 'Username minimal 4 karakter.';
    } elseif (strlen($data['username']) > 20) {
        $errors[] = 'Username maksimal 20 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors[] = 'Username hanya boleh berisi huruf, angka, dan underscore.';
    }
    
    // Password validation
    if (empty($data['password'])) {
        $errors[] = 'Password harus diisi.';
    } elseif (strlen($data['password']) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    } elseif (strlen($data['password']) > 50) {
        $errors[] = 'Password maksimal 50 karakter.';
    }
    
    // Confirm password validation
    if (empty($data['confirm_password'])) {
        $errors[] = 'Konfirmasi password harus diisi.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }
    
    // Jenis kelamin validation
    if (empty($data['jenis_kelamin'])) {
        $errors[] = 'Jenis kelamin harus dipilih.';
    } elseif (!in_array($data['jenis_kelamin'], ['L', 'P'])) {
        $errors[] = 'Jenis kelamin tidak valid.';
    }
    
    // Tempat lahir validation
    if (empty($data['tempat_lahir'])) {
        $errors[] = 'Tempat lahir harus diisi.';
    }
    
    // Tanggal lahir validation
    if (empty($data['tanggal_lahir'])) {
        $errors[] = 'Tanggal lahir harus diisi.';
    } else {
        $birth_date = new DateTime($data['tanggal_lahir']);
        $today = new DateTime();
        $age = $today->diff($birth_date)->y;
        
        if ($age < 5 || $age > 25) {
            $errors[] = 'Tanggal lahir tidak valid. Usia harus antara 5-25 tahun.';
        }
    }
    
    // Alamat validation
    if (empty($data['alamat'])) {
        $errors[] = 'Alamat harus diisi.';
    } elseif (strlen($data['alamat']) < 10) {
        $errors[] = 'Alamat minimal 10 karakter.';
    }
    
    // No telepon validation
    if (!empty($data['no_telepon'])) {
        if (!preg_match('/^(\+62|62|0)8[1-9][0-9]{6,9}$/', $data['no_telepon'])) {
            $errors[] = 'Format nomor telepon tidak valid.';
        }
    }
    
    // Nama orang tua validation
    if (empty($data['nama_ortu'])) {
        $errors[] = 'Nama orang tua harus diisi.';
    }
    
    // No telepon orang tua validation
    if (!empty($data['no_telepon_ortu'])) {
        if (!preg_match('/^(\+62|62|0)8[1-9][0-9]{6,9}$/', $data['no_telepon_ortu'])) {
            $errors[] = 'Format nomor telepon orang tua tidak valid.';
        }
    }
    
    return $errors;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Siswa - Sistem Informasi Sekolah</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--success-color), #146c43);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .register-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .register-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .register-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--success-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        
        .form-floating .form-control.is-invalid {
            border-color: var(--danger-color);
        }
        
        .form-floating .form-control.is-valid {
            border-color: var(--success-color);
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--success-color), #146c43);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1rem;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1edff, #bee5eb);
            color: #0c5460;
        }
        
        .register-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .register-footer a {
            color: var(--success-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: var(--success-color);
        }
        
        .form-section {
            background: rgba(25, 135, 84, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--success-color);
        }
        
        .form-section h5 {
            color: var(--success-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .progress-bar {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), #146c43);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .register-container {
                padding: 10px;
            }
            
            .register-card {
                border-radius: 15px;
            }
            
            .register-header {
                padding: 1.5rem;
            }
            
            .register-header h1 {
                font-size: 1.5rem;
            }
            
            .register-body {
                padding: 1.5rem;
            }
            
            .form-section {
                padding: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .register-header h1 {
                font-size: 1.3rem;
            }
            
            .register-body {
                padding: 1rem;
            }
        }
        
        /* Loading Animation */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <h1><i class="bi bi-person-plus"></i> Registrasi Siswa</h1>
                <p>Sistem Informasi Sekolah</p>
            </div>
            
            <!-- Body -->
            <div class="register-body">
                <!-- Progress Bar -->
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <!-- Success Message -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i>
                        <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Terjadi kesalahan:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Registration Form -->
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h5><i class="bi bi-person"></i> Informasi Pribadi</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" 
                                           class="form-control <?= isset($errors) && in_array('NIS harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="nis" 
                                           name="nis" 
                                           placeholder="NIS"
                                           value="<?= htmlspecialchars($form_data['nis'] ?? '') ?>"
                                           required>
                                    <label for="nis">
                                        <i class="bi bi-card-text"></i> NIS *
                                    </label>
                                    <div class="invalid-feedback">NIS harus diisi dengan 8-12 digit angka.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" 
                                           class="form-control <?= isset($errors) && in_array('Nama lengkap harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="nama_lengkap" 
                                           name="nama_lengkap" 
                                           placeholder="Nama Lengkap"
                                           value="<?= htmlspecialchars($form_data['nama_lengkap'] ?? '') ?>"
                                           required>
                                    <label for="nama_lengkap">
                                        <i class="bi bi-person-badge"></i> Nama Lengkap *
                                    </label>
                                    <div class="invalid-feedback">Nama lengkap minimal 3 karakter.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" 
                                           class="form-control <?= isset($errors) && in_array('Email harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="email" 
                                           name="email" 
                                           placeholder="Email"
                                           value="<?= htmlspecialchars($form_data['email'] ?? '') ?>"
                                           required>
                                    <label for="email">
                                        <i class="bi bi-envelope"></i> Email *
                                    </label>
                                    <div class="invalid-feedback">Format email tidak valid.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select <?= isset($errors) && in_array('Jenis kelamin harus dipilih.', $errors) ? 'is-invalid' : '' ?>" 
                                            id="jenis_kelamin" 
                                            name="jenis_kelamin" 
                                            required>
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" <?= ($form_data['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= ($form_data['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                    <label for="jenis_kelamin">
                                        <i class="bi bi-gender-ambiguous"></i> Jenis Kelamin *
                                    </label>
                                    <div class="invalid-feedback">Jenis kelamin harus dipilih.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" 
                                           class="form-control <?= isset($errors) && in_array('Tempat lahir harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="tempat_lahir" 
                                           name="tempat_lahir" 
                                           placeholder="Tempat Lahir"
                                           value="<?= htmlspecialchars($form_data['tempat_lahir'] ?? '') ?>"
                                           required>
                                    <label for="tempat_lahir">
                                        <i class="bi bi-geo-alt"></i> Tempat Lahir *
                                    </label>
                                    <div class="invalid-feedback">Tempat lahir harus diisi.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" 
                                           class="form-control <?= isset($errors) && in_array('Tanggal lahir harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="tanggal_lahir" 
                                           name="tanggal_lahir" 
                                           value="<?= htmlspecialchars($form_data['tanggal_lahir'] ?? '') ?>"
                                           required>
                                    <label for="tanggal_lahir">
                                        <i class="bi bi-calendar"></i> Tanggal Lahir *
                                    </label>
                                    <div class="invalid-feedback">Tanggal lahir harus diisi.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating">
                            <textarea class="form-control <?= isset($errors) && in_array('Alamat harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                      id="alamat" 
                                      name="alamat" 
                                      placeholder="Alamat"
                                      style="height: 100px"
                                      required><?= htmlspecialchars($form_data['alamat'] ?? '') ?></textarea>
                            <label for="alamat">
                                <i class="bi bi-house"></i> Alamat *
                            </label>
                            <div class="invalid-feedback">Alamat minimal 10 karakter.</div>
                        </div>
                        
                        <div class="form-floating">
                            <input type="tel" 
                                   class="form-control" 
                                   id="no_telepon" 
                                   name="no_telepon" 
                                   placeholder="No. Telepon"
                                   value="<?= htmlspecialchars($form_data['no_telepon'] ?? '') ?>">
                            <label for="no_telepon">
                                <i class="bi bi-telephone"></i> No. Telepon
                            </label>
                        </div>
                    </div>
                    
                    <!-- Parent Information Section -->
                    <div class="form-section">
                        <h5><i class="bi bi-people"></i> Informasi Orang Tua</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" 
                                           class="form-control <?= isset($errors) && in_array('Nama orang tua harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="nama_ortu" 
                                           name="nama_ortu" 
                                           placeholder="Nama Orang Tua"
                                           value="<?= htmlspecialchars($form_data['nama_ortu'] ?? '') ?>"
                                           required>
                                    <label for="nama_ortu">
                                        <i class="bi bi-person-heart"></i> Nama Orang Tua *
                                    </label>
                                    <div class="invalid-feedback">Nama orang tua harus diisi.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" 
                                           class="form-control" 
                                           id="no_telepon_ortu" 
                                           name="no_telepon_ortu" 
                                           placeholder="No. Telepon Orang Tua"
                                           value="<?= htmlspecialchars($form_data['no_telepon_ortu'] ?? '') ?>">
                                    <label for="no_telepon_ortu">
                                        <i class="bi bi-telephone"></i> No. Telepon Orang Tua
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Information Section -->
                    <div class="form-section">
                        <h5><i class="bi bi-shield-lock"></i> Informasi Akun</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" 
                                           class="form-control <?= isset($errors) && in_array('Username harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="username" 
                                           name="username" 
                                           placeholder="Username"
                                           value="<?= htmlspecialchars($form_data['username'] ?? '') ?>"
                                           required>
                                    <label for="username">
                                        <i class="bi bi-person-circle"></i> Username *
                                    </label>
                                    <div class="invalid-feedback">Username minimal 4 karakter, hanya huruf, angka, dan underscore.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating position-relative">
                                    <input type="password" 
                                           class="form-control <?= isset($errors) && in_array('Password harus diisi.', $errors) ? 'is-invalid' : '' ?>" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Password"
                                           required>
                                    <label for="password">
                                        <i class="bi bi-lock"></i> Password *
                                    </label>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="bi bi-eye" id="passwordIcon"></i>
                                    </button>
                                    <div class="invalid-feedback">Password minimal 6 karakter.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating position-relative">
                            <input type="password" 
                                   class="form-control <?= isset($errors) && in_array('Konfirmasi password tidak cocok.', $errors) ? 'is-invalid' : '' ?>" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Konfirmasi Password"
                                   required>
                            <label for="confirm_password">
                                <i class="bi bi-lock-fill"></i> Konfirmasi Password *
                            </label>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                            </button>
                            <div class="invalid-feedback">Konfirmasi password tidak cocok.</div>
                        </div>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="agree_terms" required>
                        <label class="form-check-label" for="agree_terms">
                            Saya setuju dengan <a href="#" class="text-decoration-none">Syarat dan Ketentuan</a> serta <a href="#" class="text-decoration-none">Kebijakan Privasi</a>
                        </label>
                    </div>
                    
                    <!-- Register Button -->
                    <button type="submit" class="btn btn-success btn-register" id="registerBtn">
                        <i class="bi bi-person-plus"></i> Daftar Sekarang
                    </button>
                </form>
            </div>
            
            <!-- Footer -->
            <div class="register-footer">
                <p>
                    <i class="bi bi-shield-check"></i> 
                    Data Anda akan dijaga kerahasiaannya
                </p>
                <p>
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </p>
                <p>
                    <a href="index.php">
                        <i class="bi bi-house"></i> Kembali ke Beranda
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId === 'password' ? 'passwordIcon' : 'confirmPasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'bi bi-eye';
            }
        }
        
        // Form validation
        function validateForm() {
            let isValid = true;
            const requiredFields = document.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
            
            // Password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                isValid = false;
            } else if (confirmPassword.value !== '') {
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
            }
            
            // Email validation
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email.value && !emailRegex.test(email.value)) {
                email.classList.add('is-invalid');
                isValid = false;
            }
            
            // NIS validation
            const nis = document.getElementById('nis');
            const nisRegex = /^\d{8,12}$/;
            if (nis.value && !nisRegex.test(nis.value)) {
                nis.classList.add('is-invalid');
                isValid = false;
            }
            
            // Username validation
            const username = document.getElementById('username');
            const usernameRegex = /^[a-zA-Z0-9_]{4,20}$/;
            if (username.value && !usernameRegex.test(username.value)) {
                username.classList.add('is-invalid');
                isValid = false;
            }
            
            return isValid;
        }
        
        // Update progress bar
        function updateProgress() {
            const requiredFields = document.querySelectorAll('[required]');
            const filledFields = Array.from(requiredFields).filter(field => field.value.trim() !== '');
            const progress = (filledFields.length / requiredFields.length) * 100;
            
            document.getElementById('progressFill').style.width = progress + '%';
        }
        
        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            const btn = document.getElementById('registerBtn');
            const originalText = btn.innerHTML;
            
            btn.classList.add('btn-loading');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
            btn.disabled = true;
            
            // Re-enable after 10 seconds if no response
            setTimeout(() => {
                btn.classList.remove('btn-loading');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 10000);
        });
        
        // Real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateForm();
                    updateProgress();
                });
                
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    updateProgress();
                });
            });
            
            // Initialize progress
            updateProgress();
        });
        
        // Auto-focus first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nis').focus();
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                document.getElementById('registerForm').submit();
            }
        });
    </script>
</body>
</html> 