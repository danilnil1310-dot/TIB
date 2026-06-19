<?php
require_once __DIR__ . '/../config.php';
ensure_admin();
$conn = db();
$message = '';
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'confirm') {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid' WHERE id = ?");
        $message = 'Booking telah dikonfirmasi dan dibayar.';
    } elseif ($_GET['action'] === 'cancel') {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'canceled' WHERE id = ?");
        $message = 'Booking dibatalkan.';
    }
    if (isset($stmt)) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}
$sql = 'SELECT b.*, u.name AS user_name, l.name AS lapangan_name FROM bookings b JOIN users u ON b.user_id = u.id JOIN lapangan l ON b.lapangan_id = l.id ORDER BY b.booking_date DESC, b.booking_time DESC';
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Booking</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>Kelola Booking</h1>
    <nav class="nav-bar">
        <a href="index.php">Dashboard</a>
        <a href="lapangan.php">Kelola Lapangan</a>
        <a href="bookings.php">Kelola Booking</a>
        <a href="../logout.php">Logout</a>
    </nav>
    <?php if ($message !== ''): ?>
        <div class="success"><?php echo escape($message); ?></div>
    <?php endif; ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>User</th><th>Lapangan</th><th>Tanggal</th><th>Jam</th><th>Durasi</th><th>Total</th><th>Status</th><th>Pembayaran</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo escape($row['user_name']); ?></td>
                    <td><?php echo escape($row['lapangan_name']); ?></td>
                    <td><?php echo escape($row['booking_date']); ?></td>
                    <td><?php echo escape($row['booking_time']); ?></td>
                    <td><?php echo escape($row['duration']); ?> jam</td>
                    <td>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                    <td><?php echo escape(ucfirst($row['booking_status'])); ?></td>
                    <td><?php echo escape(ucfirst($row['payment_status'])); ?></td>
                    <td>
                        <?php if ($row['booking_status'] === 'pending'): ?>
                            <a href="bookings.php?action=confirm&id=<?php echo escape($row['id']); ?>">Konfirmasi</a>
                            <a class="danger" href="bookings.php?action=cancel&id=<?php echo escape($row['id']); ?>" onclick="return confirm('Batalkan booking?');">Batalkan</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>