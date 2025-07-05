<?php
/**
 * Database Connection using PDO
 * File: db.php
 * Description: Handles database connection using PDO with proper error handling
 */

// Database configuration
$host = 'localhost';
$dbname = 'sistem_akademik';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Optional: Set timezone if needed
    // $pdo->exec("SET time_zone = '+07:00'");
    
} catch (PDOException $e) {
    // Log error for debugging
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly error message
    die("Maaf, terjadi kesalahan pada koneksi database. Silakan coba lagi nanti.");
}

/**
 * Helper function to execute prepared statements
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return PDOStatement|false
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to fetch single row
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Helper function to fetch all rows
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Helper function to get row count
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return int
 */
function getRowCount($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->rowCount() : 0;
}

/**
 * Helper function to get last insert ID
 * @return string
 */
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Helper function to begin transaction
 */
function beginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

/**
 * Helper function to commit transaction
 */
function commitTransaction() {
    global $pdo;
    return $pdo->commit();
}

/**
 * Helper function to rollback transaction
 */
function rollbackTransaction() {
    global $pdo;
    return $pdo->rollback();
}

// Make $pdo available globally
return $pdo;
?> 