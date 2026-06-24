<?php
require_once __DIR__ . '/../config.php';
ensure_user();
if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$id = intval($_GET['id']);
$conn = db();
$stmt = $conn->prepare('SELECT b.*, l.name AS lapangan_name FROM bookings b JOIN lapangan l ON b.lapangan_id = l.id WHERE b.id = ? AND b.user_id = ?');
$stmt->bind_param('ii', $id, $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();
if (!$booking || $booking['payment_status'] !== 'unpaid') {
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate payment succeeded from gateway
    $stmt = $conn->prepare('UPDATE bookings SET payment_status = "paid", booking_status = "confirmed" WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bayar Booking</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>Bayar Booking</h1>
    <nav class="nav-bar">
        <a href="index.php">Booking</a>
        <a href="../logout.php">Logout</a>
    </nav>
    <div class="box-form">
        <p>Booking lapangan: <strong><?php echo escape($booking['lapangan_name']); ?></strong></p>
        <p>Tanggal: <strong><?php echo escape($booking['booking_date']); ?></strong></p>
        <p>Jam: <strong><?php echo escape($booking['booking_time']); ?></strong></p>
        <p>Total bayar: <strong>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></strong></p>
        <p>Metode pembayaran: <strong><?php echo escape(strtoupper($booking['payment_method'])); ?></strong></p>
        <?php
            $qrText = $booking['payment_method'] === 'dana'
                ? 'DANA:085712345678;NOMINAL=' . number_format($booking['total_price'], 0, '', '')
                : 'QRIS:PAYMENT|AMOUNT=' . number_format($booking['total_price'], 0, '', '');
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($qrText);
        ?>
        <div style="text-align:center; margin:20px 0;">
            <img src="<?php echo escape($qrUrl); ?>" alt="QR pembayaran" style="max-width:100%; border-radius:16px;">
        </div>
        <?php if ($booking['payment_method'] === 'dana'): ?>
            <p>Scan QR dengan aplikasi DANA, lalu bayar sebesar <strong>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></strong>.</p>
            <p>Jika diperlukan, gunakan nomor DANA: <strong>0857-1234-5678</strong> dengan referensi <strong>FUTSAL-<?php echo escape($booking['id']); ?></strong>.</p>
        <?php else: ?>
            <p>Scan QRIS dengan aplikasi e-wallet untuk membayar sebesar <strong>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></strong>.</p>
            <p>Gunakan referensi: <strong>FUTSAL-<?php echo escape($booking['id']); ?></strong>.</p>
        <?php endif; ?>
        <form action="pay.php?id=<?php echo escape($id); ?>" method="post">
            <button type="submit">Saya sudah bayar</button>
        </form>
    </div>
</div>
</body>
</html>