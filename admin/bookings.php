<?php
require_once __DIR__ . '/../config.php';
ensure_admin();
$conn = db();
ensure_booking_payment_schema($conn);
expire_unpaid_bookings($conn);
$message = '';
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'confirm') {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'berhasil' WHERE id = ?");
        $message = 'Booking telah dikonfirmasi dan pembayaran dinyatakan berhasil.';
    } elseif ($_GET['action'] === 'fail') {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'canceled', payment_status = 'gagal' WHERE id = ?");
        $message = 'Status pembayaran diubah menjadi gagal.';
    } elseif ($_GET['action'] === 'cancel') {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'canceled', payment_status = 'gagal' WHERE id = ?");
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
                <h1>Kelola Booking</h1>
                <p>Konfirmasi atau batalkan booking pelanggan.</p>
            </header>
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
                            <td><span class="status-pill <?php echo escape(payment_status_class($row['payment_status'])); ?>"><?php echo escape(payment_status_label($row['payment_status'])); ?></span></td>
                            <td>
                                <?php if ($row['booking_status'] === 'pending' && $row['payment_status'] === 'menunggu_pembayaran'): ?>
                                    <a href="bookings.php?action=confirm&id=<?php echo escape($row['id']); ?>">Konfirmasi</a>
                                    <a href="bookings.php?action=fail&id=<?php echo escape($row['id']); ?>" onclick="return confirm('Tandai pembayaran sebagai gagal?');">Tandai Gagal</a>
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
    </main>
</div>
</body>
</html>