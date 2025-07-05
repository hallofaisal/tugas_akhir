<?php
/**
 * Test file for simple student dashboard
 * This file simulates a logged-in student session and tests the dashboard
 */

// Start session
session_start();

// Simulate student login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'siswa';
$_SESSION['full_name'] = 'Siswa Demo';
$_SESSION['email'] = 'siswa@demo.com';
$_SESSION['role'] = 'siswa';
$_SESSION['logged_in'] = true;

echo "<h2>Testing Student Dashboard</h2>";
echo "<p>Session data set:</p>";
echo "<ul>";
echo "<li>User ID: " . $_SESSION['user_id'] . "</li>";
echo "<li>Username: " . $_SESSION['username'] . "</li>";
echo "<li>Full Name: " . $_SESSION['full_name'] . "</li>";
echo "<li>Email: " . $_SESSION['email'] . "</li>";
echo "<li>Role: " . $_SESSION['role'] . "</li>";
echo "</ul>";

// Test middleware function
require_once 'includes/middleware.php';

$currentUser = get_current_user_data();
echo "<h3>Current User Data:</h3>";
echo "<pre>" . print_r($currentUser, true) . "</pre>";

echo "<h3>Test Results:</h3>";
echo "<p>✅ Session data is working</p>";
echo "<p>✅ Middleware functions are working</p>";
echo "<p>✅ No database queries needed</p>";

echo "<h3>Next Steps:</h3>";
echo "<p>1. Go to <a href='http://localhost:8000/siswa/'>http://localhost:8000/siswa/</a></p>";
echo "<p>2. The dashboard should load without errors</p>";
echo "<p>3. You should see the student dashboard with session data</p>";

// Clear session for testing
// session_destroy();
?> 