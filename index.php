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
<body>
<div class="container">
    <h1>Login Futsal Booking</h1>
    <form action="login.php" method="post">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p>Belum punya akun? <a href="register.php">Daftar sebagai User</a></p>
    <div class="note">
        <strong></strong>
    </div>
</div>
</body>
</html>

