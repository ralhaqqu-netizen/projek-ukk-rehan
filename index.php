<?php
include "auth.php";
include "connection.php";

/* Mengambil jumlah data */

$q_buku = mysqli_query($conn, "SELECT COUNT(*) AS total FROM buku");
$data_buku = mysqli_fetch_assoc($q_buku);
$total_buku = $data_buku['total'];

$q_stok = mysqli_query($conn, "SELECT SUM(stok) AS total FROM buku");
$data_stok = mysqli_fetch_assoc($q_stok);
$total_stok = $data_stok['total'] ?? 0;

$q_anggota = mysqli_query($conn, "SELECT COUNT(*) AS total FROM anggota");
$data_anggota = mysqli_fetch_assoc($q_anggota);
$total_anggota = $data_anggota['total'];

$q_peminjaman = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peminjaman");
$data_peminjaman = mysqli_fetch_assoc($q_peminjaman);
$total_peminjaman = $data_peminjaman['total'];

$q_pengembalian = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pengembalian");
$data_pengembalian = mysqli_fetch_assoc($q_pengembalian);
$total_pengembalian = $data_pengembalian['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Perpustakaan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<main class="page">

    <div class="page-head">
        <h1>Halo, <?= htmlspecialchars($_SESSION['username']) ?></h1>
        <p>Ringkasan isi perpustakaan saat ini.</p>
    </div>

    <section class="stats" aria-label="Ringkasan">
        <div class="stat"><span class="stat-num"><?= $total_buku ?></span><span class="stat-label">Judul buku</span></div>
        <div class="stat"><span class="stat-num"><?= $total_stok ?></span><span class="stat-label">Total stok</span></div>
        <div class="stat"><span class="stat-num"><?= $total_anggota ?></span><span class="stat-label">Anggota</span></div>
        <div class="stat"><span class="stat-num"><?= $total_peminjaman ?></span><span class="stat-label">Peminjaman</span></div>
        <div class="stat"><span class="stat-num"><?= $total_pengembalian ?></span><span class="stat-label">Pengembalian</span></div>
    </section>

    <div class="actions">
        <a class="btn solid" href="peminjaman.php">Ajukan peminjaman</a>
        <a class="btn" href="pengembalian.php">Kembalikan buku</a>
    </div>

</main>
</body>
</html>
