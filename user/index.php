<?php
require_once __DIR__ . '/../config.php';
ensure_user();
$conn = db();
$fieldsResult = $conn->query('SELECT * FROM lapangan ORDER BY name');
$fields = $fieldsResult ? $fieldsResult->fetch_all(MYSQLI_ASSOC) : [];
$bookings = $conn->prepare('SELECT b.*, l.name AS lapangan_name FROM bookings b JOIN lapangan l ON b.lapangan_id = l.id WHERE b.user_id = ? ORDER BY b.booking_date DESC, b.booking_time DESC');
$bookings->bind_param('i', $_SESSION['user']['id']);
$bookings->execute();
$bookingsResult = $bookings->get_result();
$bookings->close();

function getLapanganImage($field) {
    if (!empty($field['image'])) {
        return $field['image'];
    }
    $name = strtolower($field['name'] ?? '');
    if (strpos($name, 'lapangan a') !== false || strpos($name, 'a') === 0) {
        return 'https://images.unsplash.com/photo-1505842465776-3bd2144b5caa?auto=format&fit=crop&w=800&q=80';
    }
    if (strpos($name, 'lapangan b') !== false || strpos($name, 'b') === 0) {
        return 'https://images.unsplash.com/photo-1542736667-069246bdbc82?auto=format&fit=crop&w=800&q=80';
    }
    return 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=800&q=80';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>User - Booking Futsal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="user-page">
<div class="container">
    <header class="page-header">
        <div>
            <span class="eyebrow">Dashboard User</span>
            <h1>Booking Lapangan</h1>
            <p class="subtitle">Halo, <?php echo escape($_SESSION['user']['name']); ?>. Pilih lapangan, jadwal, dan metode pembayaran untuk memesan dengan cepat.</p>
        </div>
        <a class="logout-button" href="../logout.php">Logout</a>
    </header>
    <nav class="nav-bar user-menu">
        <div class="tab-group">
            <button type="button" class="tab-button active" data-tab="booking">Booking</button>
            <button type="button" class="tab-button" data-tab="history">Riwayat Transaksi</button>
        </div>
    </nav>
    <section id="booking-section" class="card-section">
        <div>
            <div class="field-cards">
                <?php foreach ($fields as $field): ?>
                    <div class="field-card">
                        <img src="<?php echo escape(getLapanganImage($field)); ?>" alt="<?php echo escape($field['name']); ?>">
                        <div class="field-card-info">
                            <h3><?php echo escape($field['name']); ?></h3>
                            <p><?php echo escape($field['description'] ?? 'Lapangan futsal standar.'); ?></p>
                            <span class="price">Rp <?php echo number_format($field['price'], 0, ',', '.'); ?>/jam</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="box-form">
                <h2>Form Booking</h2>
                <form action="book.php" method="post">
                    <label>Lapangan</label>
                    <select name="lapangan_id" required>
                        <option value="">Pilih lapangan</option>
                        <?php foreach ($fields as $field): ?>
                            <option value="<?php echo escape($field['id']); ?>"><?php echo escape($field['name']); ?> - Rp <?php echo number_format($field['price'], 0, ',', '.'); ?>/jam</option>
                        <?php endforeach; ?>
                    </select>
                <label>Tanggal</label>
                <input type="date" name="booking_date" required>
                <label>Jam</label>
                <input type="time" name="booking_time" required>
                <label>Durasi (jam)</label>
                <input type="number" name="duration" min="1" max="6" value="1" required>
                <label>Metode Pembayaran</label>
                <select name="payment_method" required>
                    <option value="qris">QRIS</option>
                    <option value="dana">DANA</option>
                </select>
                <button type="submit">Pesan Sekarang</button>
            </form>
            </div>
        </div>
    </section>
    <section id="history-section" class="table-wrap hidden">
        <h2>Riwayat Booking</h2>
        <div class="history-cards">
            <?php if ($bookingsResult->num_rows > 0): ?>
                <?php while ($row = $bookingsResult->fetch_assoc()): ?>
                    <article class="history-card">
                        <div class="history-card-header">
                            <div>
                                <h3><?php echo escape($row['lapangan_name']); ?></h3>
                                <p><?php echo escape($row['booking_date']); ?> · <?php echo escape($row['booking_time']); ?> · <?php echo escape($row['duration']); ?> jam</p>
                            </div>
                            <span class="status-pill status-<?php echo escape($row['payment_status']); ?>"><?php echo escape(ucfirst($row['payment_status'])); ?></span>
                        </div>
                        <div class="history-card-body">
                            <div class="history-meta">
                                <span>Total</span>
                                <strong>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></strong>
                            </div>
                            <div class="history-meta">
                                <span>Metode</span>
                                <strong class="payment-<?php echo escape($row['payment_method']); ?>"><?php echo escape(strtoupper($row['payment_method'])); ?></strong>
                            </div>
                            <div class="history-meta">
                                <span>Status Booking</span>
                                <strong><?php echo escape(ucfirst($row['booking_status'])); ?></strong>
                            </div>
                        </div>
                        <div class="history-card-footer">
                            <?php if ($row['payment_status'] === 'unpaid' && $row['booking_status'] === 'pending'): ?>
                                <a class="pay-link" href="pay.php?id=<?php echo escape($row['id']); ?>">Bayar Sekarang</a>
                            <?php else: ?>
                                <span class="history-note">Tidak ada tindakan</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Belum ada riwayat booking. Silakan lakukan booking terlebih dahulu.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookingTab = document.querySelector('.tab-button[data-tab="booking"]');
        const historyTab = document.querySelector('.tab-button[data-tab="history"]');
        const bookingSection = document.getElementById('booking-section');
        const historySection = document.getElementById('history-section');
        const buttons = document.querySelectorAll('.tab-button');

        function switchTab(tab) {
            buttons.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
            bookingSection.style.display = tab === 'booking' ? 'grid' : 'none';
            historySection.style.display = tab === 'history' ? 'block' : 'none';
        }

        bookingTab.addEventListener('click', function() {
            switchTab('booking');
        });
        historyTab.addEventListener('click', function() {
            switchTab('history');
        });

        switchTab('booking');
    });
</script>
</body>
</html>