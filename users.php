<?php
include "auth.php";
include "connection.php";
if(isset($_POST['simpan'])){
 $u=mysqli_real_escape_string($conn,$_POST['username']); $p=mysqli_real_escape_string($conn,$_POST['password']); $r=mysqli_real_escape_string($conn,$_POST['role']);
 $r = in_array($r, ['admin','petugas','user']) ? $r : 'user';
 mysqli_query($conn,"INSERT INTO users(username,password,role) VALUES('$u','$p','$r')");
 header("Location: users.php"); exit;
}
if(isset($_POST['update'])){
 $id=(int)$_POST['id_users'];
 $u=mysqli_real_escape_string($conn,$_POST['username']); $p=mysqli_real_escape_string($conn,$_POST['password']); $r=mysqli_real_escape_string($conn,$_POST['role']);
 $r = in_array($r, ['admin','petugas','user']) ? $r : 'user';
 mysqli_query($conn,"UPDATE users SET username='$u',password='$p',role='$r' WHERE id_users=$id");
 header("Location: users.php"); exit;
}
if(isset($_GET['hapus'])){ $id=(int)$_GET['hapus'];
 $cek = mysqli_query($conn, "SELECT role FROM users WHERE id_users=$id LIMIT 1");
 $row = mysqli_fetch_assoc($cek);
 if($row && !in_array($row['role'], ['admin','user'])) { header("Location: users.php"); exit; }
 mysqli_query($conn,"DELETE FROM users WHERE id_users=$id"); header("Location: users.php"); exit; }
$edit=null;
if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $q=mysqli_query($conn,"SELECT * FROM users WHERE id_users=$id"); $edit=mysqli_fetch_assoc($q); }
$data=mysqli_query($conn,"SELECT * FROM users ORDER BY id_users DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Data User | Perpustakaan</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<main class="page">

	<div class="page-head">
		<h1>Data user</h1>
		<p>Akun yang bisa login ke sistem beserta perannya.</p>
	</div>

	<form method="post" class="form">
		<h2 class="panel-title"><?= $edit ? 'Ubah user' : 'Tambah user' ?></h2>
		<?php if ($edit) { ?><input type="hidden" name="id_users" value="<?=$edit['id_users']?>"><?php } ?>
		<div class="form-grid">
			<div class="field">
				<label for="username">Username</label>
				<input id="username" name="username" value="<?=$edit?htmlspecialchars($edit['username']):''?>" required>
			</div>
			<div class="field">
				<label for="password">Password</label>
				<input id="password" name="password" value="<?=$edit?htmlspecialchars($edit['password']):''?>" required>
			</div>
			<div class="field">
				<label for="role">Peran</label>
				<select id="role" name="role">
					<option value="user" <?=($edit && $edit['role']=='user')?'selected':''?>>User</option>
					<option value="petugas" <?=($edit && $edit['role']=='petugas')?'selected':''?>>Petugas</option>
					<option value="admin" <?=($edit && $edit['role']=='admin')?'selected':''?>>Admin</option>
				</select>
			</div>
		</div>
		<div class="form-actions">
			<?php if ($edit) { ?>
				<button name="update">Simpan perubahan</button>
				<a class="btn" href="users.php">Batal</a>
			<?php } else { ?>
				<button name="simpan">Tambah user</button>
			<?php } ?>
		</div>
	</form>

	<div class="table-panel"><div class="table-wrap">
		<table>
			<thead>
				<tr><th>ID</th><th>Username</th><th>Password</th><th>Peran</th><th>Aksi</th></tr>
			</thead>
			<tbody>
			<?php while ($r = mysqli_fetch_assoc($data)) { ?>
				<tr>
					<td class="id" data-label="ID"><?=$r['id_users']?></td>
					<td class="cell-title" data-label="Username"><?=htmlspecialchars($r['username'])?></td>
					<td data-label="Password"><?=htmlspecialchars($r['password'])?></td>
					<td data-label="Peran"><span class="badge <?=htmlspecialchars($r['role'])?>"><?=htmlspecialchars($r['role'])?></span></td>
					<td data-label="Aksi">
						<span class="actions">
							<a class="btn" href="?edit=<?=$r['id_users']?>">Edit</a>
							<a class="btn red" href="?hapus=<?=$r['id_users']?>" onclick="return confirm('Hapus user?')">Hapus</a>
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
