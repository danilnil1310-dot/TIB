<?php
require_once __DIR__ . '/../config.php';
ensure_admin();
$conn = db();
$stats = [];
$stats['users'] = $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'];
$stats['fields'] = $conn->query('SELECT COUNT(*) AS total FROM lapangan')->fetch_assoc()['total'];
$stats['bookings'] = $conn->query('SELECT COUNT(*) AS total FROM bookings')->fetch_assoc()['total'];
$stats['pending'] = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE booking_status = 'pending'")->fetch_assoc()['total'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>Admin Dashboard</h1>
    <p>Selamat datang, <?php echo escape($_SESSION['user']['name']); ?></p>
    <nav class="nav-bar">
        <a href="index.php">Dashboard</a>
        <a href="lapangan.php">Kelola Lapangan</a>
        <a href="bookings.php">Kelola Booking</a>
        <a href="../logout.php">Logout</a>
    </nav>
    <div class="cards">
        <div class="card">Total User<br><strong><?php echo escape($stats['users']); ?></strong></div>
        <div class="card">Total Lapangan<br><strong><?php echo escape($stats['fields']); ?></strong></div>
        <div class="card">Total Booking<br><strong><?php echo escape($stats['bookings']); ?></strong></div>
        <div class="card">Booking Pending<br><strong><?php echo escape($stats['pending']); ?></strong></div>
    </div>
    <section>
        <h2>Panduan Cepat</h2>
        <p>Gunakan menu di atas untuk menambahkan lapangan, melihat booking, dan memproses status pembayaran.</p>
    </section>
</div>
</body>
</html>