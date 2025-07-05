<?php
// Proteksi admin
require_once '../includes/middleware.php';
require_once '../includes/middleware_config.php';
requireAdmin();

require_once '../db.php';

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
            <?php else: foreach ($books as $i => $b): ?>
                <tr>
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
                        <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $b['id'] ?>"><i class="bi bi-trash"></i></button>
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
                <!-- Modal Hapus Buku -->
                <div class="modal fade" id="modalHapus<?= $b['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <form class="modal-content" method="post" action="?delete=<?= $b['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p>Yakin ingin menghapus buku <strong><?= htmlspecialchars($b['title']) ?></strong>?</p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                      </div>
                    </form>
                  </div>
                </div>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
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
</body>
</html> 