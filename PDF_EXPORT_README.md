# Fitur Export PDF - Sistem Informasi Akademik

## Deskripsi
Fitur export laporan peminjaman buku ke PDF menggunakan library mPDF untuk menghasilkan laporan yang bisa dicetak atau diunduh.

## Instalasi mPDF

### 1. Install Composer (jika belum ada)
```bash
# Download Composer installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Move to global installation (optional)
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Install mPDF
```bash
# Di root folder project
composer install
```

### 3. Verifikasi Instalasi
```bash
# Cek apakah vendor folder sudah terbuat
ls vendor/
# Harus ada folder mpdf/mpdf
```

## Fitur Export PDF

### 1. Laporan Peminjaman Buku (`admin/export_borrowings_pdf.php`)
- **Filter yang didukung:**
  - Status peminjaman (Dipinjam, Terlambat, Dikembalikan)
  - Rentang tanggal pinjam (mulai & akhir)
  - Pencarian nama peminjam, judul buku, atau penulis

- **Konten PDF:**
  - Header dengan judul dan informasi sistem
  - Statistik ringkasan (total, aktif, terlambat, dikembalikan, denda)
  - Informasi filter yang diterapkan
  - Tabel data peminjaman lengkap
  - Ringkasan di akhir laporan

- **Format PDF:**
  - Ukuran A4
  - Margin yang optimal untuk cetak
  - Header dan footer otomatis
  - Styling yang rapi dan profesional

### 2. Cara Menggunakan
1. Buka halaman `admin/borrowings.php`
2. Gunakan filter yang diinginkan (status, tanggal, pencarian)
3. Klik tombol **"Export PDF"** (ikon file PDF merah)
4. PDF akan otomatis terdownload dengan nama file yang sesuai

### 3. Nama File PDF
Format: `laporan_peminjaman_YYYY-MM-DD_HH-MM-SS_[filter].pdf`

Contoh:
- `laporan_peminjaman_2024-01-15_14-30-25.pdf` (semua data)
- `laporan_peminjaman_2024-01-15_14-30-25_2024-01-01_to_2024-01-31.pdf` (dengan filter tanggal)

## Struktur File

### 1. File Utama
- `admin/export_borrowings_pdf.php` - Generator PDF laporan peminjaman
- `composer.json` - Konfigurasi dependencies

### 2. Dependencies
- `mpdf/mpdf` - Library untuk generate PDF dari HTML

### 3. Integrasi
- Tombol export ditambahkan di `admin/borrowings.php`
- Menggunakan filter yang sama dengan halaman utama
- Logging visitor otomatis

## Keamanan

### 1. Akses Kontrol
- Hanya admin yang bisa mengakses fitur export
- Middleware protection diterapkan
- CSRF protection untuk form

### 2. Validasi Input
- Filter parameters divalidasi
- SQL injection protection dengan prepared statements
- XSS protection dengan htmlspecialchars

### 3. Error Handling
- Try-catch untuk database operations
- Graceful error handling jika mPDF tidak terinstall

## Troubleshooting

### 1. Error "Class 'Mpdf\Mpdf' not found"
```bash
# Pastikan composer install sudah dijalankan
composer install

# Atau update autoloader
composer dump-autoload
```

### 2. Error "Permission denied" saat download
- Pastikan folder memiliki permission write
- Cek PHP memory limit (minimal 128MB)

### 3. PDF kosong atau error
- Cek apakah ada data sesuai filter
- Cek error log PHP
- Pastikan encoding UTF-8

### 4. Font tidak tampil dengan benar
- mPDF menggunakan font default Arial
- Untuk font Indonesia, bisa ditambahkan konfigurasi font

## Konfigurasi Lanjutan

### 1. Custom Font (Opsional)
```php
// Di export_borrowings_pdf.php
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'arial',
    'default_font_size' => 10,
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_header' => 10,
    'margin_footer' => 10
]);
```

### 2. Watermark (Opsional)
```php
// Tambahkan watermark
$mpdf->SetWatermarkText('DRAFT');
$mpdf->showWatermarkText = true;
```

### 3. Password Protection (Opsional)
```php
// Tambahkan password untuk PDF
$mpdf->SetProtection(['print', 'copy'], 'password123');
```

## Pengembangan Selanjutnya

### 1. Export Laporan Lain
- Laporan pengunjung ke PDF
- Laporan buku ke PDF
- Laporan siswa ke PDF

### 2. Template PDF
- Template yang lebih menarik
- Logo sekolah
- Header/footer custom

### 3. Batch Export
- Export multiple laporan sekaligus
- Email otomatis ke admin

### 4. Preview PDF
- Preview PDF sebelum download
- Embed PDF viewer di browser

## Dependencies

- PHP 7.4+
- Composer
- mPDF 8.1+
- MySQL/MariaDB

## Catatan Penting

1. **Memory Limit**: Pastikan PHP memory limit cukup (minimal 128MB)
2. **Timeout**: Untuk data besar, mungkin perlu menambah max_execution_time
3. **Encoding**: Pastikan database menggunakan UTF-8
4. **Backup**: Selalu backup data sebelum testing fitur export

## Contoh Penggunaan

### Skenario 1: Export Semua Data
1. Buka `admin/borrowings.php`
2. Klik "Export PDF"
3. Download semua data peminjaman

### Skenario 2: Export dengan Filter
1. Pilih status "Terlambat"
2. Set tanggal mulai: 2024-01-01
3. Set tanggal akhir: 2024-01-31
4. Klik "Export PDF"
5. Download data peminjaman terlambat bulan Januari 2024

### Skenario 3: Export Pencarian
1. Ketik "Matematika" di kolom pencarian
2. Klik "Export PDF"
3. Download data peminjaman buku matematika 