<?php
session_start();

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    $pdo = require_once 'db.php';
    echo "✅ Database connection successful<br>";
    echo "Database type: " . (is_object($pdo) ? get_class($pdo) : 'Unknown') . "<br>";
    
    // Test if tables exist
    if (method_exists($pdo, 'query')) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . implode(', ', $tables) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test login functionality
echo "<h2>Testing Login</h2>";
try {
    $pdo = require_once 'db.php';
    
    // Test admin login
    $username = 'admin';
    $password = 'admin123';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        echo "✅ Admin login successful<br>";
        echo "User role: " . $user['role'] . "<br>";
        echo "User name: " . $user['full_name'] . "<br>";
    } else {
        echo "❌ Admin login failed<br>";
    }
    
    // Test student login
    $username = 'student1';
    $password = 'password123';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        echo "✅ Student login successful<br>";
        echo "User role: " . $user['role'] . "<br>";
        echo "User name: " . $user['full_name'] . "<br>";
    } else {
        echo "❌ Student login failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Login test failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<a href='login.php'>Go to Login Page</a>";
?> 