<?php
require_once __DIR__ . '/../config.php';
ensure_admin();
$conn = db();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    if ($name === '' || $price <= 0) {
        $message = 'Nama lapangan dan harga harus diisi dengan benar.';
    } else {
        $stmt = $conn->prepare('INSERT INTO lapangan (name, price, description) VALUES (?, ?, ?)');
        $stmt->bind_param('sds', $name, $price, $description);
        $stmt->execute();
        $stmt->close();
        $message = 'Lapangan berhasil ditambahkan.';
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
$result = $conn->query('SELECT * FROM lapangan ORDER BY name');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Kelola Lapangan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>Kelola Lapangan</h1>
    <nav class="nav-bar">
        <a href="index.php">Dashboard</a>
        <a href="lapangan.php">Kelola Lapangan</a>
        <a href="bookings.php">Kelola Booking</a>
        <a href="../logout.php">Logout</a>
    </nav>
    <?php if ($message !== ''): ?>
        <div class="success"><?php echo escape($message); ?></div>
    <?php endif; ?>
    <section class="card-section">
        <form action="lapangan.php" method="post" class="box-form">
            <h2>Tambah Lapangan</h2>
            <label>Nama Lapangan</label>
            <input type="text" name="name" required>
            <label>Harga per Jam (Rp)</label>
            <input type="number" name="price" min="1" required>
            <label>Deskripsi</label>
            <textarea name="description"></textarea>
            <button type="submit">Simpan</button>
        </form>
        <div class="table-wrap">
            <h2>Daftar Lapangan</h2>
            <table>
                <thead>
                <tr><th>Nama</th><th>Harga</th><th>Deskripsi</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escape($row['name']); ?></td>
                        <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                        <td><?php echo escape($row['description']); ?></td>
                        <td><a class="danger" href="lapangan.php?delete=<?php echo escape($row['id']); ?>" onclick="return confirm('Hapus lapangan?');">Hapus</a></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>