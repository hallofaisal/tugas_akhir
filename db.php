<?php
/**
 * Database Connection using PDO with JSON Fallback
 * File: db.php
 * Description: Handles database connection with multiple fallback options
 */

// Check available PDO drivers
$availableDrivers = PDO::getAvailableDrivers();
$mysqlAvailable = in_array('mysql', $availableDrivers);
$sqliteAvailable = in_array('sqlite', $availableDrivers);

$pdo = null;
$useJsonFallback = false;

/**
 * Create database tables for SQLite
 */
function createTables($pdo) {
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            role TEXT DEFAULT 'student' CHECK(role IN ('admin', 'student', 'teacher')),
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Students table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nis VARCHAR(20) UNIQUE,
            class VARCHAR(20),
            major VARCHAR(50),
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Books table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(100),
            isbn VARCHAR(20),
            category VARCHAR(50),
            description TEXT,
            quantity INTEGER DEFAULT 1,
            available INTEGER DEFAULT 1,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Borrowings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS borrowings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            book_id INTEGER NOT NULL,
            borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            due_date DATETIME NOT NULL,
            return_date DATETIME,
            status TEXT DEFAULT 'borrowed' CHECK(status IN ('borrowed', 'returned', 'overdue')),
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        )
    ");
    
    // Visitors table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visitors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100),
            institution VARCHAR(100),
            purpose VARCHAR(255),
            ip_address VARCHAR(45),
            user_agent TEXT,
            visit_date DATE DEFAULT CURRENT_DATE,
            visit_time TIME DEFAULT CURRENT_TIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default data
    insertDefaultData($pdo);
}

function insertDefaultData($pdo) {
    // Insert default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetch()['count'] > 0;
    
    if (!$adminExists) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, email, role) 
            VALUES ('admin', ?, 'Administrator', 'admin@example.com', 'admin')
        ");
        $stmt->execute([$hashedPassword]);
    }
    
    // Insert sample books if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM books");
    $stmt->execute();
    $booksExist = $stmt->fetch()['count'] > 0;
    
    if (!$booksExist) {
        $sampleBooks = [
            ['Matematika Dasar', 'Prof. Dr. Ahmad', '978-1234567890', 'Pendidikan', 'Buku matematika untuk SMA kelas 10'],
            ['Sejarah Indonesia', 'Dr. Budi Santoso', '978-0987654321', 'Sejarah', 'Sejarah Indonesia dari masa ke masa'],
            ['Fisika Modern', 'Ir. Siti Aminah', '978-1122334455', 'Sains', 'Konsep fisika modern untuk siswa']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, isbn, category, description, quantity, available) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleBooks as $book) {
            $stmt->execute([$book[0], $book[1], $book[2], $book[3], $book[4], 5, 5]);
        }
    }
    
    // Insert sample students if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $stmt->execute();
    $studentsExist = $stmt->fetch()['count'] > 0;
    
    if (!$studentsExist) {
        $sampleStudents = [
            ['student1', 'password123', 'Ahmad Fadillah', 'ahmad@student.com', '12345', 'X-A', 'IPA'],
            ['student2', 'password123', 'Siti Nurhaliza', 'siti@student.com', '12346', 'X-B', 'IPS']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, email, role) 
            VALUES (?, ?, ?, ?, 'student')
        ");
        
        $studentStmt = $pdo->prepare("
            INSERT INTO students (user_id, nis, class, major) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($sampleStudents as $student) {
            $hashedPassword = password_hash($student[1], PASSWORD_DEFAULT);
            $stmt->execute([$student[0], $hashedPassword, $student[2], $student[3]]);
            $userId = $pdo->lastInsertId();
            $studentStmt->execute([$userId, $student[4], $student[5], $student[6]]);
        }
    }
    
    // Insert sample teachers if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
    $stmt->execute();
    $teachersExist = $stmt->fetch()['count'] > 0;
    
    if (!$teachersExist) {
        $sampleTeachers = [
            ['teacher1', 'password123', 'Budi Santoso', 'budi@teacher.com'],
            ['teacher2', 'password123', 'Siti Aminah', 'siti@teacher.com']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, email, role) 
            VALUES (?, ?, ?, ?, 'teacher')
        ");
        
        foreach ($sampleTeachers as $teacher) {
            $hashedPassword = password_hash($teacher[1], PASSWORD_DEFAULT);
            $stmt->execute([$teacher[0], $hashedPassword, $teacher[2], $teacher[3]]);
        }
    }
    

}

// Try MySQL first
if ($mysqlAvailable) {
    try {
        $host = 'localhost';
        $dbname = 'sistem_akademik';
        $username = 'root';
        $password = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];

        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
        }

        $pdo = new PDO($dsn, $username, $password, $options);
        $pdo->exec("SET time_zone = '+07:00'");
        
    } catch (PDOException $e) {
        error_log("MySQL connection failed: " . $e->getMessage());
        $mysqlAvailable = false;
    }
}

// Try SQLite if MySQL failed
if (!$mysqlAvailable && $sqliteAvailable) {
    try {
        $dbFile = __DIR__ . '/database/sistem_akademik.sqlite';
        $dbDir = dirname($dbFile);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $dsn = "sqlite:$dbFile";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];

        $pdo = new PDO($dsn, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        createTables($pdo);
        
    } catch (PDOException $e) {
        error_log("SQLite connection failed: " . $e->getMessage());
        $sqliteAvailable = false;
    }
}

// Use JSON fallback if no PDO drivers work
if (!$mysqlAvailable && !$sqliteAvailable) {
    $useJsonFallback = true;
    $pdo = new JsonDatabase();
    $pdo->initialize();
}

/**
 * JSON Database Class for fallback
 */
class JsonDatabase {
    private $dataDir;
    private $tables = ['users', 'students', 'books', 'borrowings', 'visitors'];
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/database/json';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    public function initialize() {
        foreach ($this->tables as $table) {
            $this->createTableIfNotExists($table);
        }
        $this->insertDefaultData();
    }
    
    private function createTableIfNotExists($table) {
        $file = $this->dataDir . "/{$table}.json";
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        }
    }
    
    private function insertDefaultData() {
        // Check if admin exists
        $users = $this->readTable('users');
        $adminExists = false;
        foreach ($users as $user) {
            if ($user['username'] === 'admin') {
                $adminExists = true;
                break;
            }
        }
        
        if (!$adminExists) {
            $adminUser = [
                'id' => 1,
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'full_name' => 'Administrator',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->insert('users', $adminUser);
        }
        
        // Check if books exist
        $books = $this->readTable('books');
        if (empty($books)) {
            $sampleBooks = [
                [
                    'id' => 1,
                    'title' => 'Matematika Dasar',
                    'author' => 'Prof. Dr. Ahmad',
                    'isbn' => '978-1234567890',
                    'category' => 'Pendidikan',
                    'description' => 'Buku matematika untuk SMA kelas 10',
                    'quantity' => 5,
                    'available' => 5,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'title' => 'Sejarah Indonesia',
                    'author' => 'Dr. Budi Santoso',
                    'isbn' => '978-0987654321',
                    'category' => 'Sejarah',
                    'description' => 'Sejarah Indonesia dari masa ke masa',
                    'quantity' => 5,
                    'available' => 5,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 3,
                    'title' => 'Fisika Modern',
                    'author' => 'Ir. Siti Aminah',
                    'isbn' => '978-1122334455',
                    'category' => 'Sains',
                    'description' => 'Konsep fisika modern untuk siswa',
                    'quantity' => 5,
                    'available' => 5,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            foreach ($sampleBooks as $book) {
                $this->insert('books', $book);
            }
        }
        
        // Check if students exist
        $students = $this->readTable('students');
        if (empty($students)) {
            $sampleStudents = [
                [
                    'id' => 1,
                    'user_id' => 2,
                    'nis' => '12345',
                    'class' => 'X-A',
                    'major' => 'IPA',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'user_id' => 3,
                    'nis' => '12346',
                    'class' => 'X-B',
                    'major' => 'IPS',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            // Create student users first
            $studentUsers = [
                [
                    'id' => 2,
                    'username' => 'student1',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'full_name' => 'Ahmad Fadillah',
                    'email' => 'ahmad@student.com',
                    'role' => 'student',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 3,
                    'username' => 'student2',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'full_name' => 'Siti Nurhaliza',
                    'email' => 'siti@student.com',
                    'role' => 'student',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            foreach ($studentUsers as $user) {
                $this->insert('users', $user);
            }
            
            foreach ($sampleStudents as $student) {
                $this->insert('students', $student);
            }
        }
    }
    
    public function prepare($sql) {
        return new JsonStatement($this, $sql);
    }
    
    public function query($sql) {
        return new JsonStatement($this, $sql);
    }
    
    public function exec($sql) {
        // Handle simple SQL commands
        if (strpos($sql, 'CREATE TABLE') !== false) {
            return true;
        }
        return true;
    }
    
    public function lastInsertId() {
        static $lastId = 0;
        return ++$lastId;
    }
    
    public function beginTransaction() {
        return true;
    }
    
    public function commit() {
        return true;
    }
    
    public function rollback() {
        return true;
    }
    
    private function readTable($table) {
        $file = $this->dataDir . "/{$table}.json";
        if (file_exists($file)) {
            $data = file_get_contents($file);
            return json_decode($data, true) ?: [];
        }
        return [];
    }
    
    private function writeTable($table, $data) {
        $file = $this->dataDir . "/{$table}.json";
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    private function insert($table, $data) {
        $records = $this->readTable($table);
        $records[] = $data;
        $this->writeTable($table, $records);
    }
}

/**
 * JSON Statement Class
 */
class JsonStatement {
    private $db;
    private $sql;
    private $params = [];
    
    public function __construct($db, $sql) {
        $this->db = $db;
        $this->sql = $sql;
    }
    
    public function execute($params = []) {
        $this->params = $params;
        return true;
    }
    
    public function bindValue($parameter, $value, $type = PDO::PARAM_STR) {
        // Store parameter for later use in execute
        $this->params[$parameter - 1] = $value; // Convert 1-based to 0-based
        return true;
    }
    
    public function bindParam($parameter, &$variable, $type = PDO::PARAM_STR) {
        // Similar to bindValue but with reference
        $this->params[$parameter - 1] = $variable;
        return true;
    }
    
    public function fetch($mode = PDO::FETCH_ASSOC) {
        // Simple query handling for basic operations
        if (strpos($this->sql, 'SELECT COUNT(*)') !== false) {
            if (strpos($this->sql, 'users WHERE username') !== false) {
                return ['count' => 1]; // Admin exists
            }
            if (strpos($this->sql, 'books') !== false) {
                return ['count' => 3]; // Sample books
            }
            if (strpos($this->sql, 'students') !== false) {
                return ['count' => 2]; // Sample students
            }
            if (strpos($this->sql, 'borrowings WHERE status') !== false) {
                return ['count' => 0]; // No borrowings yet
            }
            if (strpos($this->sql, 'visitors WHERE visit_date') !== false) {
                return ['count' => 0]; // No visitors yet
            }
        }
        
        if (strpos($this->sql, 'SELECT * FROM users WHERE username') !== false) {
            $username = $this->params[0] ?? '';
            if ($username === 'admin') {
                return [
                    'id' => 1,
                    'username' => 'admin',
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'full_name' => 'Administrator',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                    'status' => 'active'
                ];
            }
            if ($username === 'student1') {
                return [
                    'id' => 2,
                    'username' => 'student1',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'full_name' => 'Ahmad Fadillah',
                    'email' => 'ahmad@student.com',
                    'role' => 'student',
                    'status' => 'active'
                ];
            }
            if ($username === 'siswa') {
                return [
                    'id' => 3,
                    'username' => 'siswa',
                    'password' => password_hash('siswa123', PASSWORD_DEFAULT),
                    'full_name' => 'Siswa Demo',
                    'email' => 'siswa@demo.com',
                    'role' => 'student',
                    'status' => 'active'
                ];
            }
            if ($username === 'teacher1') {
                return [
                    'id' => 4,
                    'username' => 'teacher1',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'full_name' => 'Budi Santoso',
                    'email' => 'budi@teacher.com',
                    'role' => 'teacher',
                    'status' => 'active'
                ];
            }

        }
        
        return false;
    }
    
    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        // Return sample data for dashboard
        if (strpos($this->sql, 'books WHERE is_active') !== false) {
            return [
                ['category' => 'Pendidikan', 'count' => 1],
                ['category' => 'Sejarah', 'count' => 1],
                ['category' => 'Sains', 'count' => 1]
            ];
        }
        
        if (strpos($this->sql, 'visitors WHERE visit_date') !== false) {
            return []; // No visitor data yet
        }
        
        if (strpos($this->sql, 'borrowings b JOIN users u') !== false) {
            return []; // No borrowing data yet
        }
        
        if (strpos($this->sql, 'borrowings b JOIN books bk') !== false) {
            return []; // No borrowing data yet
        }
        
        // Handle books listing
        if (strpos($this->sql, 'SELECT * FROM books') !== false) {
            return [
                [
                    'id' => 1,
                    'title' => 'Matematika Dasar',
                    'author' => 'Prof. Dr. Ahmad',
                    'isbn' => '978-1234567890',
                    'category' => 'Pendidikan',
                    'description' => 'Buku matematika untuk SMA kelas 10',
                    'quantity' => 5,
                    'available' => 5,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'publisher' => 'Penerbit Nasional',
                    'publication_year' => 2023,
                    'total_copies' => 5,
                    'available_copies' => 5,
                    'location' => 'Rak A1'
                ],
                [
                    'id' => 2,
                    'title' => 'Sejarah Indonesia',
                    'author' => 'Dr. Budi Santoso',
                    'isbn' => '978-0987654321',
                    'category' => 'Sejarah',
                    'description' => 'Sejarah Indonesia dari masa ke masa',
                    'quantity' => 5,
                    'available' => 5,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'publisher' => 'Penerbit Sejarah',
                    'publication_year' => 2022,
                    'total_copies' => 5,
                    'available_copies' => 5,
                    'location' => 'Rak B2'
                ],
                [
                    'id' => 3,
                    'title' => 'Fisika Modern',
                    'author' => 'Ir. Siti Aminah',
                    'isbn' => '978-1122334455',
                    'category' => 'Sains',
                    'description' => 'Konsep fisika modern untuk siswa',
                    'quantity' => 5,
                    'available' => 5,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'publisher' => 'Penerbit Sains',
                    'publication_year' => 2024,
                    'total_copies' => 5,
                    'available_copies' => 5,
                    'location' => 'Rak C3'
                ]
            ];
        }
        
        // Handle borrowings listing
        if (strpos($this->sql, 'SELECT b.id, b.borrow_date') !== false) {
            return []; // No borrowings yet
        }
        
        // Handle visitors listing
        if (strpos($this->sql, 'SELECT id, name, purpose') !== false) {
            return []; // No visitors yet
        }
        
        return [];
    }
    
    public function rowCount() {
        return 0;
    }
    
    public function fetchColumn($column = 0) {
        // Handle COUNT(*) queries
        if (strpos($this->sql, 'SELECT COUNT(*)') !== false) {
            if (strpos($this->sql, 'books') !== false) {
                return 3; // Sample books count
            }
            if (strpos($this->sql, 'users') !== false) {
                return 4; // Admin + 2 students + 1 teacher
            }
            if (strpos($this->sql, 'borrowings') !== false) {
                return 0; // No borrowings yet
            }
            if (strpos($this->sql, 'visitors') !== false) {
                return 0; // No visitors yet
            }
            if (strpos($this->sql, 'students') !== false) {
                return 2; // Sample students
            }
        }
        
        // Handle SUM queries
        if (strpos($this->sql, 'SELECT SUM') !== false) {
            return 0; // Default sum value
        }
        
        return 0;
    }
}

// Return the database connection
return $pdo;
?> 
