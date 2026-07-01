<?php
require_once __DIR__ . '/../config.php';
ensure_user();
if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$id = intval($_GET['id']);
$conn = db();
ensure_booking_payment_schema($conn);
expire_unpaid_bookings($conn);
$stmt = $conn->prepare('SELECT b.*, l.name AS lapangan_name FROM bookings b JOIN lapangan l ON b.lapangan_id = l.id WHERE b.id = ? AND b.user_id = ?');
$stmt->bind_param('ii', $id, $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();
if ($booking && $booking['payment_status'] === 'menunggu_pembayaran' && !empty($booking['payment_expires_at']) && strtotime($booking['payment_expires_at']) < time()) {
    $conn->query("UPDATE bookings SET payment_status = 'kadaluarsa', booking_status = 'canceled' WHERE id = " . intval($id));
    $booking['payment_status'] = 'kadaluarsa';
    $booking['booking_status'] = 'canceled';
}
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    $cancelStmt = $conn->prepare('UPDATE bookings SET booking_status = "canceled", payment_status = "gagal" WHERE id = ? AND user_id = ? AND booking_status = "pending" AND payment_status = "menunggu_pembayaran"');
    $cancelStmt->bind_param('ii', $id, $_SESSION['user']['id']);
    $cancelStmt->execute();
    $cancelStmt->close();
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
if (!$booking || $booking['payment_status'] !== 'menunggu_pembayaran') {
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate payment succeeded from gateway
    $stmt = $conn->prepare('UPDATE bookings SET payment_status = "berhasil", booking_status = "confirmed" WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$conn->close();
$remainingSeconds = !empty($booking['payment_expires_at']) ? max(0, strtotime($booking['payment_expires_at']) - time()) : null;
$remainingMinutes = $remainingSeconds !== null ? intdiv($remainingSeconds, 60) : 0;
$remainingSecondPart = $remainingSeconds !== null ? ($remainingSeconds % 60) : 0;
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
        <p>Status pembayaran: <strong><?php echo escape(payment_status_label($booking['payment_status'])); ?></strong></p>
        <p>Batas pembayaran: <strong><?php echo escape($booking['payment_expires_at']); ?></strong></p>
        <?php if ($remainingSeconds !== null): ?>
            <p>Waktu tersisa: <strong><?php echo sprintf('%02d:%02d', $remainingMinutes, $remainingSecondPart); ?></strong></p>
        <?php endif; ?>
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
        <p style="margin-top: 12px;">
            <a class="danger" href="pay.php?id=<?php echo escape($id); ?>&action=cancel" onclick="return confirm('Batalkan transaksi booking ini?');">Batalkan transaksi</a>
        </p>
    </div>
</div>
</body>
</html>