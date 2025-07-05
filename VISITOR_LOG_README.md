# Sistem Log Pengunjung

## Deskripsi
Sistem log pengunjung untuk mencatat kunjungan perpustakaan dengan input nama dan waktu otomatis.

## Fitur Utama

### 1. Form Log Pengunjung (`visitor_log.php`)
- **Input Nama**: Form sederhana untuk pengunjung mengisi nama lengkap
- **Input Tujuan**: Field opsional untuk mencatat tujuan kunjungan
- **Waktu Otomatis**: Tanggal dan waktu check-in dicatat otomatis
- **Validasi**: Validasi nama (min 2 karakter, max 100 karakter)
- **UI Modern**: Desain responsif dengan Bootstrap 5
- **Real-time Clock**: Menampilkan waktu saat ini yang update setiap detik
- **Statistik**: Menampilkan jumlah pengunjung hari ini

### 2. Halaman Admin (`admin/visitors.php`)
- **Dashboard Statistik**:
  - Pengunjung hari ini
  - Pengunjung yang masih ada (belum checkout)
  - Pengunjung yang sudah pulang
  - Total pengunjung minggu ini
- **Filter dan Pencarian**:
  - Filter berdasarkan tanggal
  - Filter berdasarkan status (masih ada/sudah pulang)
  - Pencarian berdasarkan nama atau tujuan
- **Tabel Pengunjung**:
  - Daftar lengkap pengunjung dengan pagination
  - Informasi check-in dan check-out
  - Perhitungan durasi kunjungan
  - Status badge (masih ada/sudah pulang)
- **Fitur Checkout**:
  - Tombol checkout untuk pengunjung yang masih ada
  - Modal konfirmasi checkout
  - Catatan tambahan saat checkout
  - Update waktu check-out otomatis

## Struktur Database

### Tabel `visitors`
```sql
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    purpose VARCHAR(200),
    visit_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Cara Penggunaan

### Untuk Pengunjung:
1. Buka halaman `visitor_log.php`
2. Isi nama lengkap (wajib)
3. Isi tujuan kunjungan (opsional)
4. Klik "Catat Kunjungan"
5. Waktu check-in akan dicatat otomatis

### Untuk Admin:
1. Login sebagai admin
2. Akses menu "Data Pengunjung" di dashboard admin
3. Lihat statistik pengunjung
4. Filter dan cari data pengunjung
5. Proses checkout untuk pengunjung yang masih ada

## Link Akses

- **Form Log Pengunjung**: `visitor_log.php`
- **Admin Dashboard**: `admin/visitors.php`
- **Link dari Halaman Utama**: Ditambahkan di `index.php`

## Keamanan

- **CSRF Protection**: Semua form menggunakan token CSRF
- **Input Validation**: Validasi input di sisi server dan client
- **SQL Injection Protection**: Menggunakan prepared statements
- **XSS Protection**: Output di-escape dengan `htmlspecialchars()`

## Fitur Tambahan

### Real-time Clock
- Menampilkan waktu saat ini yang update setiap detik
- Format waktu Indonesia
- Menampilkan tanggal lengkap

### Responsive Design
- Desain responsif untuk desktop dan mobile
- Bootstrap 5 untuk UI yang modern
- Icon Bootstrap untuk visual yang menarik

### User Experience
- Auto-focus pada field nama
- Form validation dengan feedback
- Alert sukses/error yang informatif
- Tombol kembali ke halaman utama

## Integrasi Sistem

Sistem log pengunjung terintegrasi dengan:
- **Middleware System**: Menggunakan sistem middleware untuk proteksi admin
- **Database Connection**: Menggunakan koneksi database yang sama
- **Session Management**: Terintegrasi dengan sistem session
- **Navigation**: Link dari halaman utama dan dashboard admin

## Contoh Penggunaan

### Skenario 1: Pengunjung Baru
1. Pengunjung membuka `visitor_log.php`
2. Mengisi nama: "Ahmad Fadillah"
3. Mengisi tujuan: "Membaca buku matematika"
4. Klik "Catat Kunjungan"
5. Sistem mencatat: nama, tujuan, tanggal hari ini, waktu check-in

### Skenario 2: Admin Melihat Data
1. Admin login dan akses `admin/visitors.php`
2. Melihat statistik: 15 pengunjung hari ini, 3 masih ada
3. Filter berdasarkan tanggal hari ini
4. Melihat daftar pengunjung dengan status

### Skenario 3: Proses Checkout
1. Admin melihat pengunjung "Ahmad Fadillah" masih ada
2. Klik tombol "Checkout"
3. Konfirmasi checkout di modal
4. Sistem update waktu check-out otomatis
5. Status berubah menjadi "Sudah Pulang"

## Maintenance

### Backup Data
- Backup tabel `visitors` secara berkala
- Export data untuk analisis

### Monitoring
- Monitor jumlah pengunjung harian
- Cek pengunjung yang belum checkout
- Analisis pola kunjungan

### Troubleshooting
- Jika ada error database, cek koneksi di `db.php`
- Jika form tidak berfungsi, cek validasi input
- Jika admin tidak bisa akses, cek middleware configuration 