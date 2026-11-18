<?php
include "auth.php";
include "connection.php";

/* Tarif denda: Rp per hari */
const TARIF_DENDA = 5000;

$msg = "";
$sessionAnggotaId = isset($_SESSION['id_anggota']) ? (int)$_SESSION['id_anggota'] : 0;
$userRole = $_SESSION['role'] ?? 'user';
$filterAnggota = $userRole === 'user' ? " AND p.id_anggota = $sessionAnggotaId" : "";

/* Hanya admin/petugas yang boleh mengubah denda secara manual */
$bolehOverride = in_array($userRole, ['admin', 'petugas'], true);

/* Hitung jumlah hari terlambat (0 jika tepat waktu / lebih awal) */
function hitungHariTerlambat($jatuhTempo, $tglKembali)
{
    $jatuh = new DateTime($jatuhTempo);
    $kembali = new DateTime($tglKembali);

    if ($kembali <= $jatuh) {
        return 0;
    }

    return (int)$jatuh->diff($kembali)->days;
}

if (isset($_POST['kembali'])) {

    $pid = (int)$_POST['id_peminjaman'];
    $tgl = $_POST['tanggal_pengembalian'] ?? '';

    $tglValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)
        && checkdate((int)substr($tgl, 5, 2), (int)substr($tgl, 8, 2), (int)substr($tgl, 0, 4));

    $overrideDenda = $bolehOverride
        && isset($_POST['override_denda'])
        && $_POST['override_denda'] == '1';

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

    if (!$tglValid) {

        $msg = "Tanggal pengembalian tidak valid.";

    } elseif (empty($details)) {

        $msg = "Peminjaman tidak ditemukan atau sudah dikembalikan.";

    } else {

        $hari = hitungHariTerlambat($tanggalJatuh, $tgl);

        /* 1 hari = Rp 5.000, 2 hari = Rp 10.000, dst. */
        $denda = $hari * TARIF_DENDA;

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

            header("Location: pengembalian.php?ok=1&denda=" . (int)$denda);
            exit;

        } else {

            mysqli_rollback($conn);

            $msg = "Pengembalian gagal: " . mysqli_error($conn);
        }
    }
}

/* Pesan sukses setelah redirect */
$okMsg = "";
if (isset($_GET['ok'])) {
    $dendaOk = (int)($_GET['denda'] ?? 0);
    $okMsg = $dendaOk > 0
        ? "Buku berhasil dikembalikan. Denda keterlambatan: Rp " . number_format($dendaOk, 0, ',', '.') . "."
        : "Buku berhasil dikembalikan. Tidak ada denda.";
}


/* DATA PEMINJAMAN UNTUK FORM (satu baris per peminjaman) */

$pinjam = mysqli_query($conn, "
    SELECT 
        p.id_peminjaman,
        a.nama_anggota,
        GROUP_CONCAT(
            CONCAT(b.judul_buku, ' (', d.jumlah, ')')
            SEPARATOR ', '
        ) AS daftar_buku,
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
    GROUP BY p.id_peminjaman, a.nama_anggota, p.tanggal_kembali
    ORDER BY p.id_peminjaman DESC
");

$tanggalPengembalianDefault = $_POST['tanggal_pengembalian'] ?? date('Y-m-d');
$overrideChecked = $bolehOverride
    && isset($_POST['override_denda'])
    && $_POST['override_denda'] == '1';
$dendaManualDefault = max(0, (int)($_POST['denda_manual'] ?? 0));


/* DATA PENGEMBALIAN */

$data = mysqli_query($conn, "
    SELECT 
        k.*,
        p.tanggal_kembali AS jatuh_tempo,
        a.nama_anggota
    FROM pengembalian k
    JOIN peminjaman p 
        ON k.id_peminjaman = p.id_peminjaman
    JOIN anggota a 
        ON p.id_anggota = a.id_anggota
    WHERE 1=1$filterAnggota
    ORDER BY k.id_pengembalian DESC
");

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
        <p>Pilih pinjaman yang aktif, lalu catat tanggal pengembalian. Denda keterlambatan dihitung otomatis: Rp <?= number_format(TARIF_DENDA, 0, ',', '.') ?> per hari.</p>
    </div>

    <?php if ($msg) { ?>
        <div class="alert"><?= htmlspecialchars($msg) ?></div>
    <?php } ?>

    <?php if ($okMsg) { ?>
        <div class="alert ok"><?= htmlspecialchars($okMsg) ?></div>
    <?php } ?>

    <form method="post" class="form">
        <h2 class="panel-title">Kembalikan buku</h2>
        <div class="form-grid">
            <div class="field wide">
                <label for="id_peminjaman">Peminjaman</label>
                <select name="id_peminjaman" id="id_peminjaman" required>
                    <?php while ($p = mysqli_fetch_assoc($pinjam)) { ?>
                        <option
                            value="<?= (int)$p['id_peminjaman'] ?>"
                            data-tanggal-kembali="<?= htmlspecialchars($p['tanggal_kembali']) ?>"
                            <?= (isset($_POST['id_peminjaman']) && (int)$_POST['id_peminjaman'] === (int)$p['id_peminjaman']) ? 'selected' : '' ?>
                        >
                            ID <?= (int)$p['id_peminjaman'] ?>
                            - <?= htmlspecialchars($p['nama_anggota']) ?>
                            - <?= htmlspecialchars($p['daftar_buku']) ?>
                            (jatuh tempo <?= htmlspecialchars($p['tanggal_kembali']) ?>)
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
            <div class="field">
                <label>Keterlambatan</label>
                <div class="static-value" id="info-hari">-</div>
            </div>
            <div class="field">
                <label>Denda</label>
                <div class="static-value" id="info-denda">Rp 0</div>
            </div>

            <?php if ($bolehOverride) { ?>
            <div class="field">
                <label for="override_denda">
                    <input
                        type="checkbox"
                        id="override_denda"
                        name="override_denda"
                        value="1"
                        <?= $overrideChecked ? 'checked' : '' ?>
                    >
                    Ubah denda manual
                </label>
                <input
                    type="number"
                    id="denda_manual"
                    name="denda_manual"
                    min="0"
                    step="500"
                    value="<?= $dendaManualDefault ?>"
                    <?= $overrideChecked ? '' : 'disabled' ?>
                >
                <span class="hint">Untuk keringanan atau kebijakan khusus (mis. buku rusak/hilang).</span>
            </div>
            <?php } ?>
        </div>
        <div class="form-actions">
            <button name="kembali">Kembalikan buku</button>
        </div>
    </form>

    <script>
        const TARIF_DENDA = <?= (int)TARIF_DENDA ?>;

        const peminjamanSelect = document.getElementById('id_peminjaman');
        const tanggalInput = document.getElementById('tanggal_pengembalian');
        const infoHari = document.getElementById('info-hari');
        const infoDenda = document.getElementById('info-denda');
        const overrideBox = document.getElementById('override_denda');
        const dendaManual = document.getElementById('denda_manual');

        function formatRupiah(angka) {
            return 'Rp ' + Number(angka).toLocaleString('id-ID');
        }

        function hitungDenda() {
            const opt = peminjamanSelect.selectedOptions[0];

            if (!opt || !tanggalInput.value) {
                infoHari.textContent = '-';
                infoDenda.textContent = 'Rp 0';
                return;
            }

            const jatuhTempo = new Date(opt.dataset.tanggalKembali);
            const kembali = new Date(tanggalInput.value);

            const selisihHari = Math.round((kembali - jatuhTempo) / 86400000);
            const hari = Math.max(0, selisihHari);

            infoHari.textContent = hari > 0 ? hari + ' hari terlambat' : 'Tepat waktu';

            if (overrideBox && overrideBox.checked) {
                infoDenda.textContent = formatRupiah(parseInt(dendaManual.value, 10) || 0) + ' (manual)';
            } else {
                infoDenda.textContent = formatRupiah(hari * TARIF_DENDA);
            }
        }

        peminjamanSelect.addEventListener('change', hitungDenda);
        tanggalInput.addEventListener('input', hitungDenda);

        if (overrideBox) {
            overrideBox.addEventListener('change', function () {
                dendaManual.disabled = !this.checked;
                hitungDenda();
            });
            dendaManual.addEventListener('input', hitungDenda);
        }

        hitungDenda();
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
                        <th>Jatuh tempo</th>
                        <th>Tanggal pengembalian</th>
                        <th class="num">Terlambat</th>
                        <th class="num">Denda</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($r = mysqli_fetch_assoc($data)) {
                    $hariTelat = hitungHariTerlambat($r['jatuh_tempo'], $r['tanggal_pengembalian']);
                ?>
                    <tr>
                        <td class="id" data-label="ID"><?= $r['id_pengembalian'] ?></td>
                        <td class="id" data-label="Peminjaman">#<?= $r['id_peminjaman'] ?></td>
                        <td class="cell-title" data-label="Anggota"><?= htmlspecialchars($r['nama_anggota']) ?></td>
                        <td data-label="Jatuh tempo"><?= htmlspecialchars($r['jatuh_tempo']) ?></td>
                        <td data-label="Tanggal pengembalian"><?= $r['tanggal_pengembalian'] ?></td>
                        <td class="num" data-label="Terlambat"><?= $hariTelat > 0 ? $hariTelat . ' hari' : '-' ?></td>
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
