# Database Setup Guide

## Overview
Sistem ini mendukung tiga jenis database dengan fallback otomatis:
1. **MySQL** (primary) - Untuk production
2. **SQLite** (secondary) - Untuk development
3. **JSON Files** (fallback) - Untuk sistem tanpa PDO drivers

## Automatic Database Selection
Sistem akan secara otomatis mencoba dalam urutan:
1. **MySQL** - Jika PDO MySQL tersedia dan server berjalan
2. **SQLite** - Jika MySQL gagal tapi PDO SQLite tersedia
3. **JSON Files** - Jika tidak ada PDO drivers yang tersedia

## Setup Options

### Option 1: MySQL (Recommended for Production)

#### Prerequisites:
- MySQL Server
- PHP PDO MySQL extension (`pdo_mysql`)

#### Setup:
1. Install MySQL Server
2. Create database: `sistem_akademik`
3. Update credentials in `db.php` if needed:
   ```php
   $host = 'localhost';
   $dbname = 'sistem_akademik';
   $username = 'root';
   $password = '';
   ```

#### Import Database:
```bash
mysql -u root -p sistem_akademik < database.sql
```

### Option 2: SQLite (Easy Development)

#### Prerequisites:
- PHP PDO SQLite extension (usually included by default)

#### Setup:
1. Tidak perlu setup tambahan
2. Database akan dibuat otomatis di `database/sistem_akademik.sqlite`
3. Tabel dan data sample akan dibuat otomatis

### Option 3: JSON Files (Universal Fallback)

#### Prerequisites:
- Hanya PHP standard (tidak perlu ekstensi tambahan)

#### Setup:
1. **Tidak perlu setup apapun!**
2. Database akan dibuat otomatis di `database/json/`
3. Data sample akan dibuat otomatis
4. Bekerja di semua sistem PHP

## Default Credentials

### Admin User:
- Username: `admin`
- Password: `admin123`

### Sample Students:
- Username: `student1`, Password: `password123`
- Username: `student2`, Password: `password123`

## Sample Data

### Books:
- Matematika Dasar (Pendidikan)
- Sejarah Indonesia (Sejarah)
- Fisika Modern (Sains)

### Students:
- Ahmad Fadillah (X-A, IPA)
- Siti Nurhaliza (X-B, IPS)

## Troubleshooting

### Error: "could not find driver"
**Solution:** Sistem akan otomatis menggunakan JSON fallback
- Tidak perlu install apapun
- Aplikasi akan tetap berfungsi normal

### Error: "Database connection failed"
**Solution:** 
1. Check MySQL server is running (jika menggunakan MySQL)
2. Verify database credentials
3. System will automatically fallback to SQLite atau JSON

### JSON Database Location
- Directory: `database/json/`
- Files: `users.json`, `books.json`, `students.json`, etc.
- Directory will be created automatically
- File permissions: 644 (readable by web server)

## Development Workflow

1. **Start Development:**
   ```bash
   php -S localhost:8000
   ```

2. **Access Application:**
   - Main: http://localhost:8000
   - Admin: http://localhost:8000/admin/
   - Student: http://localhost:8000/siswa/

3. **Database Operations:**
   - All CRUD operations work with all three database types
   - No code changes needed between databases
   - Automatic table creation and sample data

## Production Deployment

### For Production (MySQL):
1. Install MySQL Server
2. Create database and user
3. Import schema: `mysql -u user -p database < database.sql`
4. Update database credentials in `db.php`
5. Ensure `pdo_mysql` extension is enabled

### For Simple Hosting (SQLite):
1. Upload files to hosting
2. Ensure `database/` directory is writable
3. System will create SQLite database automatically

### For Basic Hosting (JSON):
1. Upload files to hosting
2. Ensure `database/json/` directory is writable
3. System will create JSON database automatically
4. **Works on any PHP hosting without database setup!**

## Security Notes

- Change default admin password in production
- Use strong passwords for database users
- Set proper file permissions for database files
- Enable HTTPS in production
- Regular database backups

## Backup and Restore

### MySQL Backup:
```bash
mysqldump -u root -p sistem_akademik > backup.sql
```

### MySQL Restore:
```bash
mysql -u root -p sistem_akademik < backup.sql
```

### SQLite Backup:
```bash
cp database/sistem_akademik.sqlite backup.sqlite
```

### SQLite Restore:
```bash
cp backup.sqlite database/sistem_akademik.sqlite
```

### JSON Backup:
```bash
cp -r database/json/ backup_json/
```

### JSON Restore:
```bash
cp -r backup_json/ database/json/
```

## Performance Considerations

### MySQL:
- Better for high-traffic applications
- Supports concurrent users
- Requires server setup

### SQLite:
- Perfect for development and small applications
- Single file database
- No server setup required
- Limited concurrent access

### JSON Files:
- Perfect for simple applications
- No database server required
- Easy to backup and restore
- Limited concurrent access
- **Works everywhere!**

## Migration Between Databases

### MySQL to SQLite/JSON:
1. Export MySQL data to CSV/SQL
2. Import into target database
3. Update application configuration

### SQLite to MySQL:
1. Export SQLite data
2. Import into MySQL database
3. Update application configuration

### JSON to MySQL/SQLite:
1. Export JSON data
2. Import into target database
3. Update application configuration

## Support

For database issues:
1. Check error logs in PHP error log
2. Verify database credentials (if using MySQL)
3. Ensure proper file permissions
4. Test database connection manually

## Quick Start

1. **Clone/Download project**
2. **Start PHP server:**
   ```bash
   php -S localhost:8000
   ```
3. **Access application:**
   - URL: http://localhost:8000
   - Login as admin: admin/admin123
4. **Start using the system!**

## Universal Compatibility

Sistem ini sekarang **100% kompatibel** dengan:
- ✅ Windows, Linux, macOS
- ✅ XAMPP, WAMP, MAMP
- ✅ Shared hosting tanpa database
- ✅ VPS dengan atau tanpa MySQL
- ✅ Development environment apapun

**Tidak ada lagi masalah database!** 🎉

The system will automatically handle database setup and provide sample data for immediate use, regardless of your server configuration. 