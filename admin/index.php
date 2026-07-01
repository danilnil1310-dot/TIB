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
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"><span class="nav-icon">🏠</span> Dashboard</a>
            <a href="lapangan.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'lapangan.php' ? 'active' : ''; ?>"><span class="nav-icon">🏟️</span> Kelola Lapangan</a>
            <a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>"><span class="nav-icon">📅</span> Kelola Booking</a>
            <a href="../logout.php" class="logout-link"><span class="nav-icon">🚪</span> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="container admin-content">
            <header class="admin-header">
                <h1>Admin Dashboard</h1>
                <p>Selamat datang, <?php echo escape($_SESSION['user']['name']); ?></p>
            </header>
            <div class="app-hero">
                <div>
                    <p class="eyebrow">Ringkasan Hari Ini</p>
                    <h2>Kelola booking dan lapangan dengan cepat</h2>
                    <p>Panel admin dirancang agar terasa seperti aplikasi modern dengan akses cepat ke data penting.</p>
                </div>
                <div class="hero-badge">⚡ Live</div>
            </div>
            <div class="cards">
                <div class="card app-card">
                    <div class="card-icon">👤</div>
                    <div class="card-label">Total User</div>
                    <strong><?php echo escape($stats['users']); ?></strong>
                </div>
                <div class="card app-card">
                    <div class="card-icon">🏟️</div>
                    <div class="card-label">Total Lapangan</div>
                    <strong><?php echo escape($stats['fields']); ?></strong>
                </div>
                <div class="card app-card">
                    <div class="card-icon">📅</div>
                    <div class="card-label">Total Booking</div>
                    <strong><?php echo escape($stats['bookings']); ?></strong>
                </div>
                <div class="card app-card">
                    <div class="card-icon">⏳</div>
                    <div class="card-label">Booking Pending</div>
                    <strong><?php echo escape($stats['pending']); ?></strong>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>