<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'futsal_booking');
define('BASE_URL', '/futsal');

function db() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');
    return $conn;
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function payment_status_label($status) {
    $labels = [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'berhasil' => 'Berhasil',
        'gagal' => 'Gagal',
        'kadaluarsa' => 'Kadaluarsa',
        'unpaid' => 'Menunggu Pembayaran',
        'paid' => 'Berhasil',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', (string) $status));
}

function payment_status_class($status) {
    return 'status-' . str_replace('_', '-', (string) $status);
}

function ensure_booking_payment_schema($conn) {
    $paymentStatusColumn = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
    if ($paymentStatusColumn && $paymentStatusColumn->num_rows > 0) {
        $conn->query("UPDATE bookings SET payment_status = CASE payment_status WHEN 'paid' THEN 'berhasil' WHEN 'unpaid' THEN 'menunggu_pembayaran' WHEN 'berhasil' THEN 'berhasil' WHEN 'gagal' THEN 'gagal' WHEN 'kadaluarsa' THEN 'kadaluarsa' ELSE 'menunggu_pembayaran' END WHERE payment_status IS NOT NULL");
        $conn->query("ALTER TABLE bookings MODIFY payment_status ENUM('menunggu_pembayaran','berhasil','gagal','kadaluarsa') NOT NULL DEFAULT 'menunggu_pembayaran'");
    }

    $expiresColumn = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_expires_at'");
    if ($expiresColumn && $expiresColumn->num_rows === 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN payment_expires_at DATETIME NULL");
    }
}

function expire_unpaid_bookings($conn) {
    $conn->query("UPDATE bookings SET payment_status = 'kadaluarsa', booking_status = 'canceled' WHERE payment_status = 'menunggu_pembayaran' AND payment_expires_at IS NOT NULL AND payment_expires_at < NOW()");
}

function check_login() {
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function is_admin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function ensure_admin() {
    check_login();
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function ensure_user() {
    check_login();
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }
}

