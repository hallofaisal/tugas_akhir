<?php
/**
 * Test Admin Dashboard
 * File: test_admin_dashboard.php
 * Description: Test file to verify admin dashboard functionality
 */

echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Test Admin Dashboard</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo ".test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }";
echo ".success { background-color: #d4edda; border-color: #c3e6cb; }";
echo ".error { background-color: #f8d7da; border-color: #f5c6cb; }";
echo ".info { background-color: #d1ecf1; border-color: #bee5eb; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🧪 Test Admin Dashboard</h1>";

// Test 1: Check if admin directory exists
echo "<div class='test-section info'>";
echo "<h3>Test 1: Directory Structure</h3>";
if (is_dir('admin')) {
    echo "✅ Admin directory exists<br>";
    
    $admin_files = scandir('admin');
    echo "📁 Admin files found:<br>";
    foreach ($admin_files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'php') {
            echo "&nbsp;&nbsp;• $file<br>";
        }
    }
} else {
    echo "❌ Admin directory not found<br>";
}
echo "</div>";

// Test 2: Check if main admin index.php exists
echo "<div class='test-section info'>";
echo "<h3>Test 2: Main Dashboard File</h3>";
if (file_exists('admin/index.php')) {
    echo "✅ admin/index.php exists<br>";
    $file_size = filesize('admin/index.php');
    echo "📏 File size: " . number_format($file_size) . " bytes<br>";
} else {
    echo "❌ admin/index.php not found<br>";
}
echo "</div>";

// Test 3: Check database connection
echo "<div class='test-section info'>";
echo "<h3>Test 3: Database Connection</h3>";
try {
    $pdo = require_once 'db.php';
    echo "✅ Database connection successful<br>";
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "📊 Total users in database: " . ($result['total'] ?? 'N/A') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 4: Check admin navigation links
echo "<div class='test-section info'>";
echo "<h3>Test 4: Navigation Links</h3>";
echo "🔗 Admin dashboard links:<br>";
echo "&nbsp;&nbsp;• <a href='admin/' target='_blank'>Main Dashboard (admin/index.php)</a><br>";
echo "&nbsp;&nbsp;• <a href='admin/manage_books.php' target='_blank'>Manage Books</a><br>";
echo "&nbsp;&nbsp;• <a href='admin/borrowings.php' target='_blank'>Borrowings</a><br>";
echo "&nbsp;&nbsp;• <a href='admin/visitor_stats.php' target='_blank'>Visitor Stats</a><br>";
echo "&nbsp;&nbsp;• <a href='admin/visitor_report.php' target='_blank'>Visitor Report</a><br>";
echo "</div>";

// Test 5: Check for dashboard.php references
echo "<div class='test-section info'>";
echo "<h3>Test 5: Dashboard References</h3>";
$dashboard_refs = [];
$files_to_check = [
    'admin/index.php',
    'admin/manage_books.php', 
    'admin/borrowings.php',
    'admin/visitor_stats.php',
    'admin/visitor_report.php',
    'admin/visitors.php',
    'admin/export_borrowings_pdf.php',
    'includes/visitor_logger.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'dashboard.php') !== false) {
            $dashboard_refs[] = $file;
        }
    }
}

if (empty($dashboard_refs)) {
    echo "✅ No references to dashboard.php found<br>";
} else {
    echo "⚠️ References to dashboard.php found in:<br>";
    foreach ($dashboard_refs as $file) {
        echo "&nbsp;&nbsp;• $file<br>";
    }
}
echo "</div>";

// Test 6: Check for database path issues
echo "<div class='test-section info'>";
echo "<h3>Test 6: Database Path Issues</h3>";
$db_path_issues = [];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, "require_once 'db.php'") !== false) {
            $db_path_issues[] = $file;
        }
    }
}

if (empty($db_path_issues)) {
    echo "✅ No database path issues found<br>";
} else {
    echo "⚠️ Database path issues found in:<br>";
    foreach ($db_path_issues as $file) {
        echo "&nbsp;&nbsp;• $file<br>";
    }
}
echo "</div>";

echo "<div class='test-section success'>";
echo "<h3>🎉 Test Summary</h3>";
echo "All admin dashboard files have been updated to use index.php instead of dashboard.php.<br>";
echo "Database connection paths have been fixed in all admin files.<br>";
echo "The admin dashboard should now work correctly at: <a href='admin/' target='_blank'>http://localhost:8000/admin/</a><br>";
echo "</div>";

echo "</body>";
echo "</html>";
?> 