<?php
// config.php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'pesona_lombok_db';

try {
    // 1. Koneksi awal tanpa nama database untuk memastikan DB ada
    $pdo_init = new PDO("mysql:host=$host", $user, $pass);
    $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS $dbname");

    // 2. Koneksi utama ke database yang dituju
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 3. Buat Tabel Pengguna (Users) jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        nomor VARCHAR(20) PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        sandi VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        avatar TEXT
    ) ENGINE=InnoDB;");

    // 4. Buat Tabel Destinasi Wisata jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS destinasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(150) NOT NULL,
        deskripsi TEXT NOT NULL,
        tag VARCHAR(50) NOT NULL,
        gambar TEXT NOT NULL
    ) ENGINE=InnoDB;");

    // 5. ISI DATA DEFAULT (Hanya jika tabel masih kosong)
    // Akun Admin Default: Nomor = 087866992463 | Sandi = admin123
    $checkUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUser == 0) {
        $admin_sandi_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $user_sandi_hash = password_hash('rahasia123', PASSWORD_BCRYPT);
        
        $pdo->exec("INSERT INTO users (nomor, nama, sandi, role, avatar) VALUES 
            ('087866992463', 'Niel Pengelola (Admin)', '$admin_sandi_hash', 'admin', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=150'),
            ('08123456789', 'Budi Santoso', '$user_sandi_hash', 'user', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150')");
    }

    $checkDest = $pdo->query("SELECT COUNT(*) FROM destinasi")->fetchColumn();
    if ($checkDest == 0) {
        $pdo->exec("INSERT INTO destinasi (nama, deskripsi, tag, gambar) VALUES 
            ('Gili Trawangan', 'Pulau bebas polusi dengan pasir putih lembut, air laut jernih, dan terumbu karang indah.', 'Wisata Bahari', 'https://dinaspariwisata.ntbprov.go.id/wp-content/uploads/2015/05/Gili-Trawangan.png'),
            ('Pantai Pink', 'Fenomena unik pantai dengan pasir berwarna merah muda eksotis yang memanjakan mata.', 'Pantai Unik', 'https://indonesiajuara.asia/wp-content/uploads/2023/11/Pink-Beach-Lombok.webp'),
            ('Gunung Rinjani', 'Gunung berapi megah yang menawarkan petualangan trekking kelas dunia bagi pecinta alam.', 'Pegunungan', 'https://cdn0-production-images-kly.akamaized.net/8Oja5uPyRxXF0KIc8sRMUJiSLXQ=/1200x675/smart/filters:quality(75):strip_icc():format(jpeg)/kly-media-production/medias/1347333/original/000745400_1474018493-_trekkingrinjani_com_.jpg')");
    }

} catch (PDOException $e) {
    die("Koneksi / Setup Database Gagal: " . $e->getMessage());
}
?>