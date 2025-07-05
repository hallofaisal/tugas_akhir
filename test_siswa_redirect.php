<?php
/**
 * Test Siswa Redirect
 * File: test_siswa_redirect.php
 * Description: Test redirect from /siswa/ to index.php
 */

echo "=== Test Siswa Redirect ===\n";

// Test 1: Check if .htaccess exists
if (file_exists('siswa/.htaccess')) {
    echo "✅ .htaccess file exists in siswa/ directory\n";
} else {
    echo "❌ .htaccess file missing in siswa/ directory\n";
}

// Test 2: Check if index.php exists
if (file_exists('siswa/index.php')) {
    echo "✅ index.php exists in siswa/ directory\n";
} else {
    echo "❌ index.php missing in siswa/ directory\n";
}

// Test 3: Check if profile.php exists
if (file_exists('siswa/profile.php')) {
    echo "✅ profile.php exists in siswa/ directory\n";
} else {
    echo "❌ profile.php missing in siswa/ directory\n";
}

// Test 4: Check if login.php exists
if (file_exists('siswa/login.php')) {
    echo "✅ login.php exists in siswa/ directory\n";
} else {
    echo "❌ login.php missing in siswa/ directory\n";
}

// Test 5: Check if borrowings.php exists
if (file_exists('siswa/borrowings.php')) {
    echo "✅ borrowings.php exists in siswa/ directory\n";
} else {
    echo "❌ borrowings.php missing in siswa/ directory\n";
}

// Test 6: Check middleware functions
require_once 'includes/middleware.php';

if (function_exists('get_current_user_data')) {
    echo "✅ get_current_user_data() function exists\n";
} else {
    echo "❌ get_current_user_data() function missing\n";
}

if (function_exists('get_student_by_user_id')) {
    echo "✅ get_student_by_user_id() function exists\n";
} else {
    echo "❌ get_student_by_user_id() function missing\n";
}

if (function_exists('requireSiswa')) {
    echo "✅ requireSiswa() function exists\n";
} else {
    echo "❌ requireSiswa() function missing\n";
}

echo "\n=== Redirect Instructions ===\n";
echo "1. Start PHP server: php -S localhost:8000\n";
echo "2. Open browser: http://localhost:8000/siswa/\n";
echo "3. This should redirect to: http://localhost:8000/siswa/index.php\n";
echo "4. If not logged in, it will redirect to login page\n";
echo "5. Login with: siswa / siswa123\n";
echo "6. After login, you should see the student dashboard\n";

echo "\n=== Available Student Pages ===\n";
echo "- Dashboard: http://localhost:8000/siswa/ (or /siswa/index.php)\n";
echo "- Login: http://localhost:8000/siswa/login.php\n";
echo "- Profile: http://localhost:8000/siswa/profile.php\n";
echo "- Borrowings: http://localhost:8000/siswa/borrowings.php\n";

echo "\n=== Test Complete ===\n";
?> 