<?php
echo "<h1>Materi 4 : Form Login<h1>";
$hasil = "";

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Username dan password benar
    if ($username == "urwhaaa" && $password == "1020") {
        $hasil = "Login Berhasil";
    } else {
        $hasil = "Username atau Password Salah";
    }
}
?>

<h3>Form Login</h3>

<form method="post">
    Username : <input type="text" name="username" required><br><br>
    Password : <input type="password" name="password" required><br><br>
    
    <button type="submit" name="login">Login</button>
</form>

<?php
echo "<h4>$hasil</h4>";
?>