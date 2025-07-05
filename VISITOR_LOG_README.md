# Fitur Log Pengunjung - Sistem Informasi Akademik

## Deskripsi
Fitur log pengunjung memungkinkan pencatatan dan pelacakan kunjungan ke perpustakaan dengan berbagai metode, termasuk form manual dan pencatatan otomatis.

## Fitur Utama

### 1. Form Log Pengunjung Manual (`visitor_log_form.php`)
- **Input Nama**: Pengunjung mengisi nama lengkap (wajib)
- **Input Email**: Email pengunjung (opsional)
- **Input Telepon**: Nomor telepon (opsional)
- **Input Institusi**: Institusi/organisasi pengunjung (opsional)
- **Input Tujuan**: Tujuan kunjungan (wajib)
- **Waktu Otomatis**: Tanggal dan waktu kunjungan dicatat otomatis
- **Validasi**: Validasi input untuk memastikan data lengkap
- **Feedback**: Pesan sukses setelah berhasil mencatat

### 2. Pencatatan Otomatis (`includes/visitor_logger.php`)
- **Deteksi Pengunjung**: Mencatat kunjungan secara otomatis
- **Filter Bot**: Tidak mencatat bot/crawler
- **Deteksi IP**: Mencatat IP address pengunjung
- **User Agent**: Mencatat browser dan sistem operasi
- **Deduplikasi**: Menghindari pencatatan ganda untuk IP yang sama
- **Session Aware**: Membedakan pengunjung login dan anonim

### 3. Statistik Pengunjung (`admin/visitor_stats.php`)
- **Dashboard Statistik**: Tampilan visual statistik kunjungan
- **Periode Waktu**: Harian, mingguan, dan bulanan
- **Grafik Interaktif**: Chart.js untuk visualisasi data
- **Filter Tanggal**: Filter berdasarkan rentang tanggal
- **Pengunjung Terbaru**: Daftar 10 pengunjung terbaru
- **Institusi Teratas**: Ranking institusi berdasarkan kunjungan

### 4. Laporan Detail (`admin/visitor_report.php`)
- **Laporan Lengkap**: Data lengkap semua pengunjung
- **Filter Lanjutan**: Filter berdasarkan tanggal, institusi, tujuan
- **Pagination**: Navigasi halaman untuk data besar
- **Export CSV**: Export data ke format CSV
- **Statistik Ringkasan**: Ringkasan statistik periode tertentu
- **Institusi Teratas**: Daftar institusi dengan kunjungan terbanyak

## Struktur Database

### Tabel `visitors`
```sql
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    institution VARCHAR(100),
    purpose VARCHAR(200),
    visit_date DATE NOT NULL,
    check_in_time TIME NOT NULL,
    check_out_time TIME NULL,
    duration_minutes INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Cara Penggunaan

### 1. Form Manual
1. Akses `visitor_log_form.php`
2. Isi form dengan data pengunjung
3. Klik "Catat Kunjungan"
4. Data akan tersimpan di database

### 2. Pencatatan Otomatis
1. Include `visitor_logger.php` di halaman yang ingin dicatat
2. Buat instance `VisitorLogger`
3. Panggil method `logVisitor()`
4. Kunjungan akan dicatat otomatis

### 3. Melihat Statistik (Admin)
1. Login sebagai admin
2. Akses `admin/visitor_stats.php`
3. Lihat statistik harian, mingguan, bulanan
4. Gunakan filter untuk data spesifik

### 4. Laporan Detail (Admin)
1. Login sebagai admin
2. Akses `admin/visitor_report.php`
3. Gunakan filter untuk data tertentu
4. Export ke CSV jika diperlukan

## Integrasi dengan Sistem

### 1. Halaman Utama
- Form log pengunjung terintegrasi di halaman utama
- Link menuju form log pengunjung

### 2. Dashboard Admin
- Statistik pengunjung hari ini
- Link menuju halaman statistik dan laporan
- Menu cepat untuk akses fitur pengunjung

### 3. Session Management
- Pencatatan otomatis saat user login
- Membedakan pengunjung login dan anonim
- Integrasi dengan sistem session

## Konfigurasi

### 1. Visitor Logger
```php
// Di includes/visitor_logger.php
private $logAnonymous = true; // Set false untuk hanya log user login
```

### 2. Database Connection
```php
// Pastikan db.php sudah dikonfigurasi dengan benar
require_once 'db.php';
```

### 3. Session Management
```php
// Pastikan session sudah dimulai
session_start();
```

## Keamanan

### 1. Validasi Input
- Validasi nama wajib diisi
- Validasi tujuan kunjungan wajib diisi
- Sanitasi input untuk mencegah XSS

### 2. Akses Kontrol
- Hanya admin yang bisa melihat statistik dan laporan
- Middleware protection untuk halaman admin

### 3. Filter Data
- Filter bot/crawler
- Filter IP lokal/internal
- Deduplikasi berdasarkan IP

## Monitoring dan Maintenance

### 1. Log Error
- Error pencatatan tidak mengganggu aplikasi utama
- Log error tersimpan di error log server

### 2. Performance
- Query database dioptimasi dengan index
- Pagination untuk data besar
- Caching statistik jika diperlukan

### 3. Backup
- Data pengunjung termasuk dalam backup database
- Export CSV untuk backup manual

## Troubleshooting

### 1. Data Tidak Tercatat
- Periksa koneksi database
- Periksa permission tabel visitors
- Periksa error log

### 2. Statistik Tidak Akurat
- Periksa timezone server
- Periksa format tanggal di database
- Periksa query statistik

### 3. Export CSV Error
- Periksa permission folder
- Periksa memory limit PHP
- Periksa encoding UTF-8

## Pengembangan Selanjutnya

### 1. Fitur Tambahan
- QR Code untuk check-in cepat
- Notifikasi email untuk admin
- Dashboard real-time
- Analisis tren kunjungan

### 2. Integrasi
- Integrasi dengan sistem perpustakaan
- API untuk aplikasi mobile
- Integrasi dengan sistem keamanan

### 3. Optimasi
- Caching statistik
- Background job untuk pencatatan
- Database optimization

## File yang Terlibat

1. `visitor_log_form.php` - Form input pengunjung
2. `includes/visitor_logger.php` - Class untuk pencatatan otomatis
3. `admin/visitor_stats.php` - Halaman statistik pengunjung
4. `admin/visitor_report.php` - Halaman laporan detail
5. `index.php` - Integrasi di halaman utama
6. `admin/index.php` - Dashboard admin dengan statistik
7. `database.sql` - Struktur tabel visitors

## Dependencies

- PHP 7.4+
- MySQL/MariaDB
- Bootstrap 5.3.0
- Chart.js
- Font Awesome 6.0.0 