# Sistem Informasi Akademik

Sistem informasi akademik berbasis web untuk mengelola data siswa, nilai, dan administrasi sekolah. Aplikasi ini dirancang untuk memudahkan proses manajemen akademik dengan antarmuka yang user-friendly.

## 🎯 Deskripsi Proyek

Sistem Informasi Akademik adalah aplikasi web PHP yang menyediakan platform untuk:
- Manajemen data siswa
- Pengelolaan nilai akademik
- Dashboard admin untuk administrasi
- Dashboard siswa untuk melihat informasi pribadi
- Sistem autentikasi yang aman

## ✨ Fitur Utama

### 👨‍💼 Dashboard Admin
- Manajemen data siswa
- Pengelolaan nilai akademik
- Laporan dan statistik
- Konfigurasi sistem
- Backup dan restore data

### 👨‍🎓 Dashboard Siswa
- Melihat nilai akademik pribadi
- Informasi profil siswa
- Riwayat akademik
- Notifikasi dan pengumuman

### 🔐 Sistem Autentikasi
- Login multi-role (Admin & Siswa)
- Session management yang aman
- Logout otomatis
- Validasi input yang ketat

### 🎨 Antarmuka Pengguna
- Desain responsif
- User-friendly interface
- Navigasi yang intuitif
- Kompatibel dengan berbagai browser

## 🛠️ Teknologi yang Digunakan

### Backend
- **PHP 8.2+** - Bahasa pemrograman utama
- **PDO** - Database abstraction layer
- **MySQL** - Database management system
- **Session Management** - PHP native sessions

### Frontend
- **HTML5** - Markup language
- **CSS3** - Styling dan layout
- **JavaScript** - Interaktivitas client-side
- **Responsive Design** - Mobile-friendly interface

### Database
- **MySQL** - Relational database
- **PDO Prepared Statements** - SQL injection prevention
- **UTF-8 Encoding** - Support karakter internasional

### Development Tools
- **PHP Built-in Server** - Development server
- **Git** - Version control
- **Composer** - Dependency management (jika diperlukan)

## 📋 Persyaratan Sistem

- PHP 8.0 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx) atau PHP built-in server
- Browser modern (Chrome, Firefox, Safari, Edge)

## 🚀 Instalasi dan Setup

### 1. Clone Repository
```bash
git clone [URL_REPOSITORY]
cd tugas_akhir
```

### 2. Setup Database
```sql
-- Buat database
CREATE DATABASE sistem_akademik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import struktur database (jika ada file SQL)
mysql -u root -p sistem_akademik < database.sql
```

### 3. Konfigurasi Database
Edit file `db.php` dan sesuaikan konfigurasi database:
```php
$host = 'localhost';
$dbname = 'sistem_akademik';
$username = 'root';
$password = '';
```

### 4. Jalankan Aplikasi
```bash
# Menggunakan PHP built-in server
php -S localhost:8000

# Atau menggunakan web server (Apache/Nginx)
# Letakkan file di direktori web server
```

### 5. Akses Aplikasi
Buka browser dan kunjungi:
- **URL**: http://localhost:8000
- **Login Admin**: [sesuaikan dengan data yang ada]
- **Login Siswa**: [sesuaikan dengan data yang ada]

## 📁 Struktur Proyek

```
tugas_akhir/
├── admin/                 # Dashboard admin
│   └── index.php
├── assets/               # Asset statis
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── includes/             # File include
│   └── database.php
├── siswa/               # Dashboard siswa
│   └── index.php
├── db.php              # Koneksi database PDO
├── index.php           # Halaman utama
├── login.php           # Halaman login
├── logout.php          # Halaman logout
├── .gitignore          # Git ignore rules
└── README.md           # Dokumentasi proyek
```

## 🔧 Konfigurasi

### Database Configuration
File `db.php` berisi konfigurasi database dengan fitur:
- PDO connection dengan error handling
- Prepared statements untuk keamanan
- Helper functions untuk operasi database
- Transaction support

### Session Management
- Session dimulai di setiap halaman yang memerlukan autentikasi
- Role-based access control
- Secure logout mechanism

## 🛡️ Keamanan

- **SQL Injection Prevention**: Menggunakan PDO prepared statements
- **XSS Protection**: Input sanitization
- **Session Security**: Proper session management
- **Role-based Access**: Admin dan siswa memiliki akses terbatas
- **Input Validation**: Validasi input di sisi server

## 📊 Fitur Database

### Tabel Utama (Contoh)
- `users` - Data pengguna (admin/siswa)
- `students` - Data siswa
- `grades` - Data nilai
- `subjects` - Data mata pelajaran
- `classes` - Data kelas

## 🧪 Testing

Untuk testing aplikasi:
1. Pastikan database sudah terkonfigurasi dengan benar
2. Coba login dengan kredensial yang valid
3. Test fitur admin dan siswa
4. Verifikasi keamanan session

## 📝 Log dan Debugging

- Error log disimpan di file log sistem
- Database error handling dengan try-catch
- User-friendly error messages
- Debug mode dapat diaktifkan untuk development

## 🔄 Maintenance

### Backup Database
```bash
mysqldump -u root -p sistem_akademik > backup.sql
```

### Update Aplikasi
1. Backup database terlebih dahulu
2. Update file aplikasi
3. Jalankan migration (jika ada)
4. Test fitur-fitur utama

## 🤝 Kontribusi

Untuk berkontribusi pada proyek ini:
1. Fork repository
2. Buat branch fitur baru
3. Commit perubahan
4. Push ke branch
5. Buat Pull Request

## 📄 Lisensi

Proyek ini dibuat untuk tujuan akademik dan pembelajaran.

## 👨‍💻 Developer

- **Nama**: [Nama Developer]
- **Email**: [Email]
- **Institusi**: [Nama Institusi]

## 📞 Support

Untuk bantuan dan dukungan:
- Email: [email_support]
- Issues: [URL_issues_repository]

---

**Versi**: 1.0.0  
**Update Terakhir**: Juli 2024  
**Status**: Development 