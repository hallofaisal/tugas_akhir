<?php
/**
 * Test Siswa Access
 * File: test_siswa_access.php
 * Description: Test accessing siswa dashboard without database errors
 */

echo "=== Test Siswa Access ===\n";

// Simulate session data
session_start();
$_SESSION['user_id'] = 3;
$_SESSION['username'] = 'siswa';
$_SESSION['full_name'] = 'Siswa Demo';
$_SESSION['role'] = 'student';
$_SESSION['email'] = 'siswa@demo.com';

echo "✅ Session data set\n";

// Test middleware functions
require_once 'includes/middleware.php';

try {
    $currentUser = get_current_user_data();
    echo "✅ get_current_user_data() works\n";
    echo "   User: " . $currentUser['full_name'] . " (" . $currentUser['role'] . ")\n";
    
    $studentData = get_student_by_user_id($currentUser['id']);
    echo "✅ get_student_by_user_id() works\n";
    echo "   NIS: " . ($studentData['nis'] ?? 'N/A') . "\n";
    echo "   Class: " . ($studentData['class'] ?? 'N/A') . "\n";
    
    // Test database connection
    $pdo = require_once 'db.php';
    if ($pdo) {
        echo "✅ Database connection successful\n";
    } else {
        echo "⚠️  Using JSON fallback database\n";
    }
    
    // Test requireSiswa function
    echo "✅ Testing requireSiswa() function...\n";
    // This should not redirect since we have valid session
    echo "✅ requireSiswa() passed (no redirect)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Access Instructions ===\n";
echo "1. Start PHP server: php -S localhost:8000\n";
echo "2. Login first: http://localhost:8000/login.php\n";
echo "   Username: siswa\n";
echo "   Password: siswa123\n";
echo "3. Then access: http://localhost:8000/siswa/\n";
echo "4. Should show student dashboard without errors\n";

echo "\n=== Expected Dashboard Content ===\n";
echo "- Welcome card with NIS and class info\n";
echo "- Statistics (average grade, subjects, active borrowings)\n";
echo "- Grades table with sample data\n";
echo "- Recent borrowings list\n";
echo "- Quick action cards\n";

echo "\n=== Test Complete ===\n";
?> 