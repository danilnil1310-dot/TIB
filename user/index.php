<?php
require_once __DIR__ . '/../config.php';
ensure_user();

$booking_message = '';
$booking_error = '';
if (!empty($_SESSION['booking_message'])) {
    $booking_message = $_SESSION['booking_message'];
    unset($_SESSION['booking_message']);
}
if (!empty($_SESSION['booking_error'])) {
    $booking_error = $_SESSION['booking_error'];
    unset($_SESSION['booking_error']);
}

$conn = db();
ensure_booking_payment_schema($conn);
expire_unpaid_bookings($conn);
if (isset($_GET['cancel_id'])) {
    $cancelId = intval($_GET['cancel_id']);
    $cancelStmt = $conn->prepare('UPDATE bookings SET booking_status = "canceled", payment_status = "gagal" WHERE id = ? AND user_id = ? AND booking_status = "pending" AND payment_status = "menunggu_pembayaran"');
    $cancelStmt->bind_param('ii', $cancelId, $_SESSION['user']['id']);
    $cancelStmt->execute();
    if ($cancelStmt->affected_rows > 0) {
        $_SESSION['booking_message'] = 'Transaksi booking berhasil dibatalkan.';
    } else {
        $_SESSION['booking_error'] = 'Transaksi tidak dapat dibatalkan saat ini.';
    }
    $cancelStmt->close();
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    $deleteStmt = $conn->prepare('DELETE FROM bookings WHERE id = ? AND user_id = ? AND booking_status != "pending"');
    $deleteStmt->bind_param('ii', $deleteId, $_SESSION['user']['id']);
    $deleteStmt->execute();
    if ($deleteStmt->affected_rows > 0) {
        $_SESSION['booking_message'] = 'Riwayat transaksi berhasil dihapus.';
    } else {
        $_SESSION['booking_error'] = 'Riwayat transaksi tidak dapat dihapus saat ini.';
    }
    $deleteStmt->close();
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$fieldsResult = $conn->query('SELECT * FROM lapangan ORDER BY name');
$fields = $fieldsResult ? $fieldsResult->fetch_all(MYSQLI_ASSOC) : [];
$allBookings = [];
$bookingQuery = $conn->query('SELECT lapangan_id, booking_date, booking_time, duration, booking_status, payment_status FROM bookings WHERE booking_status != "canceled"');
if ($bookingQuery) {
    while ($row = $bookingQuery->fetch_assoc()) {
        $allBookings[] = $row;
    }
}
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
        <a class="logout-button" href="../logout.php"><span class="nav-icon">🚪</span> Logout</a>
    </header>
    <nav class="nav-bar user-menu">
        <div class="tab-group">
            <button type="button" class="tab-button active" data-tab="booking"><span class="nav-icon">📝</span> Booking</button>
            <button type="button" class="tab-button" data-tab="history"><span class="nav-icon">🧾</span> Riwayat Transaksi</button>
        </div>
        <div class="quick-pill">⚡ Cepat & aman</div>
    </nav>
    <section id="booking-section" class="card-section">
        <div>
            <div class="dashboard-facilities">
                <div class="facility-card">
                    <h3>Fasilitas Utama</h3>
                    <div class="facility-list">
                        <span class="facility-pill">🚻 Toilet</span>
                        <span class="facility-pill">🅿️ Parkir</span>
                        <span class="facility-pill">💡 Wi-Fi</span>
                        <span class="facility-pill">🍽️ Kantin</span>
                        <span class="facility-pill">🧴 Ruang Ganti</span>
                        <span class="facility-pill">🧕 Mushola</span>
                    </div>
                </div>
            </div>
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
                <p class="booking-note">Perhatian: jika jam atau tanggal sudah dibooking, Anda akan mendapat notifikasi supaya tidak salah pilih jadwal.</p>
                <form action="book.php" method="post">
                    <label>Lapangan</label>
                    <select name="lapangan_id" id="lapangan_id" required>
                        <option value="">Pilih lapangan</option>
                        <?php foreach ($fields as $field): ?>
                            <option value="<?php echo escape($field['id']); ?>"><?php echo escape($field['name']); ?> - Rp <?php echo number_format($field['price'], 0, ',', '.'); ?>/jam</option>
                        <?php endforeach; ?>
                    </select>
                    <label>Tanggal</label>
                    <input type="date" name="booking_date" id="booking_date" required>
                    <label>Jam</label>
                    <select name="booking_time" id="booking_time" required disabled>
                        <option value="">Pilih jam</option>
                    </select>
                    <label>Durasi (jam)</label>
                    <input type="number" name="duration" min="1" max="6" value="1" required>
                    <label>Metode Pembayaran</label>
                    <select name="payment_method" required>
                        <option value="qris">QRIS</option>
                        <option value="dana">DANA</option>
                    </select>
                    <div id="schedule-note" class="schedule-note">Pilih lapangan dan tanggal untuk melihat jadwal yang sudah dibooking.</div>
                    <div id="booked-ranges" class="booked-ranges"></div>
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
                            <span class="status-pill <?php echo escape(payment_status_class($row['payment_status'])); ?>"><?php echo escape(payment_status_label($row['payment_status'])); ?></span>
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
                            <?php if (!empty($row['payment_expires_at']) && $row['payment_status'] === 'menunggu_pembayaran'): ?>
                                <div class="history-meta">
                                    <span>Batas Bayar</span>
                                    <strong><?php echo escape($row['payment_expires_at']); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="history-card-footer">
                            <?php if ($row['payment_status'] === 'menunggu_pembayaran' && $row['booking_status'] === 'pending'): ?>
                                <a class="pay-link" href="pay.php?id=<?php echo escape($row['id']); ?>">Bayar Sekarang</a>
                                <a class="danger" href="index.php?cancel_id=<?php echo escape($row['id']); ?>" onclick="return confirm('Batalkan transaksi booking ini?');">Batalkan</a>
                            <?php else: ?>
                                <a class="danger" href="index.php?delete_id=<?php echo escape($row['id']); ?>" onclick="return confirm('Hapus riwayat transaksi ini?');">Hapus Riwayat</a>
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
<?php if ($booking_error !== '' || $booking_message !== ''): ?>
    <div id="popup-notification" class="popup-notification">
        <div class="popup-card <?php echo $booking_error !== '' ? 'popup-error' : 'popup-success'; ?>">
            <button type="button" class="popup-close" aria-label="Tutup">×</button>
            <h2><?php echo $booking_error !== '' ? 'Pemberitahuan' : 'Berhasil'; ?></h2>
            <p><?php echo escape($booking_error !== '' ? $booking_error : $booking_message); ?></p>
        </div>
    </div>
<?php endif; ?>
<script>
    const bookedSlots = <?php echo json_encode($allBookings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const bookingTab = document.querySelector('.tab-button[data-tab="booking"]');
        const historyTab = document.querySelector('.tab-button[data-tab="history"]');
        const bookingSection = document.getElementById('booking-section');
        const historySection = document.getElementById('history-section');
        const buttons = document.querySelectorAll('.tab-button');
        const lapanganSelect = document.getElementById('lapangan_id');
        const dateInput = document.getElementById('booking_date');
        const timeInput = document.getElementById('booking_time');
        const durationInput = document.querySelector('input[name="duration"]');
        const scheduleNote = document.getElementById('schedule-note');
        const bookedRanges = document.getElementById('booked-ranges');

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

        function updateScheduleAvailability() {
            const lapanganId = lapanganSelect.value;
            const selectedDate = dateInput.value;
            const selectedDuration = parseInt(durationInput.value, 10) || 1;
            timeInput.disabled = true;
            timeInput.value = '';
            scheduleNote.innerHTML = 'Memuat jadwal...';
            scheduleNote.className = 'schedule-note';
            bookedRanges.innerHTML = '';

            if (!lapanganId || !selectedDate) {
                scheduleNote.innerHTML = 'Pilih lapangan dan tanggal untuk melihat jadwal yang sudah dibooking.';
                return;
            }

            const bookedForDate = bookedSlots.filter(slot => String(slot.lapangan_id) === String(lapanganId) && slot.booking_date === selectedDate);
            if (bookedForDate.length > 0) {
                const rangeList = bookedForDate.map(slot => {
                    const start = slot.booking_time.substring(0, 5);
                    const end = new Date(new Date(`${selectedDate}T${slot.booking_time}`).getTime() + parseInt(slot.duration, 10) * 60 * 60 * 1000);
                    return `<li>${start} - ${end.toTimeString().substring(0, 5)} </li>`;
                }).join('');
                bookedRanges.innerHTML = `<div class="booked-ranges-title">Jadwal sudah dibooking:</div><ul>${rangeList}</ul>`;
            } else {
                bookedRanges.innerHTML = '<div class="booked-ranges-title">Belum ada booking untuk tanggal ini.</div>';
            }

            const availableHours = [];
            for (let hour = 6; hour <= 23; hour++) {
                const start = `${String(hour).padStart(2, '0')}:00:00`;
                const startDate = new Date(`${selectedDate}T${start}`);
                const endDate = new Date(startDate.getTime() + selectedDuration * 60 * 60 * 1000);
                const isBlocked = bookedSlots.some(slot => {
                    if (String(slot.lapangan_id) !== String(lapanganId) || slot.booking_date !== selectedDate) {
                        return false;
                    }
                    const slotStart = new Date(`${slot.booking_date}T${slot.booking_time}`);
                    const slotEnd = new Date(slotStart.getTime() + parseInt(slot.duration, 10) * 60 * 60 * 1000);
                    return startDate < slotEnd && slotStart < endDate;
                });
                if (!isBlocked) {
                    availableHours.push(`${String(hour).padStart(2, '0')}:00`);
                }
            }

            if (availableHours.length === 0) {
                timeInput.innerHTML = '<option value="">Tidak ada jam tersedia</option>';
                timeInput.disabled = true;
                scheduleNote.innerHTML = 'Tidak ada jadwal tersedia untuk tanggal ini. Silakan pilih tanggal lain.';
                scheduleNote.className = 'schedule-note warning';
                return;
            }

            timeInput.innerHTML = '<option value="">Pilih jam</option>' + availableHours.map(hour => `<option value="${hour}">${hour}</option>`).join('');
            timeInput.disabled = false;
            scheduleNote.innerHTML = `Jadwal tersedia: ${availableHours.join(', ')}. Jam yang sudah dibooking tidak bisa dipilih.`;
            scheduleNote.className = 'schedule-note success';
        }

        lapanganSelect.addEventListener('change', updateScheduleAvailability);
        dateInput.addEventListener('change', updateScheduleAvailability);
        durationInput.addEventListener('input', updateScheduleAvailability);
        timeInput.addEventListener('change', function() {
            if (!timeInput.value) {
                scheduleNote.innerHTML = 'Silakan pilih jam yang masih tersedia.';
                scheduleNote.className = 'schedule-note warning';
            }
        });

        const popup = document.getElementById('popup-notification');
        if (popup) {
            const closeButton = popup.querySelector('.popup-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    popup.style.display = 'none';
                });
            }
            popup.addEventListener('click', function(event) {
                if (event.target === popup) {
                    popup.style.display = 'none';
                }
            });
        }
    });
</script>
</body>
</html>