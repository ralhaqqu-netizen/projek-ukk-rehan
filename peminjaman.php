<?php
include "auth.php";
include "connection.php";
$msg = "";
$sessionAnggotaId = isset($_SESSION['id_anggota']) ? (int)$_SESSION['id_anggota'] : 0;
$sessionAnggotaNama = $_SESSION['nama_anggota'] ?? null;
$userRole = $_SESSION['role'] ?? 'user';
$filterAnggota = $userRole === 'user' ? " AND p.id_anggota = $sessionAnggotaId" : "";

if (isset($_POST['approve_peminjaman'])) {
    if (!in_array($userRole, ['admin', 'petugas'])) {
        $msg = "Hanya admin atau petugas yang bisa menyetujui pinjaman.";
    } else {
        $approveId = (int)$_POST['approve_peminjaman'];
        $ok = mysqli_query($conn, "UPDATE peminjaman SET status='Dipinjam' WHERE id_peminjaman=$approveId AND status='Menunggu Persetujuan'");
        if ($ok) {
            header("Location: peminjaman.php");
            exit;
        } else {
            $msg = "Persetujuan gagal: " . mysqli_error($conn);
        }
    }
}

if (isset($_POST['hapus_peminjaman'])) {
    $hapusId = (int)$_POST['hapus_peminjaman'];
    $cek = mysqli_query($conn, "SELECT p.status, p.id_anggota FROM peminjaman p WHERE p.id_peminjaman=$hapusId LIMIT 1");
    $statusRow = mysqli_fetch_assoc($cek);
    if (!$statusRow) {
        $msg = "Data peminjaman tidak ditemukan.";
    } elseif ($statusRow['status'] === 'Dipinjam') {
        $msg = "Pinjaman yang masih aktif tidak bisa dihapus.";
    } else {
        $bolehHapus = ($userRole === 'admin') || ($userRole === 'user' && (int)$statusRow['id_anggota'] === $sessionAnggotaId);
        if (!$bolehHapus) {
            $msg = "Anda tidak punya izin untuk menghapus riwayat ini.";
        } else {
            mysqli_begin_transaction($conn);
            $ok = mysqli_query($conn, "DELETE FROM pengembalian WHERE id_peminjaman=$hapusId");
            $ok = $ok && mysqli_query($conn, "DELETE FROM peminjaman_detail WHERE id_peminjaman=$hapusId");
            $ok = $ok && mysqli_query($conn, "DELETE FROM peminjaman WHERE id_peminjaman=$hapusId");
            if ($ok) {
                mysqli_commit($conn);
                header("Location: peminjaman.php");
                exit;
            } else {
                mysqli_rollback($conn);
                $msg = "Gagal menghapus riwayat: " . mysqli_error($conn);
            }
        }
    }
}

if (isset($_POST['pinjam'])) {
    $anggota = $sessionAnggotaId;
    $buku = (int)$_POST['id_buku'];
    $jumlah = (int)$_POST['jumlah'];
    $tgl = mysqli_real_escape_string($conn, $_POST['tanggal_pinjam']);
    $kembali = mysqli_real_escape_string($conn, $_POST['tanggal_kembali']);

    if (!$anggota) {
        $msg = "Data anggota untuk akun login belum ditemukan. Hubungi admin.";
    } else {
        $q = mysqli_query($conn, "SELECT stok FROM buku WHERE id_buku=$buku");
        $b = mysqli_fetch_assoc($q);
        if (!$b || $b['stok'] < $jumlah || $jumlah < 1) {
            $msg = "Stok buku tidak cukup.";
        } else {
            mysqli_begin_transaction($conn);
            $ok = mysqli_query($conn, "INSERT INTO peminjaman(id_anggota,tanggal_pinjam,tanggal_kembali,status) VALUES($anggota,'$tgl','$kembali','Menunggu Persetujuan')");
            $pid = mysqli_insert_id($conn);
            $ok = $ok && mysqli_query($conn, "INSERT INTO peminjaman_detail(id_peminjaman,id_buku,jumlah) VALUES($pid,$buku,$jumlah)");
            if ($ok) {
                mysqli_commit($conn);
                header("Location: peminjaman.php");
                exit;
            } else {
                mysqli_rollback($conn);
                $msg = "Peminjaman gagal: " . mysqli_error($conn);
            }
        }
    }
}

$anggota = mysqli_query($conn, "SELECT * FROM anggota ORDER BY nama_anggota");
$buku = mysqli_query($conn, "SELECT * FROM buku WHERE stok>0 ORDER BY judul_buku");
$data = mysqli_query($conn, "SELECT p.*,a.nama_anggota,b.judul_buku,d.jumlah FROM peminjaman p JOIN anggota a ON p.id_anggota=a.id_anggota JOIN peminjaman_detail d ON p.id_peminjaman=d.id_peminjaman JOIN buku b ON d.id_buku=b.id_buku WHERE 1=1$filterAnggota ORDER BY p.id_peminjaman DESC");
?>
<?php
$badgeMap = [
    'Menunggu Persetujuan' => 'wait',
    'Dipinjam'             => 'out',
    'Dikembalikan'         => 'done',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peminjaman | Perpustakaan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<main class="page">

    <div class="page-head">
        <h1>Peminjaman buku</h1>
        <p>Ajukan pinjaman, lalu petugas menyetujuinya sebelum buku dipinjam.</p>
    </div>

    <?php if ($msg) { ?>
        <div class="alert"><?= htmlspecialchars($msg) ?></div>
    <?php } ?>

    <form method="post" class="form">
        <h2 class="panel-title">Ajukan pinjaman</h2>
        <input type="hidden" name="id_anggota" value="<?=$sessionAnggotaId?>">
        <div class="form-grid">
            <div class="field">
                <label>Anggota</label>
                <div class="static-value selected-anggota"><?=htmlspecialchars($sessionAnggotaNama)?></div>
            </div>
            <div class="field">
                <label for="id_buku">Buku</label>
                <select id="id_buku" name="id_buku" required>
                    <?php while ($b = mysqli_fetch_assoc($buku)) { ?>
                        <option value="<?=$b['id_buku']?>"><?=htmlspecialchars($b['judul_buku'])?> (stok <?=$b['stok']?>)</option>
                    <?php } ?>
                </select>
            </div>
            <div class="field">
                <label for="jumlah">Jumlah</label>
                <input type="number" id="jumlah" name="jumlah" value="1" min="1" required>
            </div>
            <div class="field">
                <label for="tanggal_pinjam">Tanggal pinjam</label>
                <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" value="<?=date('Y-m-d')?>" required>
            </div>
            <div class="field">
                <label for="tanggal_kembali">Tanggal kembali</label>
                <input type="date" id="tanggal_kembali" name="tanggal_kembali" required>
            </div>
        </div>
        <div class="form-actions">
            <button name="pinjam">Ajukan pinjam</button>
        </div>
    </form>

    <div class="table-panel">
        <?php if (mysqli_num_rows($data) === 0) { ?>
            <p class="empty">Belum ada peminjaman. Ajukan yang pertama lewat formulir di atas.</p>
        <?php } else { ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th class="num">Jumlah</th>
                        <th>Pinjam</th>
                        <th>Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = mysqli_fetch_assoc($data)) {
                    $bolehHapus = ($userRole === 'admin') || ($userRole === 'user' && (int)$r['id_anggota'] === $sessionAnggotaId);
                ?>
                    <tr>
                        <td class="id" data-label="ID"><?=$r['id_peminjaman']?></td>
                        <td class="cell-title" data-label="Anggota"><?=htmlspecialchars($r['nama_anggota'])?></td>
                        <td data-label="Buku"><?=htmlspecialchars($r['judul_buku'])?></td>
                        <td class="num" data-label="Jumlah"><?=$r['jumlah']?></td>
                        <td data-label="Pinjam"><?=$r['tanggal_pinjam']?></td>
                        <td data-label="Kembali"><?=$r['tanggal_kembali']?></td>
                        <td data-label="Status"><span class="badge <?=$badgeMap[$r['status']] ?? ''?>"><?=htmlspecialchars($r['status'])?></span></td>
                        <td data-label="Aksi">
                            <span class="actions">
                            <?php if (in_array($userRole, ['admin', 'petugas']) && $r['status'] == 'Menunggu Persetujuan') { ?>
                                <form method="post" class="inline">
                                    <input type="hidden" name="approve_peminjaman" value="<?=$r['id_peminjaman']?>">
                                    <button type="submit">Setujui</button>
                                </form>
                            <?php }
                            if ($bolehHapus && $r['status'] != 'Dipinjam') { ?>
                                <form method="post" class="inline" onsubmit="return confirm('Hapus riwayat peminjaman ini?')">
                                    <input type="hidden" name="hapus_peminjaman" value="<?=$r['id_peminjaman']?>">
                                    <button type="submit" class="btn red">Hapus</button>
                                </form>
                            <?php } elseif (!$bolehHapus && $r['status'] != 'Dipinjam') {
                                echo '-';
                            } ?>
                            </span>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </div>

</main>
</body>
</html>
