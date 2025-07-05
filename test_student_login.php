<?php
/**
 * Test Student Login
 * File: test_student_login.php
 * Description: Test student login functionality
 */

echo "=== Test Student Login ===\n";

// Include database connection
$pdo = require_once 'db.php';

if (!$pdo) {
    echo "❌ Database connection failed\n";
    exit;
}

echo "✅ Database connection successful\n";

// Test accounts to check
$test_accounts = [
    ['username' => 'siswa', 'password' => 'siswa123'],
    ['username' => 'student1', 'password' => 'password123'],
    ['username' => 'admin', 'password' => 'admin123']
];

foreach ($test_accounts as $account) {
    echo "\n--- Testing: {$account['username']} ---\n";
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$account['username']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "❌ User not found: {$account['username']}\n";
            continue;
        }
        
        echo "✅ User found: {$user['username']}\n";
        echo "   Role: {$user['role']}\n";
        echo "   Full Name: {$user['full_name']}\n";
        echo "   Email: {$user['email']}\n";
        
        // Test password verification
        if (password_verify($account['password'], $user['password'])) {
            echo "✅ Password correct\n";
            
            // Test role-based redirect
            if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
                echo "   → Would redirect to: admin/\n";
            } elseif ($user['role'] === 'student') {
                echo "   → Would redirect to: siswa/\n";
            } else {
                echo "   → Would redirect to: index.php\n";
            }
        } else {
            echo "❌ Password incorrect\n";
            echo "   Expected: {$account['password']}\n";
            echo "   Hash: " . substr($user['password'], 0, 20) . "...\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Test creating a new student account
echo "\n--- Creating Test Student Account ---\n";

try {
    // Check if 'siswa' account exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'siswa'");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if (!$existing) {
        echo "Creating 'siswa' account...\n";
        
        // Create user account
        $hashed_password = password_hash('siswa123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, email, full_name, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'student', 'active', NOW())
        ");
        $stmt->execute(['siswa', $hashed_password, 'siswa@demo.com', 'Siswa Demo']);
        
        $user_id = $pdo->lastInsertId();
        echo "✅ User created with ID: $user_id\n";
        
        // Create student record
        $stmt = $pdo->prepare("
            INSERT INTO students (
                user_id, nis, full_name, birth_place, birth_date, gender, 
                address, phone, parent_name, parent_phone, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([
            $user_id, '2024001', 'Siswa Demo', 'Jakarta', '2006-01-01', 
            'L', 'Jl. Demo No. 1', '08123456789', 'Orang Tua Demo', '08123456788'
        ]);
        
        echo "✅ Student record created\n";
    } else {
        echo "✅ 'siswa' account already exists\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating account: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Now try logging in with:\n";
echo "Username: siswa\n";
echo "Password: siswa123\n";
?> 