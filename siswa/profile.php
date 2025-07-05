<?php
session_start();
/**
 * Student Profile Page
 * File: siswa/profile.php
 * Description: Student profile management
 */

// Include middleware system
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';

// Apply middleware protection
requireSiswa();

// Data profil dari session/sample
$currentUser = $_SESSION;
$fullName = $currentUser['full_name'] ?? 'Siswa Demo';
$username = $currentUser['username'] ?? 'siswa';
$email = $currentUser['email'] ?? 'siswa@demo.com';
$nis = '2024001';
$kelas = 'X-A';
$phone = '08123456789';
$address = 'Jl. Demo No. 1, Jakarta';
$status = 'Aktif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background: #f8fafc; color: #334155; }
        .page-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 1.5rem 0; margin-bottom: 2rem; }
        .page-title { font-size: 1.875rem; font-weight: 600; color: #1e293b; margin: 0; }
        .profile-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2rem 2.5rem;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 16px rgba(59,130,246,0.06);
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #eff6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            color: #3b82f6;
        }
        .profile-name { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.25rem; color: #1e293b; }
        .profile-username { color: #64748b; font-size: 1rem; margin-bottom: 1.5rem; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem 2rem;
            margin-bottom: 1.5rem;
        }
        .info-label { color: #64748b; font-size: 0.95rem; margin-bottom: 0.25rem; }
        .info-value { font-weight: 500; color: #1e293b; margin-bottom: 0.5rem; }
        .badge-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-size: 0.9em; }
        .btn-success { background: #10b981; border-color: #10b981; }
        .btn-success:hover { background: #059669; border-color: #059669; }
        .btn-secondary { border-radius: 8px; }
        @media (max-width: 600px) {
            .profile-card { padding: 1.2rem 0.5rem; }
            .info-grid { grid-template-columns: 1fr; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title"><i class="bi bi-person me-2"></i>Profil Siswa</h1>
                </div>
                <div>
                    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="profile-card">
            <div class="profile-avatar mb-2">
                <i class="bi bi-person"></i>
            </div>
            <div class="text-center">
                <div class="profile-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="profile-username">@<?= htmlspecialchars($username) ?></div>
            </div>
            <div class="info-grid">
                <div>
                    <div class="info-label">NIS</div>
                    <div class="info-value"><?= htmlspecialchars($nis) ?></div>
                </div>
                <div>
                    <div class="info-label">Kelas</div>
                    <div class="info-value"><?= htmlspecialchars($kelas) ?></div>
                </div>
                <div>
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($email) ?></div>
                </div>
                <div>
                    <div class="info-label">No. HP</div>
                    <div class="info-value"><?= htmlspecialchars($phone) ?></div>
                </div>
                <div style="grid-column:1/3">
                    <div class="info-label">Alamat</div>
                    <div class="info-value"><?= htmlspecialchars($address) ?></div>
                </div>
                <div>
                    <div class="info-label">Status</div>
                    <div class="info-value"><span class="badge badge-success">Aktif</span></div>
                </div>
            </div>
            <div class="mt-4 text-center">
                <a href="#" class="btn btn-success disabled"><i class="bi bi-pencil me-1"></i>Edit Profil</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 