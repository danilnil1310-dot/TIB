<?php
// admin_check.php - skrip sementara untuk debugging login
require_once __DIR__ . '/config.php';

$conn = db();
$res = $conn->query('SELECT id, name, username, role, password, created_at FROM users ORDER BY id');
$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Debug Users</title>
    <style>body{font-family:Arial,Helvetica,sans-serif;padding:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#f4f4f4}</style>
</head>
<body>
<h1>Daftar users (debug)</h1>
<p>Hapus berkas ini setelah debugging.</p>
<table>
    <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Password Hash</th><th>Created At</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo escape($u['id']); ?></td>
            <td><?php echo escape($u['name']); ?></td>
            <td><?php echo escape($u['username']); ?></td>
            <td><?php echo escape($u['role']); ?></td>
            <td style="font-family:monospace;font-size:12px;"><?php echo escape($u['password']); ?></td>
            <td><?php echo escape($u['created_at']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>