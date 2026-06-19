 echo "<h1>Materi 2 : Looping<h1>";
<form method="POST">
masukkan sebuah angka : <input type="number" name = "angka">
<input type="submit" value="kirim">

</form>

<?php

if (isset($_POST['angka'])){
    $angka = $_POST['angka'];

    for ($i = 1; $i <= $angka; $i++) {
        echo "<br>Nilai Anda : $i";
    }

}

echo "<h2>Materi 3: Perulangan & Function</h2>";

/* ================= WHILE ================= */
echo "<h3>1. While</h3>";
echo "Tampilkan angka dari 1 sampai 5 menggunakan while <br>";
$i = 1;
while ($i <= 5) {
    echo $i . " ";
    $i++;
}

echo "<br><br>";

/* ================= DO WHILE ================= */
echo "<h3>2. Do While</h3>";
echo "Tampilkan angka dari 1 sampai 5 menggunakan do while <br>";

$i = 1;
do {
    echo $i . " ";
    $i++;
} while ($i <= 5);

echo "<br><br>";

/* ================= FOR ================= */
echo "<h3>3. For</h3>";
echo "Tampilkan angka dari 1 sampai 5 menggunakan for <br>";

for ($i = 1; $i <= 5; $i++) {
    echo $i . " ";
}

echo "<br><br>";

/* ================= FOR INCREMENT ================= */
echo "<h3>4. For Increment (Loncat 2)</h3>";
echo "Tampilkan angka ganjil dari 1 sampai 10 <br>";

for ($i = 1; $i <= 10; $i += 2) {
    echo $i . " ";
}

echo "<br><br>";

/* ================= FOR DECREMENT ================= */
echo "<h3>5. For Mundur</h3>";
echo "Tampilkan angka dari 10 sampai 1 <br>";

for ($i = 10; $i >= 1; $i--) {
    echo $i . " ";
}

echo "<br>";
?>