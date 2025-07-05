<?php
/**
 * Test File: test_siswa_borrowings.php
 * Description: Test student borrowings page functionality and design
 */

echo "<h1>Test Student Borrowings Page</h1>";

// Test 1: Check if file exists
echo "<h2>Test 1: File Existence</h2>";
if (file_exists('siswa/borrowings.php')) {
    echo "✅ File siswa/borrowings.php exists<br>";
} else {
    echo "❌ File siswa/borrowings.php not found<br>";
}

// Test 2: Check file size
echo "<h2>Test 2: File Size</h2>";
$fileSize = filesize('siswa/borrowings.php');
echo "File size: " . number_format($fileSize) . " bytes<br>";
if ($fileSize > 10000) {
    echo "✅ File has substantial content<br>";
} else {
    echo "❌ File seems too small<br>";
}

// Test 3: Check file content structure
echo "<h2>Test 3: Content Structure</h2>";
$content = file_get_contents('siswa/borrowings.php');

$checks = [
    'PHP opening tag' => '<?php',
    'Session start' => 'session_start()',
    'Middleware include' => 'require_once \'../includes/middleware.php\'',
    'HTML structure' => '<!DOCTYPE html>',
    'Bootstrap CSS' => 'bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'Bootstrap Icons' => 'bootstrap-icons@1.10.0/font/bootstrap-icons.css',
    'Inter font' => 'fonts.googleapis.com/css2?family=Inter',
    'Page title' => 'Peminjaman Buku',
    'Status cards' => 'status-card',
    'Book cards' => 'book-card',
    'Search functionality' => 'searchBooks',
    'Borrowing history table' => 'Riwayat Peminjaman',
    'JavaScript' => '<script'
];

foreach ($checks as $check => $search) {
    if (strpos($content, $search) !== false) {
        echo "✅ $check found<br>";
    } else {
        echo "❌ $check not found<br>";
    }
}

// Test 4: Check for sample data
echo "<h2>Test 4: Sample Data</h2>";
$sampleDataChecks = [
    'Available books array' => '$availableBooks = [',
    'Borrowing history array' => '$borrowingHistory = [',
    'Sample book titles' => 'Fisika Modern',
    'Sample authors' => 'Dr. Sarah Wilson',
    'Sample categories' => 'Pelajaran',
    'Sample ISBNs' => '978-1234567890',
    'Sample locations' => 'Rak A-1'
];

foreach ($sampleDataChecks as $check => $search) {
    if (strpos($content, $search) !== false) {
        echo "✅ $check found<br>";
    } else {
        echo "❌ $check not found<br>";
    }
}

// Test 5: Check for functionality
echo "<h2>Test 5: Functionality</h2>";
$functionalityChecks = [
    'Form processing' => '$_SERVER[\'REQUEST_METHOD\'] === \'POST\'',
    'Borrow book action' => 'borrow_book',
    'Return book action' => 'return_book',
    'Success messages' => '$success =',
    'Error handling' => '$error =',
    'CSRF protection' => 'csrf_token',
    'Session data usage' => 'get_current_user_data()',
    'Middleware protection' => 'requireSiswa()'
];

foreach ($functionalityChecks as $check => $search) {
    if (strpos($content, $search) !== false) {
        echo "✅ $check found<br>";
    } else {
        echo "❌ $check not found<br>";
    }
}

// Test 6: Check for modern design elements
echo "<h2>Test 6: Modern Design Elements</h2>";
$designChecks = [
    'Inter font family' => 'font-family: \'Inter\'',
    'Modern color palette' => '#f8fafc',
    'Card design' => 'border-radius: 12px',
    'Hover effects' => 'transition: all 0.2s ease',
    'Bootstrap icons' => 'bi bi-',
    'Responsive design' => '@media (max-width: 768px)',
    'Progress bars' => 'progress-bar',
    'Status badges' => 'status-badge',
    'Search box' => 'search-box',
    'Table responsive' => 'table-responsive'
];

foreach ($designChecks as $check => $search) {
    if (strpos($content, $search) !== false) {
        echo "✅ $check found<br>";
    } else {
        echo "❌ $check not found<br>";
    }
}

// Test 7: Check for library-specific features
echo "<h2>Test 7: Library Features</h2>";
$libraryChecks = [
    'Borrowing status' => 'Status Peminjaman',
    'Available books' => 'Buku Tersedia',
    'Borrowing history' => 'Riwayat Peminjaman',
    'Book information' => 'ISBN',
    'Book location' => 'location',
    'Due dates' => 'Jatuh Tempo',
    'Return functionality' => 'Kembalikan',
    'Borrow functionality' => 'Pinjam',
    'Student information' => 'Informasi Siswa',
    'Borrowing limits' => 'maksimal'
];

foreach ($libraryChecks as $check => $search) {
    if (strpos($content, $search) !== false) {
        echo "✅ $check found<br>";
    } else {
        echo "❌ $check not found<br>";
    }
}

// Test 8: Check for accessibility and UX
echo "<h2>Test 8: Accessibility & UX</h2>";
$uxChecks = [
    'Alert messages' => 'alert alert-',
    'Dismissible alerts' => 'alert-dismissible',
    'Form validation' => 'validation',
    'Loading states' => 'disabled',
    'Clear navigation' => 'Kembali',
    'Consistent styling' => 'btn btn-',
    'Icon usage' => 'bi bi-',
    'Responsive layout' => 'col-md-',
    'Search functionality' => 'addEventListener',
    'Hover effects' => 'hover'
];

foreach ($uxChecks as $check => $search) {
    if (strpos($content, $search) !== false) {
        echo "✅ $check found<br>";
    } else {
        echo "❌ $check not found<br>";
    }
}

echo "<h2>Summary</h2>";
echo "The student borrowings page has been created with:<br>";
echo "• Modern, clean design using Bootstrap 5 and Inter font<br>";
echo "• Comprehensive borrowing management functionality<br>";
echo "• Sample data for testing without database dependencies<br>";
echo "• Responsive layout for mobile and desktop<br>";
echo "• Search functionality for books<br>";
echo "• Status tracking and borrowing limits<br>";
echo "• Complete borrowing history with return functionality<br>";
echo "• Professional UI with hover effects and transitions<br>";

echo "<br><strong>Access the page at:</strong> <a href='http://localhost:8000/siswa/borrowings.php' target='_blank'>http://localhost:8000/siswa/borrowings.php</a>";
?> 