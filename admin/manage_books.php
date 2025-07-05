<?php
// Proteksi admin
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
requireAdmin();

require_once '../db.php';

$success = $error = '';

// Proses Tambah Buku
if (isset($_GET['add']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year = intval($_POST['publication_year'] ?? 0);
    $total = intval($_POST['total_copies'] ?? 1);
    $available = intval($_POST['available_copies'] ?? $total);
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($title === '' || $author === '' || $total < 1) {
        $error = 'Judul, penulis, dan stok total wajib diisi.';
    } elseif ($year && ($year < 1900 || $year > 2100)) {
        $error = 'Tahun tidak valid.';
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO books (isbn, title, author, publisher, publication_year, category, total_copies, available_copies, location, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$isbn, $title, $author, $publisher, $year ?: null, $category, $total, $available, $location]);
            $success = 'Buku berhasil ditambahkan.';
            header('Location: manage_books.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menambah buku: ' . $e->getMessage();
        }
    }
}

// Proses Edit Buku
if (isset($_GET['edit']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_GET['edit']);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year = intval($_POST['publication_year'] ?? 0);
    $total = intval($_POST['total_copies'] ?? 1);
    $available = intval($_POST['available_copies'] ?? $total);
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validasi
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($title === '' || $author === '' || $total < 1) {
        $error = 'Judul, penulis, dan stok total wajib diisi.';
    } elseif ($year && ($year < 1900 || $year > 2100)) {
        $error = 'Tahun tidak valid.';
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE books SET isbn=?, title=?, author=?, publisher=?, publication_year=?, category=?, total_copies=?, available_copies=?, location=? WHERE id=?");
            $stmt->execute([$isbn, $title, $author, $publisher, $year ?: null, $category, $total, $available, $location, $id]);
            $success = 'Buku berhasil diupdate.';
            header('Location: manage_books.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal mengupdate buku: ' . $e->getMessage();
        }
    }
}

// Proses Hapus Buku
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_GET['delete']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(generateCSRFToken(), $csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("DELETE FROM books WHERE id=?");
            $stmt->execute([$id]);
            $success = 'Buku berhasil dihapus.';
            header('Location: manage_books.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menghapus buku: ' . $e->getMessage();
        }
    }
}

// Ambil daftar buku
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM books ORDER BY id DESC");
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $books = [];
    $error = 'Gagal mengambil data buku.';
}

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .table-responsive { margin-top: 2rem; }
        .modal-header { background: #0d6efd; color: #fff; }
        .btn-action { margin-right: 0.25rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4"><i class="bi bi-book"></i> Manajemen Buku</h1>
    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-circle"></i> Tambah Buku</button>
    </div>
    
    <!-- Search Box -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control" placeholder="Cari buku berdasarkan judul, penulis, ISBN, atau kategori..." onkeyup="filterBooks()">
                <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex justify-content-end">
                <span class="text-muted" id="searchResults">Menampilkan semua buku</span>
            </div>
        </div>
    </div>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle bg-white">
            <thead class="table-primary">
                <tr>
                    <th>#</th>
                    <th>ISBN</th>
                    <th>Judul</th>
                    <th>Penulis</th>
                    <th>Penerbit</th>
                    <th>Tahun</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Lokasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($books)): ?>
                <tr><td colspan="10" class="text-center">Tidak ada data buku.</td></tr>
            <?php else: ?>
                <tr id="noResultsRow" style="display: none;"><td colspan="10" class="text-center text-muted">Tidak ada buku yang cocok dengan pencarian.</td></tr>
                <?php foreach ($books as $i => $b): ?>
                <tr class="book-row" data-title="<?= htmlspecialchars(strtolower($b['title'])) ?>" data-author="<?= htmlspecialchars(strtolower($b['author'])) ?>" data-isbn="<?= htmlspecialchars(strtolower($b['isbn'])) ?>" data-category="<?= htmlspecialchars(strtolower($b['category'])) ?>" data-publisher="<?= htmlspecialchars(strtolower($b['publisher'])) ?>">
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($b['isbn']) ?></td>
                    <td><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['author']) ?></td>
                    <td><?= htmlspecialchars($b['publisher']) ?></td>
                    <td><?= htmlspecialchars($b['publication_year']) ?></td>
                    <td><?= htmlspecialchars($b['category']) ?></td>
                    <td><?= htmlspecialchars($b['available_copies']) ?>/<?= htmlspecialchars($b['total_copies']) ?></td>
                    <td><?= htmlspecialchars($b['location']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $b['id'] ?>"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger btn-action" onclick="confirmDelete(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['title'])) ?>')"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <!-- Modal Edit Buku -->
                <div class="modal fade" id="modalEdit<?= $b['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <form class="modal-content" method="post" action="?edit=<?= $b['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit Buku</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-2">
                          <label class="form-label">ISBN</label>
                          <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($b['isbn']) ?>" required>
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Judul</label>
                          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($b['title']) ?>" required>
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Penulis</label>
                          <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($b['author']) ?>" required>
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Penerbit</label>
                          <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($b['publisher']) ?>">
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Tahun</label>
                          <input type="number" name="publication_year" class="form-control" value="<?= htmlspecialchars($b['publication_year']) ?>" min="1900" max="2100">
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Kategori</label>
                          <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($b['category']) ?>">
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Stok Total</label>
                          <input type="number" name="total_copies" class="form-control" value="<?= htmlspecialchars($b['total_copies']) ?>" min="1">
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Stok Tersedia</label>
                          <input type="number" name="available_copies" class="form-control" value="<?= htmlspecialchars($b['available_copies']) ?>" min="0">
                        </div>
                        <div class="mb-2">
                          <label class="form-label">Lokasi</label>
                          <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($b['location']) ?>">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                      </div>
                    </form>
                  </div>
                </div>

            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Hidden form for delete action -->
    <form id="deleteForm" method="post" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    </form>
    
    <!-- Modal Tambah Buku -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
      <div class="modal-dialog">
        <form class="modal-content" method="post" action="?add=1">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Buku</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">ISBN</label>
              <input type="text" name="isbn" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Judul</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Penulis</label>
              <input type="text" name="author" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Penerbit</label>
              <input type="text" name="publisher" class="form-control">
            </div>
            <div class="mb-2">
              <label class="form-label">Tahun</label>
              <input type="number" name="publication_year" class="form-control" min="1900" max="2100">
            </div>
            <div class="mb-2">
              <label class="form-label">Kategori</label>
              <input type="text" name="category" class="form-control">
            </div>
            <div class="mb-2">
              <label class="form-label">Stok Total</label>
              <input type="number" name="total_copies" class="form-control" min="1" value="1">
            </div>
            <div class="mb-2">
              <label class="form-label">Stok Tersedia</label>
              <input type="number" name="available_copies" class="form-control" min="0" value="1">
            </div>
            <div class="mb-2">
              <label class="form-label">Lokasi</label>
              <input type="text" name="location" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Tambah Buku</button>
          </div>
        </form>
      </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(bookId, bookTitle) {
    if (confirm('Yakin ingin menghapus buku "' + bookTitle + '"?\n\nTindakan ini tidak dapat dibatalkan.')) {
        const form = document.getElementById('deleteForm');
        form.action = '?delete=' + bookId;
        form.submit();
    }
}

// Real-time search functionality
function filterBooks() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const bookRows = document.querySelectorAll('.book-row');
    const noResultsRow = document.getElementById('noResultsRow');
    const searchResults = document.getElementById('searchResults');
    
    let visibleCount = 0;
    let totalCount = bookRows.length;
    
    bookRows.forEach((row, index) => {
        const title = row.getAttribute('data-title') || '';
        const author = row.getAttribute('data-author') || '';
        const isbn = row.getAttribute('data-isbn') || '';
        const category = row.getAttribute('data-category') || '';
        const publisher = row.getAttribute('data-publisher') || '';
        
        // Check if search term matches any field
        const matches = title.includes(searchTerm) || 
                       author.includes(searchTerm) || 
                       isbn.includes(searchTerm) || 
                       category.includes(searchTerm) || 
                       publisher.includes(searchTerm);
        
        if (matches || searchTerm === '') {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    if (visibleCount === 0 && searchTerm !== '') {
        noResultsRow.style.display = '';
    } else {
        noResultsRow.style.display = 'none';
    }
    
    // Update results counter
    if (searchTerm === '') {
        searchResults.textContent = `Menampilkan semua buku (${totalCount})`;
    } else {
        searchResults.textContent = `Menampilkan ${visibleCount} dari ${totalCount} buku`;
    }
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    filterBooks();
}

// Add keyboard shortcuts
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    
    // Focus search box when pressing Ctrl+F
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
        }
    });
    
    // Clear search when pressing Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            clearSearch();
        }
    });
});
</script>
</body>
</html> 