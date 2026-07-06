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
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span> Dashboard</a>
            <a href="lapangan.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'lapangan.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"/><circle cx="12" cy="12" r="3"/></svg></span> Kelola Lapangan</a>
            <a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 9h16"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M9 13h6"/></svg></span> Kelola Booking</a>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4 21c0-4.28 3.58-7.75 8-7.75s8 3.47 8 7.75"/></svg></span> Kelola Pengguna</a>
            <a href="finance.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'finance.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8h18"/><path d="M6 4h12"/><path d="M5 12h14"/><path d="M7 16h10"/><path d="M9 20h6"/></svg></span> Laporan Keuangan</a>
            <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1Z"/></svg></span> Pengaturan</a>
            <a href="../logout.php" class="logout-link"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span> Logout</a>
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
                        <a class="danger" href="lapangan.php">Batal</a>
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