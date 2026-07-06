<?php
require_once __DIR__ . '/../config.php';
ensure_admin();
$conn = db();
ensure_settings_schema($conn);

$settings = [
    'place_name' => get_setting($conn, 'place_name', 'Futsal Admin'),
    'location' => get_setting($conn, 'location', 'Alamat belum diatur'),
    'contact' => get_setting($conn, 'contact', '0812-3456-7890'),
    'operating_hours' => get_setting($conn, 'operating_hours', 'Senin - Minggu 08.00 - 22.00'),
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['place_name'] = trim($_POST['place_name'] ?? '') !== '' ? trim($_POST['place_name'] ?? '') : 'Futsal Admin';
    $settings['location'] = trim($_POST['location'] ?? '') !== '' ? trim($_POST['location'] ?? '') : 'Alamat belum diatur';
    $settings['contact'] = trim($_POST['contact'] ?? '') !== '' ? trim($_POST['contact'] ?? '') : '0812-3456-7890';
    $settings['operating_hours'] = trim($_POST['operating_hours'] ?? '') !== '' ? trim($_POST['operating_hours'] ?? '') : 'Senin - Minggu 08.00 - 22.00';

    foreach ($settings as $key => $value) {
        save_setting($conn, $key, $value);
    }
    $message = 'Pengaturan berhasil disimpan.';
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Pengaturan</title>
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
            <a href="index.php"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span> Dashboard</a>
            <a href="lapangan.php"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="3"/><circle cx="12" cy="12" r="3"/></svg></span> Kelola Lapangan</a>
            <a href="bookings.php"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="15" rx="3"/><path d="M4 9h16"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M9 13h6"/></svg></span> Kelola Booking</a>
            <a href="users.php"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M4 21c0-4.28 3.58-7.75 8-7.75s8 3.47 8 7.75"/></svg></span> Kelola Pengguna</a>
            <a href="finance.php"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8h18"/><path d="M6 4h12"/><path d="M5 12h14"/><path d="M7 16h10"/><path d="M9 20h6"/></svg></span> Laporan Keuangan</a>
            <a href="settings.php" class="active"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1Z"/></svg></span> Pengaturan</a>
            <a href="../logout.php" class="logout-link"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="container admin-content">
            <header class="admin-header">
                <h1>Pengaturan</h1>
                <p>Atur preferensi dasar panel admin.</p>
            </header>
            <?php if ($message !== ''): ?><div class="success"><?php echo escape($message); ?></div><?php endif; ?>
            <div class="box-form">
                <form action="settings.php" method="post">
                    <label>Nama Tempat</label>
                    <input type="text" name="place_name" value="<?php echo escape($settings['place_name']); ?>" required>
                    <label>Lokasi</label>
                    <input type="text" name="location" value="<?php echo escape($settings['location']); ?>" required>
                    <label>Kontak Kami</label>
                    <input type="text" name="contact" value="<?php echo escape($settings['contact']); ?>" required>
                    <label>Jam Operasional</label>
                    <input type="text" name="operating_hours" value="<?php echo escape($settings['operating_hours']); ?>" required>
                    <button type="submit">Simpan</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
