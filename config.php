<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'futsal_booking');
define('BASE_URL', '/futsal');
define('PAYMENT_EXPIRY_MINUTES', 5);

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

function ensure_settings_schema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
}

function get_setting($conn, $key, $default = '') {
    $stmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['setting_value'] ?? $default;
}

function save_setting($conn, $key, $value) {
    $stmt = $conn->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP');
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
    $stmt->close();
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

