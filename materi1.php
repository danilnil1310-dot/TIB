<h1>Materi 1 : If dan Else</h1>
<?php
    $nama = "Danil";
    $umur = "20";
    $tinggi = "180";
    $kelas = "TIB Semester 4";

    echo "<br>Nama saya $nama, <br>umur saya $umur, <br>tinggi saya $tinggi, <br>kelas saya $kelas";

echo "<br><br>========================<br>";
$nilai1 = 11;
$nilai2 = 12;
$nilai3 = 13;

$hasil = $nilai1 * $nilai2 - $nilai3;
echo "<br>hasil dari $nilai1 x $nilai2 - $nilai3 adalah $hasil";

if($hasil >= 100) {
    echo "<br>nilai anda lebih dari 100";
} else if($hasil <= 100){
    echo "<br>nilai anda kurang dari 100";
} else {
    echo "<br>nilai anda kosong";
}
if($hasil >= 100) {
    echo "<br>Nilai anda ganjil";
} else if ($hasil < 120) {
    echo "<br>Nilai anda genap";
}

?>