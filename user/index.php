<?php
require_once __DIR__ . '/../config.php';
ensure_user();
$conn = db();
$fields = $conn->query('SELECT * FROM lapangan ORDER BY name');
$bookings = $conn->prepare('SELECT b.*, l.name AS lapangan_name FROM bookings b JOIN lapangan l ON b.lapangan_id = l.id WHERE b.user_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC');
$bookings->bind_param('i', $_SESSION['user']['id']);
$bookings->execute();
$bookingsResult = $bookings->get_result();
$bookings->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>User - Booking Futsal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>Booking Lapangan</h1>
    <p>Halo, <?php echo escape($_SESSION['user']['name']); ?></p>
    <nav class="nav-bar">
        <a href="index.php">Booking</a>
        <a href="../logout.php">Logout</a>
    </nav>
    <section class="card-section">
        <div class="box-form">
            <h2>Form Booking</h2>
            <form action="book.php" method="post">
                <label>Lapangan</label>
                <select name="lapangan_id" required>
                    <option value="">Pilih lapangan</option>
                    <?php while ($field = $fields->fetch_assoc()): ?>
                        <option value="<?php echo escape($field['id']); ?>"><?php echo escape($field['name']); ?> - Rp <?php echo number_format($field['price'], 0, ',', '.'); ?>/jam</option>
                    <?php endwhile; ?>
                </select>
                <label>Tanggal</label>
                <input type="date" name="booking_date" required>
                <label>Jam</label>
                <input type="time" name="booking_time" required>
                <label>Durasi (jam)</label>
                <input type="number" name="duration" min="1" max="6" value="1" required>
                <button type="submit">Pesan Sekarang</button>
            </form>
        </div>
        <div class="table-wrap">
            <h2>Riwayat Booking</h2>
            <table>
                <thead>
                <tr><th>Lapangan</th><th>Tanggal</th><th>Jam</th><th>Durasi</th><th>Total</th><th>Status</th><th>Pembayaran</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php while ($row = $bookingsResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escape($row['lapangan_name']); ?></td>
                        <td><?php echo escape($row['booking_date']); ?></td>
                        <td><?php echo escape($row['booking_time']); ?></td>
                        <td><?php echo escape($row['duration']); ?> jam</td>
                        <td>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                        <td><?php echo escape(ucfirst($row['booking_status'])); ?></td>
                        <td><?php echo escape(ucfirst($row['payment_status'])); ?></td>
                        <td>
                            <?php if ($row['payment_status'] === 'unpaid' && $row['booking_status'] === 'pending'): ?>
                                <a href="pay.php?id=<?php echo escape($row['id']); ?>">Bayar</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>