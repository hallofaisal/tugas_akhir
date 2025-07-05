<?php
/**
 * Test file for library-focused student dashboard
 * This file simulates a logged-in student session and tests the new dashboard
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

echo "<h2>Testing Library-Focused Student Dashboard</h2>";
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

echo "<h3>New Dashboard Features:</h3>";
echo "<ul>";
echo "<li>📚 <strong>Library Statistics:</strong></li>";
echo "<ul>";
echo "<li>Peminjaman Aktif: 2</li>";
echo "<li>Buku Terlambat: 0</li>";
echo "<li>Total Dipinjam: 5</li>";
echo "<li>Buku Dibaca: 8</li>";
echo "<li>Hari Membaca: 3</li>";
echo "<li>Kategori Favorit: Pelajaran</li>";
echo "</ul>";
echo "<li>🎯 <strong>Quick Actions:</strong></li>";
echo "<ul>";
echo "<li>Peminjaman (Kelola peminjaman dan pengembalian)</li>";
echo "<li>Profil (Lihat dan edit profil)</li>";
echo "<li>Katalog (Jelajahi koleksi buku)</li>";
echo "<li>Logout (Keluar dari sistem)</li>";
echo "</ul>";
echo "<li>📋 <strong>Recent Activities:</strong> Riwayat peminjaman buku</li>";
echo "<li>👤 <strong>Student Info:</strong> NIS, Kelas, Email, Status</li>";
echo "<li>📖 <strong>Available Books:</strong> Tabel buku tersedia untuk dipinjam</li>";
echo "<li>⭐ <strong>Reading History:</strong> Riwayat membaca dengan rating bintang</li>";
echo "</ul>";

echo "<h3>Design Changes:</h3>";
echo "<ul>";
echo "<li>🎨 <strong>Admin-like Design:</strong> Layout dan styling mirip dashboard admin</li>";
echo "<li>📱 <strong>Responsive Layout:</strong> Grid system yang menyesuaikan ukuran layar</li>";
echo "<li>🔄 <strong>Hover Effects:</strong> Animasi dan transisi yang smooth</li>";
echo "<li>📊 <strong>Professional Cards:</strong> Statistik cards dengan ikon dan warna</li>";
echo "<li>🎯 <strong>Clean Interface:</strong> Desain minimalis dan modern</li>";
echo "<li>❌ <strong>Removed Academic Grades:</strong> Tidak ada lagi bagian nilai akademik</li>";
echo "<li>📚 <strong>Library Focus:</strong> Fokus pada fitur perpustakaan</li>";
echo "</ul>";

echo "<h3>Library Features:</h3>";
echo "<ul>";
echo "<li>📖 <strong>Book Management:</strong> Peminjaman, pengembalian, katalog</li>";
echo "<li>📊 <strong>Reading Analytics:</strong> Statistik membaca dan preferensi</li>";
echo "<li>⭐ <strong>Rating System:</strong> Rating bintang untuk buku yang dibaca</li>";
echo "<li>📅 <strong>Reading History:</strong> Riwayat buku yang telah dibaca</li>";
echo "<li>🔥 <strong>Reading Streak:</strong> Tracking hari membaca berturut-turut</li>";
echo "<li>❤️ <strong>Favorite Category:</strong> Kategori buku favorit</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<p>1. Go to <a href='http://localhost:8000/siswa/'>http://localhost:8000/siswa/</a></p>";
echo "<p>2. The dashboard should load without errors</p>";
echo "<p>3. You should see the new library-focused dashboard</p>";
echo "<p>4. No academic grades section should be present</p>";

echo "<h3>Color Scheme:</h3>";
echo "<ul>";
echo "<li>🟢 <strong>Primary Green:</strong> #10b981 (Welcome card, success elements)</li>";
echo "<li>🔵 <strong>Primary Blue:</strong> #3b82f6 (Primary actions, links)</li>";
echo "<li>🟡 <strong>Warning Yellow:</strong> #d97706 (Warning elements)</li>";
echo "<li>🔴 <strong>Danger Red:</strong> #dc2626 (Danger elements, logout)</li>";
echo "<li>⚪ <strong>Neutral Gray:</strong> #64748b (Text, labels)</li>";
echo "</ul>";

// Clear session for testing
// session_destroy();
?> 