<?php
include "auth.php";
include "connection.php";

$msg = "";
$selectedDenda = 0;
$manualDendaValue = 0;
$sessionAnggotaId = isset($_SESSION['id_anggota']) ? (int)$_SESSION['id_anggota'] : 0;
$userRole = $_SESSION['role'] ?? 'user';
$filterAnggota = $userRole === 'user' ? " AND p.id_anggota = $sessionAnggotaId" : "";

if (isset($_POST['kembali'])) {

    $pid = (int)$_POST['id_peminjaman'];
    $tgl = mysqli_real_escape_string($conn, $_POST['tanggal_pengembalian']);

    $overrideDenda = isset($_POST['override_denda']) && $_POST['override_denda'] == '1';

    $manualDenda = $overrideDenda
        ? max(0, (int)($_POST['denda_manual'] ?? 0))
        : null;

    $q = mysqli_query($conn, "
        SELECT 
            p.tanggal_kembali,
            d.id_buku,
            d.jumlah
        FROM peminjaman p
        JOIN peminjaman_detail d 
            ON p.id_peminjaman = d.id_peminjaman
        WHERE p.id_peminjaman = $pid
        AND p.status = 'Dipinjam'
        $filterAnggota
    ");

    $details = [];
    $tanggalJatuh = null;
    $denda = 0;

    while ($row = mysqli_fetch_assoc($q)) {
        $details[] = $row;

        if ($tanggalJatuh === null) {
            $tanggalJatuh = $row['tanggal_kembali'];
        }
    }

    if (empty($details)) {

        $msg = "Peminjaman tidak ditemukan atau sudah dikembalikan.";

    } else {

        $jatuh = strtotime($tanggalJatuh);
        $kembali = strtotime($tgl);

        $hari = max(
            0,
            (int)(($kembali - $jatuh) / 86400)
        );

        foreach ($details as $row) {
            $denda += $hari * 1000 * (int)$row['jumlah'];
        }

        if ($manualDenda !== null) {
            $denda = $manualDenda;
        }

        mysqli_begin_transaction($conn);

        $ok = mysqli_query($conn, "
            INSERT INTO pengembalian
            (id_peminjaman, tanggal_pengembalian, denda)
            VALUES
            ($pid, '$tgl', $denda)
        ");

        $ok = $ok && mysqli_query($conn, "
            UPDATE peminjaman
            SET status = 'Dikembalikan'
            WHERE id_peminjaman = $pid
        ");

        foreach ($details as $row) {

            $jumlah = (int)$row['jumlah'];
            $id_buku = (int)$row['id_buku'];

            $ok = $ok && mysqli_query($conn, "
                UPDATE buku
                SET stok = stok + $jumlah
                WHERE id_buku = $id_buku
            ");
        }

        if ($ok) {

            mysqli_commit($conn);

            header("Location: pengembalian.php");
            exit;

        } else {

            mysqli_rollback($conn);

            $msg = "Pengembalian gagal: " . mysqli_error($conn);
        }
    }
}


/* DATA PEMINJAMAN UNTUK FORM */

$pinjam = mysqli_query($conn, "
    SELECT 
        p.id_peminjaman,
        a.nama_anggota,
        b.judul_buku,
        d.jumlah,
        p.tanggal_kembali
    FROM peminjaman p
    JOIN anggota a 
        ON p.id_anggota = a.id_anggota
    JOIN peminjaman_detail d 
        ON p.id_peminjaman = d.id_peminjaman
    JOIN buku b 
        ON d.id_buku = b.id_buku
    WHERE p.status = 'Dipinjam'
    $filterAnggota
    ORDER BY p.id_peminjaman DESC
");
$tanggalPengembalianDefault = $_POST['tanggal_pengembalian'] ?? date('Y-m-d');
$gunakanTanggalJatuhTempo = !isset($_POST['tanggal_pengembalian']);


/* DATA PENGEMBALIAN */

$data = mysqli_query($conn, "
    SELECT 
        k.*,
        a.nama_anggota
    FROM pengembalian k
    JOIN peminjaman p 
        ON k.id_peminjaman = p.id_peminjaman
    JOIN anggota a 
        ON p.id_anggota = a.id_anggota
    WHERE 1=1$filterAnggota
    ORDER BY k.id_pengembalian DESC
");


/* HITUNG DENDA */

if (
    isset($_POST['id_peminjaman']) &&
    !empty($_POST['tanggal_pengembalian'])
) {

    $selectedPid = (int)$_POST['id_peminjaman'];

    $selectedQ = mysqli_query($conn, "
        SELECT 
            p.tanggal_kembali,
            d.jumlah
        FROM peminjaman p
        JOIN peminjaman_detail d
            ON p.id_peminjaman = d.id_peminjaman
        WHERE p.id_peminjaman = $selectedPid
        AND p.status = 'Dipinjam'
        $filterAnggota
    ");

    while ($row = mysqli_fetch_assoc($selectedQ)) {

        $jatuh = strtotime($row['tanggal_kembali']);

        $kembali = strtotime(
            $_POST['tanggal_pengembalian']
        );

        $hari = max(
            0,
            (int)(($kembali - $jatuh) / 86400)
        );

        $selectedDenda +=
            $hari * 1000 * (int)$row['jumlah'];
    }
}

$manualDendaValue =
    isset($_POST['override_denda']) &&
    $_POST['override_denda'] == '1'
    ? max(
        0,
        (int)($_POST['denda_manual'] ?? 0)
    )
    : $selectedDenda;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengembalian | Perpustakaan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<main class="page">

    <div class="page-head">
        <h1>Pengembalian buku</h1>
        <p>Pilih pinjaman yang aktif, lalu catat tanggal pengembalian.</p>
    </div>

    <?php if ($msg) { ?>
        <div class="alert"><?= htmlspecialchars($msg) ?></div>
    <?php } ?>

    <form method="post" class="form">
        <h2 class="panel-title">Kembalikan buku</h2>
        <div class="form-grid">
            <div class="field wide">
                <label for="id_peminjaman">Peminjaman</label>
                <select name="id_peminjaman" id="id_peminjaman" required>
                    <?php while ($p = mysqli_fetch_assoc($pinjam)) { ?>
                        <?php if ($gunakanTanggalJatuhTempo) {
                            $tanggalPengembalianDefault = $p['tanggal_kembali'];
                            $gunakanTanggalJatuhTempo = false;
                        } ?>
                        <option
                            value="<?= $p['id_peminjaman'] ?>"
                            data-tanggal-kembali="<?= htmlspecialchars($p['tanggal_kembali']) ?>"
                        >
                            ID <?= $p['id_peminjaman'] ?>
                            - <?= htmlspecialchars($p['nama_anggota']) ?>
                            - <?= htmlspecialchars($p['judul_buku']) ?>
                            (<?= $p['jumlah'] ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="field">
                <label for="tanggal_pengembalian">Tanggal pengembalian</label>
                <input
                    type="date"
                    id="tanggal_pengembalian"
                    name="tanggal_pengembalian"
                    value="<?= htmlspecialchars($tanggalPengembalianDefault) ?>"
                    required
                >
            </div>
        </div>
        <div class="form-actions">
            <button name="kembali">Kembalikan buku</button>
        </div>
    </form>

    <script>
        const peminjamanSelect = document.getElementById('id_peminjaman');
        const tanggalPengembalianInput = document.getElementById('tanggal_pengembalian');

        peminjamanSelect.addEventListener('change', function () {
            const tanggalKembali = this.selectedOptions[0].dataset.tanggalKembali;
            if (tanggalKembali) {
                tanggalPengembalianInput.value = tanggalKembali;
            }
        });
    </script>

    <h2 class="panel-title">Riwayat pengembalian</h2>

    <div class="table-panel">
        <?php if (mysqli_num_rows($data) === 0) { ?>
            <p class="empty">Belum ada buku yang dikembalikan.</p>
        <?php } else { ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Peminjaman</th>
                        <th>Anggota</th>
                        <th>Tanggal pengembalian</th>
                        <th class="num">Denda</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = mysqli_fetch_assoc($data)) { ?>
                    <tr>
                        <td class="id" data-label="ID"><?= $r['id_pengembalian'] ?></td>
                        <td class="id" data-label="Peminjaman">#<?= $r['id_peminjaman'] ?></td>
                        <td class="cell-title" data-label="Anggota"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                        <td data-label="Tanggal pengembalian"><?= $r['tanggal_pengembalian'] ?></td>
                        <td class="num" data-label="Denda"><?= (int)$r['denda'] > 0 ? 'Rp ' . number_format((int)$r['denda'], 0, ',', '.') : '-' ?></td>
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
