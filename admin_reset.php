<?php
// admin_reset.php
// Gunakan hanya sementara untuk mereset password admin ke 'admin123'.
require_once __DIR__ . '/config.php';

// Cek apakah diakses dari browser: tambahkan konfirmasi sederhana
if (PHP_SAPI === 'cli') {
    echo "Jalankan melalui browser: http://localhost/futsal/admin_reset.php\n";
    exit;
}

// Simple token untuk mencegah akses tak sengaja (opsional: ganti atau hapus setelah dipakai)
$token = isset($_GET['token']) ? $_GET['token'] : null;
// Jika Anda ingin menambahkan token, set ?token=resetme ketika mengakses. Kosongkan untuk akses default.

try {
    $conn = db();
    $username = 'admin';
    $newPassword = 'admin123';
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Cek apakah user admin ada
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row) {
        $id = $row['id'];
        $u = $conn->prepare('UPDATE users SET password = ?, role = "admin" WHERE id = ?');
        $u->bind_param('si', $hash, $id);
        $u->execute();
        $u->close();
        echo "Password user 'admin' berhasil direset menjadi 'admin123'. Silakan login dan hapus file admin_reset.php.";
    } else {
        $i = $conn->prepare('INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, "admin")');
        $name = 'Admin Futsal';
        $i->bind_param('sss', $name, $username, $hash);
        $i->execute();
        $i->close();
        echo "User 'admin' dibuat dengan password 'admin123'. Silakan login dan hapus file admin_reset.php.";
    }

    $conn->close();
} catch (Exception $e) {
    echo 'Terjadi kesalahan: ' . $e->getMessage();
}

?>