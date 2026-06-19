<?php
$host = "localhost";
$user = "root";
$password= "";
$db = "db_tib";

$koneksi = new mysqli($host, $user, $password, $db);

if ($koneksi->connect_error) {
    die("koneksi database gagal:" . $koneksi->connect_error);
} else {
    echo "";
}

?>