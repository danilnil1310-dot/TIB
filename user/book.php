<?php
require_once __DIR__ . '/../config.php';
ensure_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$lapangan_id = intval($_POST['lapangan_id']);
$booking_date = $_POST['booking_date'];
$booking_time = $_POST['booking_time'];
$duration = intval($_POST['duration']);
$payment_method = trim($_POST['payment_method'] ?? 'qris');
$payment_method = in_array($payment_method, ['qris', 'dana']) ? $payment_method : 'qris';
if (!$lapangan_id || !$booking_date || !$booking_time || $duration <= 0) {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$conn = db();
if ($conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'")->num_rows === 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'qris'");
}
$stmt = $conn->prepare('SELECT price FROM lapangan WHERE id = ?');
$stmt->bind_param('i', $lapangan_id);
$stmt->execute();
$result = $stmt->get_result();
$field = $result->fetch_assoc();
$stmt->close();
if (!$field) {
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$price = $field['price'];
$total = $price * $duration;

$requestedStart = new DateTimeImmutable($booking_date . ' ' . $booking_time);
$requestedEnd = $requestedStart->modify('+' . $duration . ' hours');

$check = $conn->prepare('SELECT booking_time, duration FROM bookings WHERE lapangan_id = ? AND booking_date = ? AND booking_status != "canceled"');
$check->bind_param('is', $lapangan_id, $booking_date);
$check->execute();
$existingResult = $check->get_result();
$conflictMessage = '';

while ($existing = $existingResult->fetch_assoc()) {
    $existingStart = new DateTimeImmutable($booking_date . ' ' . $existing['booking_time']);
    $existingEnd = $existingStart->modify('+' . intval($existing['duration']) . ' hours');

    if ($requestedStart < $existingEnd && $existingStart < $requestedEnd) {
        $conflictMessage = sprintf(
            'Maaf, lapangan sudah dibooking pada %s %s - %s. Silakan pilih jadwal lain.',
            $booking_date,
            $existingStart->format('H:i'),
            $existingEnd->format('H:i')
        );
        break;
    }
}

$check->close();

if ($conflictMessage !== '') {
    $_SESSION['booking_error'] = $conflictMessage;
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}

$stmt = $conn->prepare('INSERT INTO bookings (user_id, lapangan_id, booking_date, booking_time, duration, total_price, payment_method, booking_status, payment_status, payment_expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, "pending", "menunggu_pembayaran", DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())');
$stmt->bind_param('iissids', $_SESSION['user']['id'], $lapangan_id, $booking_date, $booking_time, $duration, $total, $payment_method);
$stmt->execute();
$stmt->close();
$conn->close();

$_SESSION['booking_message'] = 'Booking berhasil dibuat. Silakan lanjutkan pembayaran.';
header('Location: ' . BASE_URL . '/user/index.php');
exit;
