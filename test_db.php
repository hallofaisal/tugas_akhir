<?php
/**
 * Database Connection Test
 * File: test_db.php
 * Description: Simple test to check database connection and PDO drivers
 */

// Include visitor logger
require_once 'db.php';
require_once 'includes/visitor_logger.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('test_db.php');

echo "<h1>Database Connection Test</h1>";

// Check if PDO is available
echo "<h2>1. PDO Availability</h2>";
if (extension_loaded('pdo')) {
    echo "✅ PDO extension is loaded<br>";
    
    // List available PDO drivers
    $drivers = PDO::getAvailableDrivers();
    echo "Available PDO drivers: " . implode(', ', $drivers) . "<br>";
    
    if (in_array('mysql', $drivers)) {
        echo "✅ MySQL PDO driver is available<br>";
    } else {
        echo "❌ MySQL PDO driver is NOT available<br>";
    }
} else {
    echo "❌ PDO extension is NOT loaded<br>";
}

// Test database connection
echo "<h2>2. Database Connection Test</h2>";
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
    
    // Add MySQL-specific options if available
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
    }
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ Database connection successful<br>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $result = $stmt->fetch();
    echo "✅ Query test successful. Books count: " . $result['count'] . "<br>";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check PHP version and extensions
echo "<h2>3. PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "<br>";

echo "<h2>4. Recommendations</h2>";
if (!extension_loaded('pdo')) {
    echo "⚠️ Install PDO extension<br>";
}
if (!extension_loaded('pdo_mysql')) {
    echo "⚠️ Install PDO MySQL driver<br>";
}
if (!extension_loaded('mysql')) {
    echo "⚠️ Install MySQL extension (optional)<br>";
}
?> 
