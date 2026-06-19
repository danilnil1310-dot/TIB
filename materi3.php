<?php
echo "<h1>Materi 3 : If dan Else</h1>";
echo "<h3>1. Function tanpa parameter</h3>";
function sapa() {
    echo "Halo!";
}

sapa();

echo "<h3>2. Function dengan parameter</h3>";
function sapaNama($nama) {
    echo "Halo, $nama!";
}

sapaNama("Niel");

echo "<h3>3. Function dengan return</h3>";
function tambah($a, $b) {
    return $a + $b;
}

$hasil = tambah(5, 5);
echo $hasil;

echo "<h3>4. Function</h3>";
function salam ()
{
echo "Assalamu'alaikum";
}
salam();

echo "<h3>5. Function parameter</h3>";
echo "<br><br>penjumlahan<br><br>";
function jumlah(int $a, int $b)
{
    $jumlah = $a + $b;
    return $jumlah;
}
$hasil = jumlah(4, 5);
echo $hasil;

echo "<br><br>perkalian<br><br>";
function kali(int $a, int $b)
{
    $kali = $a * $b;
    return $kali;
}
$hasil = kali(4, 5);
echo $hasil;

echo "<br><br>pembagian<br><br>";
function bagi(int $a, int $b)
{
    $bagi = $a / $b;
    return $bagi;
}
$hasil = bagi(4, 5);
echo $hasil;

echo "<br><br>pengurangan<br><br>";
function kurang(int $a, int $b)
{
    $kurang = $a - $b;
    return $kurang;
}
$hasil = kurang(4, 5);
echo $hasil;
?>

<h3>6. Function parameter form</h3>
<form method="post">
    <input type="number" name="angka1">
    <input type="number" name="angka2">
    <button type="submit" name="kirim">kirim</button>
</form>

<?php

$hasilForm = "";

if (isset($_POST['kirim'])){
    $angka1 = $_POST['angka1'];
    $angka2 = $_POST['angka2'];

    $hasilTambah = tambah($angka1, $angka2);
    $hasilKali   = kali($angka1, $angka2);
    $hasilKurang = kurang($angka1, $angka2);

    if ($angka2 != 0) {
        $hasilBagi = bagi($angka1, $angka2);
    } else {
        $hasilBagi = "Tidak bisa dibagi 0";
    }

    // tampilkan dengan keterangan + rumus biar jelas
    $hasilForm = "
    <br>$angka1 + $angka2 = $hasilTambah <br>
    <br>$angka1 × $angka2 = $hasilKali <br>
    <br>$angka1 - $angka2 = $hasilKurang <br>
    <br>$angka1 ÷ $angka2 = $hasilBagi
    ";
}

echo "<h4>Hasil:<br>$hasilForm</h4>";

?>