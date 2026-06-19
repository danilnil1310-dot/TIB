<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!$name || !$username || !$password) {
        $error = 'Semua bidang wajib diisi.';
    } else {
        $conn = db();
        $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $check->bind_param('s', $username);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = 'Username sudah digunakan.';
            $check->close();
            $conn->close();
        } else {
            $check->close();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, "user")');
            $stmt->bind_param('sss', $name, $username, $passwordHash);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun User</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Daftar Akun User</h1>
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo escape($error); ?></div>
    <?php endif; ?>
    <form action="register.php" method="post">
        <label>Nama Lengkap</label>
        <input type="text" name="name" required>
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="index.php">Login di sini</a></p>
</div>
</body>
</html>