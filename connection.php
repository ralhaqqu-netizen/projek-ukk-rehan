<?php
$host = "sql313.infinityfree.com";
$user = "if0_43221700";
$pass = "VXvInDP72Jw";
$db   = "if0_43221700_db_rehan_xiirpl1_paket4";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>