<?php
require_once __DIR__ . '/../config.php';
ensure_user();

$booking_message = '';
$booking_error = '';
$profile_message = '';
$profile_error = '';
if (!empty($_SESSION['booking_message'])) {
    $booking_message = $_SESSION['booking_message'];
    unset($_SESSION['booking_message']);
}
if (!empty($_SESSION['booking_error'])) {
    $booking_error = $_SESSION['booking_error'];
    unset($_SESSION['booking_error']);
}
if (!empty($_SESSION['profile_message'])) {
    $profile_message = $_SESSION['profile_message'];
    unset($_SESSION['profile_message']);
}
if (!empty($_SESSION['profile_error'])) {
    $profile_error = $_SESSION['profile_error'];
    unset($_SESSION['profile_error']);
}

$conn = db();
ensure_settings_schema($conn);
ensure_booking_payment_schema($conn);
expire_unpaid_bookings($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_update'])) {
    $profileName = trim($_POST['profile_name'] ?? '');
    $profileUsername = trim($_POST['profile_username'] ?? '');
    $profilePassword = trim($_POST['profile_password'] ?? '');
    $profileConfirmPassword = trim($_POST['profile_confirm_password'] ?? '');

    if ($profileName === '' || $profileUsername === '') {
        $_SESSION['profile_error'] = 'Nama lengkap dan username wajib diisi.';
    } elseif ($profilePassword !== '' && $profilePassword !== $profileConfirmPassword) {
        $_SESSION['profile_error'] = 'Konfirmasi password tidak cocok.';
    } else {
        $checkStmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $checkStmt->bind_param('si', $profileUsername, $_SESSION['user']['id']);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $_SESSION['profile_error'] = 'Username sudah digunakan oleh akun lain.';
        } else {
            $checkStmt->close();
            if ($profilePassword !== '') {
                $passwordHash = password_hash($profilePassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare('UPDATE users SET name = ?, username = ?, password = ? WHERE id = ?');
                $updateStmt->bind_param('sssi', $profileName, $profileUsername, $passwordHash, $_SESSION['user']['id']);
            } else {
                $updateStmt = $conn->prepare('UPDATE users SET name = ?, username = ? WHERE id = ?');
                $updateStmt->bind_param('ssi', $profileName, $profileUsername, $_SESSION['user']['id']);
            }
            $updateStmt->execute();
            $updateStmt->close();

            $_SESSION['user']['name'] = $profileName;
            $_SESSION['user']['username'] = $profileUsername;
            $_SESSION['profile_message'] = 'Profil berhasil diperbarui.';
        }

        $checkStmt->close();
    }

    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}

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

$settings = [
    'place_name' => get_setting($conn, 'place_name', 'Futsal Admin'),
    'location' => get_setting($conn, 'location', 'Alamat belum diatur'),
    'contact' => get_setting($conn, 'contact', 'Kontak belum diatur'),
    'operating_hours' => get_setting($conn, 'operating_hours', 'Jam operasional belum diatur'),
];

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
        <a class="logout-button" href="../logout.php"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span> Logout</a>
    </header>
    <?php if ($profile_error !== '' || $profile_message !== ''): ?>
        <div class="alert <?php echo $profile_error !== '' ? 'alert-error' : 'alert-success'; ?>">
            <?php echo escape($profile_error !== '' ? $profile_error : $profile_message); ?>
        </div>
    <?php endif; ?>
    <nav class="nav-bar user-menu">
        <div class="tab-group">
            <button type="button" class="tab-button active" data-tab="booking"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h4"/></svg></span> Booking</button>
            <button type="button" class="tab-button" data-tab="history"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4a8 8 0 1 0 8 8"/><path d="M12 8v4l3 2"/></svg></span> Riwayat Transaksi</button>
            <button type="button" class="tab-button" data-tab="profile"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M4 20a8 8 0 0 1 16 0"/></svg></span> Profil</button>
        </div>
    </nav>
    <section id="profile-section" class="box-form hidden">
        <h2 class="section-heading"><span class="section-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M4 20a8 8 0 0 1 16 0"/></svg></span> Pengaturan Profil</h2>
        <p class="booking-note">Perbarui data akun Anda dengan cepat tanpa meninggalkan halaman ini.</p>
        <form action="index.php" method="post">
            <input type="hidden" name="profile_update" value="1">
            <label>Nama Lengkap</label>
            <input type="text" name="profile_name" value="<?php echo escape($_SESSION['user']['name']); ?>" required>
            <label>Username</label>
            <input type="text" name="profile_username" value="<?php echo escape($_SESSION['user']['username']); ?>" required>
            <label>Password Baru (opsional)</label>
            <input type="password" name="profile_password" autocomplete="new-password">
            <label>Konfirmasi Password Baru</label>
            <input type="password" name="profile_confirm_password" autocomplete="new-password">
            <button type="submit">Simpan Perubahan</button>
        </form>
    </section>
    <section id="booking-section" class="card-section">
        <div>
            <div class="dashboard-facilities">
                <div class="facility-card">
                    <h3>Fasilitas Utama</h3>
                    <div class="facility-list">
                        <span class="facility-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 11h8v6a3 3 0 0 1-3 3H11a3 3 0 0 1-3-3v-6Z"/><path d="M10 11V8a2 2 0 0 1 4 0v3"/><path d="M7 20h10"/></svg>Toilet</span>
                        <span class="facility-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17V9a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8"/><path d="M7 17h10"/><path d="M7 13h10"/><rect x="3" y="17" width="2" height="4" rx="1"/><rect x="19" y="17" width="2" height="4" rx="1"/></svg>Parkir</span>
                        <span class="facility-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="6"/><path d="M12 6v-2"/><path d="M12 20v-2"/><path d="M6 12H4"/><path d="M20 12h-2"/><path d="M16.95 7.05l-1.4-1.4"/><path d="M7.45 16.95l-1.4-1.4"/><path d="M16.95 16.95l-1.4 1.4"/><path d="M7.45 7.05l-1.4 1.4"/></svg>Wi-Fi</span>
                        <span class="facility-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14"/><path d="M6 11h12"/><path d="M8 15h8"/><path d="M10 19h4"/></svg>Kantin</span>
                        <span class="facility-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5h8"/><path d="M8 9h8"/><path d="M8 13h8"/><path d="M8 17h8"/><path d="M4 21h16"/></svg>Ruang Ganti</span>
                        <span class="facility-pill"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 5h10"/><path d="M7 9h10"/><path d="M7 13h10"/><path d="M7 17h10"/><path d="M10 21h4"/><path d="M8 4a4 4 0 0 1 8 0v4"/></svg>Mushola</span>
                    </div>
                </div>
                <div class="facility-card">
                    <h3>Informasi Tempat</h3>
                    <div class="facility-list">
                        <span class="facility-pill">🏟️ <?php echo escape($settings['place_name']); ?></span>
                        <span class="facility-pill">📍 <?php echo escape($settings['location']); ?></span>
                        <span class="facility-pill">📞 <?php echo escape($settings['contact']); ?></span>
                        <span class="facility-pill">🕒 <?php echo escape($settings['operating_hours']); ?></span>
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
                <h2 class="section-heading"><span class="section-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="M12 3v18"/></svg></span> Form Booking</h2>
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
        <h2 class="section-heading"><span class="section-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M8 8h8v8H8z"/></svg></span> Riwayat Booking</h2>
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
        const profileTab = document.querySelector('.tab-button[data-tab="profile"]');
        const bookingSection = document.getElementById('booking-section');
        const historySection = document.getElementById('history-section');
        const profileSection = document.getElementById('profile-section');
        const buttons = document.querySelectorAll('.tab-button');
        const lapanganSelect = document.getElementById('lapangan_id');
        const dateInput = document.getElementById('booking_date');
        const timeInput = document.getElementById('booking_time');
        const durationInput = document.querySelector('input[name="duration"]');
        const scheduleNote = document.getElementById('schedule-note');
        const bookedRanges = document.getElementById('booked-ranges');

        function switchTab(tab) {
            buttons.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
            bookingSection.classList.toggle('hidden', tab !== 'booking');
            historySection.classList.toggle('hidden', tab !== 'history');
            profileSection.classList.toggle('hidden', tab !== 'profile');
        }

        bookingTab.addEventListener('click', function() {
            switchTab('booking');
        });
        historyTab.addEventListener('click', function() {
            switchTab('history');
        });
        profileTab.addEventListener('click', function() {
            switchTab('profile');
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