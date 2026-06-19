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
if (!$lapangan_id || !$booking_date || !$booking_time || $duration <= 0) {
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$conn = db();
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
$check = $conn->prepare('SELECT id FROM bookings WHERE lapangan_id = ? AND booking_date = ? AND booking_time = ? AND booking_status != "canceled"');
$check->bind_param('iss', $lapangan_id, $booking_date, $booking_time);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    $check->close();
    $conn->close();
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
$check->close();
$stmt = $conn->prepare('INSERT INTO bookings (user_id, lapangan_id, booking_date, booking_time, duration, total_price, booking_status, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, "pending", "unpaid", NOW())');
$stmt->bind_param('iissid', $_SESSION['user']['id'], $lapangan_id, $booking_date, $booking_time, $duration, $total);
$stmt->execute();
$stmt->close();
$conn->close();
header('Location: ' . BASE_URL . '/user/index.php');
exit;
