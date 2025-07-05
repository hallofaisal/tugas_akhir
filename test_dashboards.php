<?php
session_start();

echo "<h1>Test Dashboard Access</h1>";
echo "<p>Testing database connection and user creation...</p>";

try {
    $pdo = require_once 'db.php';
    echo "✅ Database connection successful<br>";
    
    // Test all user roles
    $testUsers = [
        ['admin', 'admin123', 'admin'],
        ['student1', 'password123', 'student'],
        ['teacher1', 'password123', 'teacher']
    ];
    
    foreach ($testUsers as $user) {
        $username = $user[0];
        $password = $user[1];
        $expectedRole = $user[2];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $userData = $stmt->fetch();
        
        if ($userData && password_verify($password, $userData['password'])) {
            echo "✅ Login successful for {$username} (Role: {$userData['role']})<br>";
            
            // Test redirect logic
            $role = $userData['role'];
            $redirectUrl = '';
            
            if ($role === 'admin' || $role === 'teacher') {
                $redirectUrl = 'admin/';
            } elseif ($role === 'student') {
                $redirectUrl = 'siswa/';
            } else {
                $redirectUrl = 'index.php';
            }
            
            echo "   → Would redirect to: {$redirectUrl}<br>";
            
            // Check if dashboard file exists
            if ($role === 'student') {
                $dashboardFile = 'siswa/index.php';
                if (file_exists($dashboardFile)) {
                    echo "   ✅ Dashboard file exists: {$dashboardFile}<br>";
                } else {
                    echo "   ❌ Dashboard file missing: {$dashboardFile}<br>";
                }
            } elseif ($role === 'admin' || $role === 'teacher') {
                $dashboardFile = 'admin/index.php';
                if (file_exists($dashboardFile)) {
                    echo "   ✅ Dashboard file exists: {$dashboardFile}<br>";
                } else {
                    echo "   ❌ Dashboard file missing: {$dashboardFile}<br>";
                }
            }
            
        } else {
            echo "❌ Login failed for {$username}<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Dashboard Links:</h3>";
    echo "<ul>";
    echo "<li><a href='admin/' target='_blank'>Admin/Teacher Dashboard</a></li>";
    echo "<li><a href='siswa/' target='_blank'>Student Dashboard</a></li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>Test Login:</h3>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?> 