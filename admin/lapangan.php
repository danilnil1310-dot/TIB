<?php
require_once __DIR__ . '/../config.php';
ensure_admin();
$conn = db();
$hasImageColumn = $conn->query("SHOW COLUMNS FROM lapangan LIKE 'image'")->num_rows > 0;
if (!$hasImageColumn) {
    $conn->query("ALTER TABLE lapangan ADD COLUMN image VARCHAR(255) NULL");
    $hasImageColumn = true;
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editIdPost = intval($_POST['edit_id'] ?? 0);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $image = trim($_POST['image'] ?? '');
    if ($name === '' || $price <= 0) {
        $message = 'Nama lapangan dan harga harus diisi dengan benar.';
    } else {
        if ($editIdPost > 0) {
            if ($hasImageColumn) {
                $stmt = $conn->prepare('UPDATE lapangan SET name = ?, price = ?, description = ?, image = ? WHERE id = ?');
                $stmt->bind_param('sdssi', $name, $price, $description, $image, $editIdPost);
            } else {
                $stmt = $conn->prepare('UPDATE lapangan SET name = ?, price = ?, description = ? WHERE id = ?');
                $stmt->bind_param('sdsi', $name, $price, $description, $editIdPost);
            }
            $message = 'Lapangan berhasil diperbarui.';
        } else {
            if ($hasImageColumn) {
                $stmt = $conn->prepare('INSERT INTO lapangan (name, price, description, image) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('sdss', $name, $price, $description, $image);
            } else {
                $stmt = $conn->prepare('INSERT INTO lapangan (name, price, description) VALUES (?, ?, ?)');
                $stmt->bind_param('sds', $name, $price, $description);
            }
            $message = 'Lapangan berhasil ditambahkan.';
        }
        $stmt->execute();
        $stmt->close();
    }
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare('DELETE FROM lapangan WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $message = 'Lapangan berhasil dihapus.';
}
$editLapangan = null;
$editId = 0;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare('SELECT * FROM lapangan WHERE id = ?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editLapangan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$result = $conn->query('SELECT * FROM lapangan ORDER BY name');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Kelola Lapangan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-page">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <h2>Futsal Admin</h2>
            <p>Panel manajemen</p>
        </div>
        <nav class="admin-nav">
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"><span class="nav-icon">🏠</span> Dashboard</a>
            <a href="lapangan.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'lapangan.php' ? 'active' : ''; ?>"><span class="nav-icon">🏟️</span> Kelola Lapangan</a>
            <a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>"><span class="nav-icon">📅</span> Kelola Booking</a>
            <a href="../logout.php" class="logout-link"><span class="nav-icon">🚪</span> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="container admin-content">
            <header class="admin-header">
                <h1>Kelola Lapangan</h1>
                <p>Tambah, edit, atau hapus data lapangan.</p>
            </header>
            <?php if ($message !== ''): ?>
                <div class="success"><?php echo escape($message); ?></div>
            <?php endif; ?>
            <section class="card-section">
                <form action="lapangan.php" method="post" class="box-form">
                    <h2><?php echo $editLapangan ? 'Edit Lapangan' : 'Tambah Lapangan'; ?></h2>
                    <input type="hidden" name="edit_id" value="<?php echo escape($editLapangan['id'] ?? 0); ?>">
                    <label>Nama Lapangan</label>
                    <input type="text" name="name" value="<?php echo escape($editLapangan['name'] ?? ''); ?>" required>
                    <label>Harga per Jam (Rp)</label>
                    <input type="number" name="price" min="1" value="<?php echo escape($editLapangan['price'] ?? ''); ?>" required>
                    <label>Deskripsi</label>
                    <textarea name="description"><?php echo escape($editLapangan['description'] ?? ''); ?></textarea>
                    <?php if ($hasImageColumn): ?>
                        <label>URL Gambar</label>
                        <input type="text" name="image" placeholder="https://..." value="<?php echo escape($editLapangan['image'] ?? ''); ?>">
                    <?php endif; ?>
                    <button type="submit"><?php echo $editLapangan ? 'Perbarui' : 'Simpan'; ?></button>
                    <?php if ($editLapangan): ?>
                        <a class="danger" href="lapangan.php" style="display:inline-block;margin-top:12px;">Batal</a>
                    <?php endif; ?>
                </form>
                <div class="table-wrap">
                    <h2>Daftar Lapangan</h2>
                    <table>
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>Deskripsi</th>
                            <?php if ($hasImageColumn): ?><th>Gambar</th><?php endif; ?>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo escape($row['name']); ?></td>
                                <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                                <td><?php echo escape($row['description']); ?></td>
                                <?php if ($hasImageColumn): ?>
                                    <td>
                                        <img class="lapangan-thumb" src="<?php echo escape($row['image'] ?: 'https://images.unsplash.com/photo-1505842465776-3bd2144b5caa?auto=format&fit=crop&w=400&q=80'); ?>" alt="<?php echo escape($row['name']); ?>">
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <a href="lapangan.php?edit=<?php echo escape($row['id']); ?>">Edit</a> |
                                    <a class="danger" href="lapangan.php?delete=<?php echo escape($row['id']); ?>" onclick="return confirm('Hapus lapangan?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>