<?php
/**
 * Export Borrowings Report to PDF
 * File: admin/export_borrowings_pdf.php
 * Description: Generate PDF report of book borrowings with filters
 */

// Proteksi admin
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
require_once '../includes/visitor_logger.php';
requireAdmin();

require_once '../db.php';

// Log visitor automatically
$logger = new VisitorLogger($pdo);
$logger->logVisitor('admin/export_borrowings_pdf.php');

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR bk.title LIKE ? OR bk.author LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($start_date && $end_date) {
    $where_conditions[] = "b.borrow_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
} elseif ($start_date) {
    $where_conditions[] = "b.borrow_date >= ?";
    $params[] = $start_date;
} elseif ($end_date) {
    $where_conditions[] = "b.borrow_date <= ?";
    $params[] = $end_date;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get borrowings data
try {
    if (!isset($pdo) || !$pdo) {
        $pdo = require_once '../db.php';
    }
    
    // Get borrowings
    $sql = "
        SELECT b.id, b.borrow_date, b.due_date, b.return_date, b.status, b.fine_amount, b.notes,
               u.full_name as borrower_name, u.username,
               bk.title, bk.author, bk.isbn
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        $where_clause
        ORDER BY b.borrow_date DESC, b.id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll();
    
    // Get statistics
    $stats_sql = "
        SELECT 
            COUNT(*) as total_borrowings,
            COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as active_borrowings,
            COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_borrowings,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_borrowings,
            SUM(fine_amount) as total_fines
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        $where_clause
    ";
    
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Include mPDF library
try {
    require_once '../vendor/autoload.php';
} catch (Exception $e) {
    die('Error: mPDF library tidak ditemukan. Silakan jalankan "composer install" terlebih dahulu.');
}

// Create mPDF instance
try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);
} catch (Exception $e) {
    die('Error: Gagal membuat instance mPDF. Pastikan library terinstall dengan benar.');
}

// Set document properties
$mpdf->SetTitle('Laporan Peminjaman Buku');
$mpdf->SetAuthor('Sistem Informasi Akademik');
$mpdf->SetCreator('mPDF');

// Add header
$mpdf->SetHeader('Laporan Peminjaman Buku - Sistem Informasi Akademik|' . date('d/m/Y H:i') . '|Halaman {PAGENO}');

// Add footer
$mpdf->SetFooter('Dicetak pada: ' . date('d/m/Y H:i:s'));

// Generate filter info
$filter_info = [];
if ($status_filter) {
    $status_names = [
        'borrowed' => 'Dipinjam',
        'overdue' => 'Terlambat',
        'returned' => 'Dikembalikan'
    ];
    $filter_info[] = 'Status: ' . $status_names[$status_filter];
}
if ($start_date && $end_date) {
    $filter_info[] = 'Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
} elseif ($start_date) {
    $filter_info[] = 'Dari: ' . date('d/m/Y', strtotime($start_date));
} elseif ($end_date) {
    $filter_info[] = 'Sampai: ' . date('d/m/Y', strtotime($end_date));
}
if ($search) {
    $filter_info[] = 'Pencarian: ' . $search;
}

// Create HTML content
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 10pt; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h1 { color: #2c3e50; margin: 0; font-size: 18pt; }
    .header p { color: #7f8c8d; margin: 5px 0; font-size: 10pt; }
    .stats { margin-bottom: 20px; }
    .stats table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .stats td { padding: 8px; border: 1px solid #ddd; }
    .stats .label { font-weight: bold; background-color: #f8f9fa; }
    .filter-info { margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff; }
    .filter-info p { margin: 5px 0; font-size: 9pt; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .data-table th { background-color: #007bff; color: white; padding: 8px; text-align: left; font-size: 9pt; }
    .data-table td { padding: 6px; border: 1px solid #ddd; font-size: 8pt; }
    .data-table tr:nth-child(even) { background-color: #f8f9fa; }
    .status-borrowed { color: #007bff; font-weight: bold; }
    .status-overdue { color: #dc3545; font-weight: bold; }
    .status-returned { color: #28a745; font-weight: bold; }
    .fine { color: #dc3545; font-weight: bold; }
    .no-data { text-align: center; padding: 20px; color: #6c757d; font-style: italic; }
</style>

<div class="header">
    <h1>LAPORAN PEMINJAMAN BUKU</h1>
    <p>Sistem Informasi Akademik</p>
    <p>Perpustakaan Sekolah</p>
</div>

<div class="stats">
    <table>
        <tr>
            <td class="label" width="25%">Total Peminjaman</td>
            <td width="25%">' . number_format($stats['total_borrowings']) . '</td>
            <td class="label" width="25%">Total Denda</td>
            <td width="25%">Rp ' . number_format($stats['total_fines'], 0, ',', '.') . '</td>
        </tr>
        <tr>
            <td class="label">Sedang Dipinjam</td>
            <td>' . number_format($stats['active_borrowings']) . '</td>
            <td class="label">Terlambat</td>
            <td>' . number_format($stats['overdue_borrowings']) . '</td>
        </tr>
        <tr>
            <td class="label">Dikembalikan</td>
            <td>' . number_format($stats['returned_borrowings']) . '</td>
            <td class="label">Tanggal Cetak</td>
            <td>' . date('d/m/Y H:i:s') . '</td>
        </tr>
    </table>
</div>';

// Add filter information
if (!empty($filter_info)) {
    $html .= '<div class="filter-info">';
    $html .= '<p><strong>Filter yang diterapkan:</strong></p>';
    foreach ($filter_info as $info) {
        $html .= '<p>• ' . $info . '</p>';
    }
    $html .= '</div>';
}

// Add data table
$html .= '
<table class="data-table">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="15%">Peminjam</th>
            <th width="20%">Judul Buku</th>
            <th width="12%">Penulis</th>
            <th width="10%">Tanggal Pinjam</th>
            <th width="10%">Jatuh Tempo</th>
            <th width="8%">Status</th>
            <th width="10%">Denda</th>
            <th width="10%">Catatan</th>
        </tr>
    </thead>
    <tbody>';

if (empty($borrowings)) {
    $html .= '<tr><td colspan="9" class="no-data">Tidak ada data peminjaman untuk periode yang dipilih.</td></tr>';
} else {
    foreach ($borrowings as $index => $borrowing) {
        $status_class = 'status-' . $borrowing['status'];
        $status_text = [
            'borrowed' => 'Dipinjam',
            'overdue' => 'Terlambat',
            'returned' => 'Dikembalikan',
            'lost' => 'Hilang'
        ][$borrowing['status']] ?? $borrowing['status'];
        
        $fine_text = $borrowing['fine_amount'] > 0 ? 'Rp ' . number_format($borrowing['fine_amount'], 0, ',', '.') : '-';
        $fine_class = $borrowing['fine_amount'] > 0 ? 'fine' : '';
        
        $html .= '<tr>
            <td>' . ($index + 1) . '</td>
            <td>' . htmlspecialchars($borrowing['borrower_name']) . '<br><small>(' . htmlspecialchars($borrowing['username']) . ')</small></td>
            <td>' . htmlspecialchars($borrowing['title']) . '</td>
            <td>' . htmlspecialchars($borrowing['author']) . '</td>
            <td>' . date('d/m/Y', strtotime($borrowing['borrow_date'])) . '</td>
            <td>' . date('d/m/Y', strtotime($borrowing['due_date'])) . '</td>
            <td class="' . $status_class . '">' . $status_text . '</td>
            <td class="' . $fine_class . '">' . $fine_text . '</td>
            <td>' . htmlspecialchars($borrowing['notes'] ?: '-') . '</td>
        </tr>';
    }
}

$html .= '
    </tbody>
</table>';

// Add summary at the end
$html .= '
<div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd;">
    <p><strong>Ringkasan:</strong></p>
    <ul>
        <li>Total peminjaman: ' . number_format($stats['total_borrowings']) . '</li>
        <li>Sedang dipinjam: ' . number_format($stats['active_borrowings']) . '</li>
        <li>Terlambat: ' . number_format($stats['overdue_borrowings']) . '</li>
        <li>Dikembalikan: ' . number_format($stats['returned_borrowings']) . '</li>
        <li>Total denda: Rp ' . number_format($stats['total_fines'], 0, ',', '.') . '</li>
    </ul>
</div>';

// Write HTML to PDF
$mpdf->WriteHTML($html);

// Generate filename
$filename = 'laporan_peminjaman_' . date('Y-m-d_H-i-s');
if ($start_date && $end_date) {
    $filename .= '_' . date('Y-m-d', strtotime($start_date)) . '_to_' . date('Y-m-d', strtotime($end_date));
}
$filename .= '.pdf';

// Output PDF
$mpdf->Output($filename, 'D');
exit;
?> 
