<?php
include "auth.php";
include "connection.php";
$msg = "";

function getBookImageExtension($file)
{
	if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
		return null;
	}

	if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
		return false;
	}

	$image = getimagesize($file['tmp_name']);
	$extensions = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/webp' => 'webp'
	];

	return $image ? ($extensions[$image['mime']] ?? false) : false;
}

function saveBookImage($id, $file, $extension)
{
	$directory = __DIR__ . '/uploads/buku';
	if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
		return false;
	}

	$destination = $directory . '/buku_' . (int)$id . '.' . $extension;
	if (!move_uploaded_file($file['tmp_name'], $destination)) {
		return false;
	}

	foreach (glob($directory . '/buku_' . (int)$id . '.*') ?: [] as $oldImage) {
		if ($oldImage !== $destination) {
			unlink($oldImage);
		}
	}

	return true;
}

function getBookImagePath($id)
{
	$images = glob(__DIR__ . '/uploads/buku/buku_' . (int)$id . '.*');
	return $images ? 'uploads/buku/' . basename($images[0]) : null;
}

if (isset($_POST['simpan'])) {
	$extension = getBookImageExtension($_FILES['gambar_buku'] ?? []);
	if ($extension === false) {
		$msg = "Gambar harus berformat JPG, PNG, atau WEBP dan maksimal 5 MB.";
	} else {
		$judul = mysqli_real_escape_string($conn, $_POST['judul_buku']);
		$penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
		$penerbit = mysqli_real_escape_string($conn, $_POST['penerbit']);
		$tahun = (int)$_POST['tahun_terbit'];
		$stok = (int)$_POST['stok'];

		$query = mysqli_query($conn, "INSERT INTO buku(judul_buku,penulis,penerbit,tahun_terbit,stok) VALUES('$judul','$penulis','$penerbit',$tahun,$stok)");
		if ($query) {
			$id = mysqli_insert_id($conn);
			if ($extension !== null && !saveBookImage($id, $_FILES['gambar_buku'], $extension)) {
				$msg = "Data buku tersimpan, tetapi gambar gagal diunggah.";
			} else {
				header("Location: buku.php");
				exit;
			}
		} else {
			$msg = "Data buku gagal disimpan: " . mysqli_error($conn);
		}
	}
}

if (isset($_POST['update'])) {
	$extension = getBookImageExtension($_FILES['gambar_buku'] ?? []);
	if ($extension === false) {
		$msg = "Gambar harus berformat JPG, PNG, atau WEBP dan maksimal 5 MB.";
	} else {
		$id = (int)$_POST['id_buku'];
		$judul = mysqli_real_escape_string($conn, $_POST['judul_buku']);
		$penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
		$penerbit = mysqli_real_escape_string($conn, $_POST['penerbit']);
		$tahun = (int)$_POST['tahun_terbit'];
		$stok = (int)$_POST['stok'];

		$query = mysqli_query($conn, "UPDATE buku SET judul_buku='$judul',penulis='$penulis',penerbit='$penerbit',tahun_terbit=$tahun,stok=$stok WHERE id_buku=$id");
		if ($query && ($extension === null || saveBookImage($id, $_FILES['gambar_buku'], $extension))) {
			header("Location: buku.php");
			exit;
		}
		$msg = $query
			? "Data buku tersimpan, tetapi gambar gagal diunggah."
			: "Data buku gagal diperbarui: " . mysqli_error($conn);
	}
}

if (isset($_GET['hapus'])) {
	$id = (int)$_GET['hapus'];
	mysqli_query($conn, "DELETE FROM buku WHERE id_buku=$id");
	foreach (glob(__DIR__ . '/uploads/buku/buku_' . $id . '.*') ?: [] as $image) {
		unlink($image);
	}
	header("Location: buku.php");
	exit;
}

$edit = null;
if (isset($_GET['edit'])) {
	$id = (int)$_GET['edit'];
	$q = mysqli_query($conn, "SELECT * FROM buku WHERE id_buku=$id");
	$edit = mysqli_fetch_assoc($q);
}

$data = mysqli_query($conn, "SELECT * FROM buku ORDER BY id_buku DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Data Buku | Perpustakaan</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<main class="page">

	<div class="page-head">
		<h1>Data buku</h1>
		<p>Koleksi buku, stok, dan sampulnya.</p>
	</div>

	<?php if ($msg) { ?>
		<div class="alert"><?=htmlspecialchars($msg)?></div>
	<?php } ?>

	<form method="post" class="form" enctype="multipart/form-data">
		<h2 class="panel-title"><?= $edit ? 'Ubah buku' : 'Tambah buku' ?></h2>
		<?php if ($edit) { ?>
			<input type="hidden" name="id_buku" value="<?=$edit['id_buku']?>">
		<?php } ?>
		<div class="form-grid">
			<div class="field wide">
				<label for="judul_buku">Judul buku</label>
				<input id="judul_buku" name="judul_buku" value="<?=$edit?htmlspecialchars($edit['judul_buku']):''?>" required>
			</div>
			<div class="field">
				<label for="penulis">Penulis</label>
				<input id="penulis" name="penulis" value="<?=$edit?htmlspecialchars($edit['penulis']):''?>" required>
			</div>
			<div class="field">
				<label for="penerbit">Penerbit</label>
				<input id="penerbit" name="penerbit" value="<?=$edit?htmlspecialchars($edit['penerbit']):''?>" required>
			</div>
			<div class="field">
				<label for="tahun_terbit">Tahun terbit</label>
				<input type="number" id="tahun_terbit" name="tahun_terbit" value="<?=$edit?$edit['tahun_terbit']:''?>" required>
			</div>
			<div class="field">
				<label for="stok">Stok</label>
				<input type="number" id="stok" name="stok" min="0" value="<?=$edit?$edit['stok']:''?>" required>
			</div>
			<div class="field wide">
				<label for="gambar_buku">Sampul <span class="hint">(JPG, PNG, atau WEBP, maksimal 5 MB)</span></label>
				<input type="file" id="gambar_buku" name="gambar_buku" accept="image/jpeg,image/png,image/webp">
			</div>
			<?php if ($edit && ($gambarEdit = getBookImagePath($edit['id_buku']))) { ?>
				<div class="field wide">
					<img class="book-cover-preview" src="<?=htmlspecialchars($gambarEdit)?>" alt="Sampul <?=htmlspecialchars($edit['judul_buku'])?>">
				</div>
			<?php } ?>
		</div>
		<div class="form-actions">
			<?php if ($edit) { ?>
				<button name="update">Simpan perubahan</button>
				<a class="btn" href="buku.php">Batal</a>
			<?php } else { ?>
				<button name="simpan">Tambah buku</button>
			<?php } ?>
		</div>
	</form>

	<div class="table-panel"><div class="table-wrap">
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Sampul</th>
					<th>Judul</th>
					<th>Penerbit</th>
					<th class="num">Tahun</th>
					<th class="num">Stok</th>
					<th>Aksi</th>
				</tr>
			</thead>
			<tbody>
			<?php while ($r = mysqli_fetch_assoc($data)) { ?>
				<tr>
					<td class="id" data-label="ID"><?=$r['id_buku']?></td>
					<td data-label="Sampul">
						<?php $gambar = getBookImagePath($r['id_buku']); ?>
						<?php if ($gambar) { ?>
							<img class="book-cover" src="<?=htmlspecialchars($gambar)?>" alt="Sampul <?=htmlspecialchars($r['judul_buku'])?>">
						<?php } else { ?>
							<div class="book-cover-placeholder"><span>Belum ada sampul</span></div>
						<?php } ?>
					</td>
					<td class="cell-title" data-label="Judul">
						<span><?=htmlspecialchars($r['judul_buku'])?>
						<span class="cell-sub"><?=htmlspecialchars($r['penulis'])?></span></span>
					</td>
					<td data-label="Penerbit"><?=htmlspecialchars($r['penerbit'])?></td>
					<td class="num" data-label="Tahun"><?=$r['tahun_terbit']?></td>
					<td class="num" data-label="Stok"><?=$r['stok']?></td>
					<td data-label="Aksi">
						<span class="actions">
							<a class="btn" href="?edit=<?=$r['id_buku']?>">Edit</a>
							<a class="btn red" href="?hapus=<?=$r['id_buku']?>" onclick="return confirm('Hapus buku?')">Hapus</a>
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
