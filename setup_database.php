<?php
/**
 * Database Setup Script
 * File: setup_database.php
 * Description: Creates database and tables if they don't exist
 */

echo "<h1>Database Setup</h1>";

try {
    // Connect to MySQL without specifying database
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to MySQL server<br>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS sistem_akademik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'sistem_akademik' created/verified<br>";
    
    // Use the database
    $pdo->exec("USE sistem_akademik");
    
    // Create tables
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                role ENUM('admin', 'siswa') DEFAULT 'siswa',
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ",
        'books' => "
            CREATE TABLE IF NOT EXISTS books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                author VARCHAR(100) NOT NULL,
                isbn VARCHAR(20) UNIQUE,
                publisher VARCHAR(100),
                publication_year INT,
                category VARCHAR(50),
                total_copies INT DEFAULT 1,
                available_copies INT DEFAULT 1,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ",
        'borrowings' => "
            CREATE TABLE IF NOT EXISTS borrowings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                borrow_date DATE NOT NULL,
                due_date DATE NOT NULL,
                return_date DATE NULL,
                status ENUM('borrowed', 'returned', 'overdue', 'lost') DEFAULT 'borrowed',
                fine_amount DECIMAL(10,2) DEFAULT 0.00,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
            )
        ",
        'students' => "
            CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNIQUE NOT NULL,
                nis VARCHAR(20) UNIQUE NOT NULL,
                class VARCHAR(20),
                major VARCHAR(50),
                phone VARCHAR(20),
                address TEXT,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ",
        'visitors' => "
            CREATE TABLE IF NOT EXISTS visitors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                purpose VARCHAR(200),
                visit_date DATE NOT NULL,
                check_in TIME,
                check_out TIME,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'grades' => "
            CREATE TABLE IF NOT EXISTS grades (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                subject VARCHAR(50) NOT NULL,
                grade DECIMAL(5,2) NOT NULL,
                semester ENUM('1', '2') NOT NULL,
                academic_year VARCHAR(10) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            )
        "
    ];
    
    foreach ($tables as $table_name => $sql) {
        $pdo->exec($sql);
        echo "✅ Table '$table_name' created/verified<br>";
    }
    
    // Insert default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, email, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', $hashed_password, 'Administrator', 'admin@school.com', 'admin']);
        echo "✅ Default admin user created (username: admin, password: admin123)<br>";
    } else {
        echo "ℹ️ Admin user already exists<br>";
    }
    
    // Insert sample books if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM books");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $sample_books = [
            ['Matematika Dasar', 'John Doe', '978-1234567890', 'Penerbit A', 2020, 'Pendidikan'],
            ['Bahasa Indonesia', 'Jane Smith', '978-0987654321', 'Penerbit B', 2021, 'Pendidikan'],
            ['Sejarah Indonesia', 'Bob Johnson', '978-1122334455', 'Penerbit C', 2019, 'Sejarah'],
            ['Fisika Modern', 'Alice Brown', '978-5566778899', 'Penerbit D', 2022, 'Sains'],
            ['Kimia Organik', 'Charlie Wilson', '978-9988776655', 'Penerbit E', 2021, 'Sains']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, isbn, publisher, publication_year, category, total_copies, available_copies)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sample_books as $book) {
            $stmt->execute([...$book, 3, 3]);
        }
        echo "✅ Sample books inserted<br>";
    } else {
        echo "ℹ️ Books already exist<br>";
    }
    
    echo "<h2>✅ Database setup completed successfully!</h2>";
    echo "<p>You can now access the system with:</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: admin, password: admin123</li>";
    echo "<li><strong>Student:</strong> Register a new account</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "<br>";
}
?> 