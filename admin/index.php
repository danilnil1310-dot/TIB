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
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span> Dashboard</a>
            <a href="lapangan.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'lapangan.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"/><circle cx="12" cy="12" r="3"/></svg></span> Kelola Lapangan</a>
            <a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 9h16"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M9 13h6"/></svg></span> Kelola Booking</a>
            <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4 21c0-4.28 3.58-7.75 8-7.75s8 3.47 8 7.75"/></svg></span> Kelola Pengguna</a>
            <a href="finance.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'finance.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8h18"/><path d="M6 4h12"/><path d="M5 12h14"/><path d="M7 16h10"/><path d="M9 20h6"/></svg></span> Laporan Keuangan</a>
            <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1Z"/></svg></span> Pengaturan</a>
            <a href="../logout.php" class="logout-link"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span> Logout</a>
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
                    <div class="card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4 21c0-4.28 3.58-7.75 8-7.75s8 3.47 8 7.75"/></svg></div>
                    <div class="card-label">Total User</div>
                    <strong><?php echo escape($stats['users']); ?></strong>
                </div>
                <div class="card app-card">
                    <div class="card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="12" cy="12" r="3"/></svg></div>
                    <div class="card-label">Total Lapangan</div>
                    <strong><?php echo escape($stats['fields']); ?></strong>
                </div>
                <div class="card app-card">
                    <div class="card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 9h16"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M9 13h6"/></svg></div>
                    <div class="card-label">Total Booking</div>
                    <strong><?php echo escape($stats['bookings']); ?></strong>
                </div>
                <div class="card app-card">
                    <div class="card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v4l-4 4 4 4v4H6v-4l4-4-4-4V2z"/></svg></div>
                    <div class="card-label">Booking Pending</div>
                    <strong><?php echo escape($stats['pending']); ?></strong>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>