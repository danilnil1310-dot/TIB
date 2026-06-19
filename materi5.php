<h1> Materi 5 : Database </h1>
<form method="POST">
    username :<input type="text" name="username"><br><br>
    password :<input type="text" name="password"><br><br>
    nama :<input type="text" name="nama"><br><br>
    email :<input type="text" name="email"><br><br>
    <button type="submit" name="kirim">kirim</button>
</form>

<?php
include "koneksi.php";

if (isset($_POST['kirim'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];

    $sql = "INSERT INTO user(username,password,nama,email)
            VALUES('$username','$password','$nama','$email')";
    if ($koneksi->query($sql) == TRUE) {
        echo "Data berhasil ditambahkan <br><br>";
    } else {
        echo "Error : " . $koneksi->error;
    }
}
?>

<table border="1" cellpadding="10">
<tr>
    <th>ID user</th>
    <th>Username</th>
    <th>Password</th>
    <th>Nama</th>
    <th>Email</th>
    <th>Aksi</th>
</tr>
<?php
$sql = "SELECT * FROM user";
$result = $koneksi->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['id']."</td>";
        echo "<td>".$row['username']."</td>";
        echo "<td>".$row['password']."</td>";
        echo "<td>".$row['nama']."</td>";
        echo "<td>".$row['email']."</td>";
        echo "<td>
        <a href='materi5.php?hapus=".$row['id']."'
        style='color:black; padding:5px 10px; text-decoration:none; border-radius:5px;'
        onclick='return confirm(\"Yakin hapus?\")'>
        Hapus
        </a>
        |
        <a href='materi5.php?edit=".$row['id']."'
        style='color:black; padding:5px 10px; text-decoration:none; border-radius:5px;'>
        Edit
        </a>
        </td>";
        echo "</tr>";
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $sql = "DELETE FROM user WHERE id='$id'";
    if ($koneksi->query($sql) == TRUE) {
        echo "Data berhasil dihapus <br><br>";
    } else {
        echo "Error : " . $koneksi->error;
    }
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];

    $sql = "UPDATE user SET
            username='$username',
            password='$password',
            nama='$nama',
            email='$email'
            WHERE id='$id'";

    if ($koneksi->query($sql) == TRUE) {
        echo "Data berhasil diupdate <br><br>";
    } else {
        echo "Error : " . $koneksi->error;
    }
}
?>
</table>

<?php
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $data = $koneksi->query("SELECT * FROM user WHERE id='$id'")
                     ->fetch_assoc();
?>
<br><br>
<form method="POST">
    <input type="hidden" name="id"
    value="<?= $data['id'] ?>">
    Username :<input type="text" name="username" value="<?= $data['username'] ?>"><br><br>
    Password :<input type="password" name="password" value="<?= $data['password'] ?>"><br><br>
    Nama :<input type="text" name="nama" value="<?= $data['nama'] ?>"><br><br>
    Email :<input type="email" name="email" value="<?= $data['email'] ?>"><br><br>
    <button type="submit" name="update">Update</button>
</form>
<?php
}
?>