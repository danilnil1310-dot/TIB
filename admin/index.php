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
<body class="admin-page">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <h2>Futsal Admin</h2>
            <p>Panel manajemen</p>
        </div>
        <nav class="admin-nav">
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="lapangan.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'lapangan.php' ? 'active' : ''; ?>">Kelola Lapangan</a>
            <a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>">Kelola Booking</a>
            <a href="../logout.php" class="logout-link">Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="container admin-content">
            <header class="admin-header">
                <h1>Admin Dashboard</h1>
                <p>Selamat datang, <?php echo escape($_SESSION['user']['name']); ?></p>
            </header>
            <div class="cards">
                <div class="card">Total User<br><strong><?php echo escape($stats['users']); ?></strong></div>
                <div class="card">Total Lapangan<br><strong><?php echo escape($stats['fields']); ?></strong></div>
                <div class="card">Total Booking<br><strong><?php echo escape($stats['bookings']); ?></strong></div>
                <div class="card">Booking Pending<br><strong><?php echo escape($stats['pending']); ?></strong></div>
            </div>
        </div>
    </main>
</div>
</body>
</html>