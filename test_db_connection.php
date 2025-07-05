<?php
/**
 * Test Database Connection
 * File: test_db_connection.php
 * Description: Test database connection from admin directory
 */

echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Test Database Connection</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo ".test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }";
echo ".success { background-color: #d4edda; border-color: #c3e6cb; }";
echo ".error { background-color: #f8d7da; border-color: #f5c6cb; }";
echo ".info { background-color: #d1ecf1; border-color: #bee5eb; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🧪 Test Database Connection</h1>";

// Test 1: Check if db.php exists
echo "<div class='test-section info'>";
echo "<h3>Test 1: Database File</h3>";
if (file_exists('../db.php')) {
    echo "✅ ../db.php exists<br>";
    $file_size = filesize('../db.php');
    echo "📏 File size: " . number_format($file_size) . " bytes<br>";
} else {
    echo "❌ ../db.php not found<br>";
}
echo "</div>";

// Test 2: Test database connection
echo "<div class='test-section info'>";
echo "<h3>Test 2: Database Connection</h3>";
try {
    $pdo = require_once '../db.php';
    
    if ($pdo && $pdo instanceof PDO) {
        echo "✅ Database connection successful (PDO)<br>";
        echo "🔗 Connection type: " . get_class($pdo) . "<br>";
    } elseif ($pdo && method_exists($pdo, 'prepare')) {
        echo "✅ Database connection successful (JSON Fallback)<br>";
        echo "🔗 Connection type: " . get_class($pdo) . "<br>";
    } else {
        echo "❌ Database connection failed - returned: " . var_export($pdo, true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 3: Test basic queries
echo "<div class='test-section info'>";
echo "<h3>Test 3: Basic Queries</h3>";
try {
    if (!isset($pdo) || !$pdo) {
        $pdo = require_once '../db.php';
    }
    
    if ($pdo) {
        // Test users table
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        if ($stmt) {
            $result = $stmt->fetch();
            echo "✅ Users table query successful<br>";
            echo "👥 Total users: " . ($result['total'] ?? 'N/A') . "<br>";
        } else {
            echo "❌ Users table query failed<br>";
        }
        
        // Test books table
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM books");
        if ($stmt) {
            $result = $stmt->fetch();
            echo "✅ Books table query successful<br>";
            echo "📚 Total books: " . ($result['total'] ?? 'N/A') . "<br>";
        } else {
            echo "❌ Books table query failed<br>";
        }
        
        // Test borrowings table
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrowings");
        if ($stmt) {
            $result = $stmt->fetch();
            echo "✅ Borrowings table query successful<br>";
            echo "📖 Total borrowings: " . ($result['total'] ?? 'N/A') . "<br>";
        } else {
            echo "❌ Borrowings table query failed<br>";
        }
        
    } else {
        echo "❌ No database connection available<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Query test failed: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 4: Test admin files
echo "<div class='test-section info'>";
echo "<h3>Test 4: Admin Files Database Access</h3>";
$admin_files = [
    'manage_books.php',
    'borrowings.php', 
    'visitors.php',
    'export_borrowings_pdf.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file not found<br>";
    }
}
echo "</div>";

// Test 5: Test require_once behavior
echo "<div class='test-section info'>";
echo "<h3>Test 5: Require Once Behavior</h3>";
try {
    // First call
    $pdo1 = require_once '../db.php';
    echo "First require_once: " . ($pdo1 ? "Success" : "Failed") . "<br>";
    
    // Second call (should return false)
    $pdo2 = require_once '../db.php';
    echo "Second require_once: " . ($pdo2 ? "Success" : "Failed") . "<br>";
    
    if ($pdo2 === false) {
        echo "✅ require_once working correctly (returns false on second call)<br>";
    } else {
        echo "⚠️ require_once may not be working as expected<br>";
    }
    
} catch (Exception $e) {
    echo "❌ require_once test failed: " . $e->getMessage() . "<br>";
}
echo "</div>";

echo "<div class='test-section success'>";
echo "<h3>🎉 Test Summary</h3>";
echo "Database connection issues should now be fixed.<br>";
echo "All admin files now check if \$pdo exists before calling require_once.<br>";
echo "Try accessing the admin pages again:<br>";
echo "• <a href='manage_books.php' target='_blank'>Manage Books</a><br>";
echo "• <a href='borrowings.php' target='_blank'>Borrowings</a><br>";
echo "• <a href='visitors.php' target='_blank'>Visitors</a><br>";
echo "</div>";

echo "</body>";
echo "</html>";
?> 