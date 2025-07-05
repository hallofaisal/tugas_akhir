# Sistem Akademik - Dashboard Guide

## Overview
Sistem Akademik adalah aplikasi web untuk mengelola informasi akademik sekolah dengan fitur perpustakaan dan manajemen pengunjung. Aplikasi ini mendukung multiple role user dengan dashboard yang berbeda untuk setiap role.

## Role dan Dashboard

### 1. Admin/Teacher Dashboard (`/admin/`)
**Username Admin:** admin | **Password:** admin123
**Username Teacher:** teacher1 | **Password:** password123

**Fitur:**
- Statistik lengkap sistem
- Manajemen user (siswa, guru)
- Manajemen buku perpustakaan
- Laporan peminjaman dan pengunjung
- Export data ke PDF
- Pengaturan sistem
- Monitoring aktivitas siswa (untuk guru)

**Menu:**
- Dashboard utama dengan grafik
- Kelola User
- Kelola Buku
- Peminjaman
- Laporan Pengunjung
- Laporan Peminjaman
- Pengaturan

### 2. Student Dashboard (`/siswa/`)
**Username:** student1 | **Password:** password123

**Fitur:**
- Melihat buku yang tersedia
- Melakukan peminjaman buku
- Melihat riwayat peminjaman
- Melihat buku yang sedang dipinjam
- Profil siswa

**Menu:**
- Dashboard siswa
- Katalog Buku
- Peminjaman Saya
- Riwayat Peminjaman
- Profil

## Fitur Utama

### 1. Visitor Logging System
- Form manual untuk mencatat pengunjung
- Logging otomatis saat mengakses halaman tertentu
- Laporan statistik pengunjung
- Export data pengunjung

### 2. Library Management
- Katalog buku dengan kategori
- Sistem peminjaman dan pengembalian
- Tracking buku yang dipinjam
- Notifikasi buku terlambat

### 3. User Management
- Multi-role user system (Admin, Teacher, Student)
- Role-based access control
- Session management
- Password security

### 4. Reporting System
- Laporan peminjaman dengan filter
- Laporan pengunjung
- Export ke PDF
- Statistik real-time

## Database System

### Fallback System
Aplikasi menggunakan sistem fallback database:
1. **MySQL** (prioritas utama)
2. **SQLite** (fallback jika MySQL tidak tersedia)
3. **JSON Files** (fallback jika PDO tidak tersedia)

### Tables
- `users` - Data pengguna
- `students` - Data siswa
- `books` - Data buku
- `borrowings` - Data peminjaman
- `visitors` - Data pengunjung

## Security Features

### 1. Authentication
- Password hashing dengan `password_hash()`
- Session management
- Role-based access control

### 2. CSRF Protection
- CSRF token untuk form submission
- Token validation

### 3. Input Validation
- Sanitasi input
- Prepared statements untuk SQL
- XSS protection

## Installation & Setup

### Requirements
- PHP 7.4 atau lebih tinggi
- Web server (Apache/Nginx) atau PHP built-in server
- Browser modern

### Quick Start
1. Clone atau download project
2. Jalankan PHP server:
   ```bash
   php -S localhost:8000
   ```
3. Buka browser: `http://localhost:8000`
4. Login dengan akun demo yang tersedia

### Database Setup
Aplikasi akan otomatis membuat database dan tabel saat pertama kali diakses. Tidak perlu setup manual.

## File Structure

```
tugas_akhir/
├── admin/                 # Admin/Teacher dashboard
├── siswa/                 # Student dashboard  
├── assets/                # CSS, JS, images
├── includes/              # Helper functions
├── database/              # Database files
├── db.php                 # Database connection
├── login.php              # Login page
├── index.php              # Home page
└── README.md              # Documentation
```

## Testing

### Test File
Gunakan `test_dashboards.php` untuk memverifikasi:
- Koneksi database
- User authentication
- Dashboard access
- Role-based redirects

### Demo Accounts
- **Admin:** admin / admin123
- **Teacher:** teacher1 / password123
- **Student:** student1 / password123

## Troubleshooting

### Common Issues

1. **"Cannot redeclare function" error**
   - Pastikan tidak ada duplikasi fungsi di `db.php`
   - Restart PHP server

2. **Database connection failed**
   - Aplikasi akan otomatis menggunakan JSON fallback
   - Check error logs untuk detail

3. **Login stuck**
   - Clear browser cookies
   - Check session configuration
   - Verify database connection

### Error Logs
Check PHP error logs untuk debugging:
- Windows: Check PHP error log location
- Linux: `/var/log/apache2/error.log` atau `/var/log/nginx/error.log`

## Development

### Adding New Features
1. Create new PHP files in appropriate directory
2. Update navigation menus
3. Add database tables if needed
4. Update role permissions

### Customization
- Modify CSS in `assets/css/style.css`
- Update dashboard layouts
- Add new statistics or reports

## Support

Untuk bantuan atau pertanyaan:
1. Check error logs
2. Verify database connection
3. Test dengan akun demo
4. Review file permissions

---

**Sistem Akademik v1.0** - Academic Information System with Library Management 