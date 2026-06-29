<?php
require_once 'config.php';
if (!empty($_SESSION['user'])) {
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    }
    header('Location: ' . BASE_URL . '/user/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Futsal Booking</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
<div class="container">
    <div class="login-card">
        <div class="login-panel">
            <div class="login-copy">
                <span class="eyebrow">Akses Cepat</span>
                <h1>Login Futsal Booking</h1>
                <p class="subtitle">Masuk untuk pesan lapangan futsal sekarang.</p>
            </div>
            <div class="field-image">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTfc3ZogGqCeM1XKLndwRQRXBs2fwlbKWAEeAg_Pzc_UU6_BN2bMCKrbQo&s=10" alt="Lapangan futsal">
            </div>
        </div>
        <div class="login-form-card">
            <form action="login.php" method="post">
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit">Login</button>
            </form>
            <p class="register-link">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
        </div>
    </div>
</div>
</body>
</html>

