<?php
include "auth.php";
include "connection.php";
if (isset($_POST['simpan'])) {
	$nama = mysqli_real_escape_string($conn, $_POST['nama_anggota']);
	$kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
	$alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
	$telp = mysqli_real_escape_string($conn, $_POST['no_telp']);

	mysqli_query($conn, "INSERT INTO anggota(nama_anggota,kelas,alamat,no_telp) VALUES('$nama','$kelas','$alamat','$telp')");
	header("Location: anggota.php");
	exit;
}

if (isset($_POST['update'])) {
	$id = (int)$_POST['id_anggota'];
	$nama = mysqli_real_escape_string($conn, $_POST['nama_anggota']);
	$kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
	$alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
	$telp = mysqli_real_escape_string($conn, $_POST['no_telp']);

	mysqli_query($conn, "UPDATE anggota SET nama_anggota='$nama',kelas='$kelas',alamat='$alamat',no_telp='$telp' WHERE id_anggota=$id");
	header("Location: anggota.php");
	exit;
}

if (isset($_GET['hapus'])) {
	$id = (int)$_GET['hapus'];
	mysqli_query($conn, "DELETE FROM anggota WHERE id_anggota=$id");
	header("Location: anggota.php");
	exit;
}

$edit = null;
if (isset($_GET['edit'])) {
	$id = (int)$_GET['edit'];
	$q = mysqli_query($conn, "SELECT * FROM anggota WHERE id_anggota=$id");
	$edit = mysqli_fetch_assoc($q);
}

$data = mysqli_query($conn, "SELECT * FROM anggota ORDER BY id_anggota DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Data Anggota | Perpustakaan</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<main class="page">

	<div class="page-head">
		<h1>Data anggota</h1>
		<p>Anggota yang daftar lewat halaman daftar muncul di sini otomatis.</p>
	</div>

	<form method="post" class="form">
		<h2 class="panel-title"><?= $edit ? 'Ubah anggota' : 'Tambah anggota' ?></h2>
		<?php if ($edit) { ?>
			<input type="hidden" name="id_anggota" value="<?=$edit['id_anggota']?>">
		<?php } ?>
		<div class="form-grid">
			<div class="field">
				<label for="nama_anggota">Nama</label>
				<input id="nama_anggota" name="nama_anggota" value="<?=$edit?htmlspecialchars($edit['nama_anggota']):''?>" required>
			</div>
			<div class="field">
				<label for="kelas">Kelas</label>
				<input id="kelas" name="kelas" value="<?=$edit?htmlspecialchars($edit['kelas']):''?>" required>
			</div>
			<div class="field">
				<label for="alamat">Alamat</label>
				<input id="alamat" name="alamat" value="<?=$edit?htmlspecialchars($edit['alamat']):''?>" required>
			</div>
			<div class="field">
				<label for="no_telp">No. telepon</label>
				<input id="no_telp" name="no_telp" value="<?=$edit?htmlspecialchars($edit['no_telp']):''?>" required>
			</div>
		</div>
		<div class="form-actions">
			<?php if ($edit) { ?>
				<button name="update">Simpan perubahan</button>
				<a class="btn" href="anggota.php">Batal</a>
			<?php } else { ?>
				<button name="simpan">Tambah anggota</button>
			<?php } ?>
		</div>
	</form>

	<div class="table-panel"><div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Nama</th>
					<th>Kelas</th>
					<th>Alamat</th>
					<th>No. telepon</th>
					<th>Aksi</th>
				</tr>
			</thead>
			<tbody>
			<?php while ($r = mysqli_fetch_assoc($data)) { ?>
				<tr>
					<td class="id" data-label="ID"><?=$r['id_anggota']?></td>
					<td class="cell-title" data-label="Nama"><?=htmlspecialchars($r['nama_anggota'])?></td>
					<td data-label="Kelas"><?=htmlspecialchars($r['kelas'])?></td>
					<td data-label="Alamat"><?=htmlspecialchars($r['alamat'])?></td>
					<td data-label="No. telepon"><?=htmlspecialchars($r['no_telp'])?></td>
					<td data-label="Aksi">
						<span class="actions">
							<a class="btn" href="?edit=<?=$r['id_anggota']?>">Edit</a>
							<a class="btn red" href="?hapus=<?=$r['id_anggota']?>" onclick="return confirm('Hapus anggota?')">Hapus</a>
						</span>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div></div>

</main>
</body>
</html>
