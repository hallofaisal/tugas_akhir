<?php
// Database configuration
$host = 'localhost';
$dbname = 'sistem_akademik';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // For development, we'll create a simple fallback
    // In production, you should handle this properly
    error_log("Database connection failed: " . $e->getMessage());
    
    // Create a mock connection for demo purposes
    class MockPDO {
        public function query($sql) {
            return new MockResultSet();
        }
        public function prepare($sql) {
            return new MockStatement();
        }
    }
    
    class MockResultSet {
        public function fetchAll() {
            return [];
        }
        public function fetch() {
            return false;
        }
    }
    
    class MockStatement {
        public function execute($params = []) {
            return true;
        }
        public function fetchAll() {
            return [];
        }
        public function fetch() {
            return false;
        }
    }
    
    $pdo = new MockPDO();
}

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('../login.php');
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        redirect('../index.php');
    }
}

function require_siswa() {
    require_login();
    if ($_SESSION['role'] !== 'siswa') {
        redirect('../index.php');
    }
}
?> 
