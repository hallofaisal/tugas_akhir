<?php
session_start();
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
requireSiswa();

// Sample available books
$availableBooks = [
    [
        'title' => 'Fisika Modern',
        'author' => 'Dr. Sarah Wilson',
        'category' => 'Pelajaran',
        'available_copies' => 3
    ],
    [
        'title' => 'Kimia Dasar',
        'author' => 'Prof. Michael Brown',
        'category' => 'Pelajaran',
        'available_copies' => 2
    ],
    [
        'title' => 'Biologi Sel',
        'author' => 'Dr. Lisa Chen',
        'category' => 'Pelajaran',
        'available_copies' => 4
    ],
    [
        'title' => 'Ekonomi Mikro',
        'author' => 'Prof. John Smith',
        'category' => 'Pelajaran',
        'available_copies' => 1
    ],
    [
        'title' => 'Sejarah Indonesia',
        'author' => 'Dr. Budi Santoso',
        'category' => 'Pelajaran',
        'available_copies' => 2
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background: #f8fafc; color: #334155; }
        .page-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 1.5rem 0; margin-bottom: 2rem; }
        .page-title { font-size: 1.875rem; font-weight: 600; color: #1e293b; margin: 0; }
        .search-box { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem; margin-bottom: 2rem; }
        .search-input { border: none; outline: none; width: 100%; font-size: 0.95rem; }
        .search-input::placeholder { color: #9ca3af; }
        .book-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; transition: all 0.2s ease; height: 100%; }
        .book-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59,130,246,0.1); transform: translateY(-2px); }
        .badge-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title"><i class="bi bi-search me-2"></i>Katalog Buku</h1>
                </div>
                <div>
                    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="search-box">
            <div class="d-flex align-items-center">
                <i class="bi bi-search text-muted me-2"></i>
                <input type="text" class="search-input" placeholder="Cari judul buku, penulis, atau kategori..." id="searchBooks">
            </div>
        </div>
        <div class="row" id="booksContainer">
            <?php foreach ($availableBooks as $book): ?>
                <div class="col-md-6 col-lg-4 mb-3 book-item">
                    <div class="book-card">
                        <h6 class="fw-semibold mb-1"><?= htmlspecialchars($book['title']) ?></h6>
                        <p class="text-muted small mb-2"><i class="bi bi-person me-1"></i><?= htmlspecialchars($book['author']) ?></p>
                        <p class="text-muted small mb-2"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($book['category']) ?></p>
                        <span class="badge badge-success mb-2"><?= $book['available_copies'] ?> tersedia</span>
                        <div class="mt-2">
                            <a href="borrowings.php" class="btn btn-sm btn-success"><i class="bi bi-plus-circle me-1"></i>Pinjam</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchBooks').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const bookItems = document.querySelectorAll('.book-item');
            bookItems.forEach(item => {
                const title = item.querySelector('h6').textContent.toLowerCase();
                const author = item.querySelector('.text-muted').textContent.toLowerCase();
                const category = item.querySelectorAll('.text-muted')[1].textContent.toLowerCase();
                if (title.includes(searchTerm) || author.includes(searchTerm) || category.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 