<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include "connection.php";

$error = "";
$sukses = "";
$old = ['username' => '', 'kelas' => '', 'alamat' => '', 'no_telp' => ''];

if (isset($_POST['daftar'])) {

    $old['username'] = trim($_POST['username'] ?? '');
    $old['kelas']    = trim($_POST['kelas'] ?? '');
    $old['alamat']   = trim($_POST['alamat'] ?? '');
    $old['no_telp']  = trim($_POST['no_telp'] ?? '');

    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    $username = mysqli_real_escape_string($conn, $old['username']);
    $kelas    = mysqli_real_escape_string($conn, $old['kelas']);
    $alamat   = mysqli_real_escape_string($conn, $old['alamat']);
    $telp     = mysqli_real_escape_string($conn, $old['no_telp']);
    $passEsc  = mysqli_real_escape_string($conn, $password);

    if ($old['username'] === '' || $old['kelas'] === '' || $old['alamat'] === '' || $old['no_telp'] === '') {

        $error = "Semua kolom wajib diisi.";

    } elseif ($password !== $password2) {

        $error = "Konfirmasi password tidak sama.";

    } else {

        $cekUser = mysqli_query($conn, "
            SELECT id_users
            FROM users
            WHERE username = '$username'
            LIMIT 1
        ");

        /*
         * Login mencocokkan akun ke anggota lewat nama,
         * jadi nama anggota dibuat sama dengan username.
         */
        $cekAnggota = mysqli_query($conn, "
            SELECT id_anggota
            FROM anggota
            WHERE nama_anggota = '$username'
            LIMIT 1
        ");

        if (mysqli_num_rows($cekUser) > 0) {

            $error = "Username sudah digunakan.";

        } elseif (mysqli_num_rows($cekAnggota) > 0) {

            $error = "Nama itu sudah terdaftar sebagai anggota. Pakai username lain.";

        } else {

            /*
             * Akun yang daftar dari halaman ini
             * otomatis menjadi user biasa, sekaligus
             * dibuatkan data anggotanya.
             */
            $role = "user";

            mysqli_begin_transaction($conn);

            $ok = mysqli_query($conn, "
                INSERT INTO users
                (username, password, role)
                VALUES
                ('$username', '$passEsc', '$role')
            ");

            $ok = $ok && mysqli_query($conn, "
                INSERT INTO anggota
                (nama_anggota, kelas, alamat, no_telp)
                VALUES
                ('$username', '$kelas', '$alamat', '$telp')
            ");

            if ($ok) {

                mysqli_commit($conn);
                $sukses = "Pendaftaran berhasil. Akun dan data anggota sudah dibuat, silakan login.";

            } else {

                mysqli_rollback($conn);
                $error = "Pendaftaran gagal: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar | Perpustakaan</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="auth">

    <aside class="auth-side">
        <div>
            <span class="brand"><span class="brand-mark" aria-hidden="true"></span>Perpustakaan</span>
            <h2>Jadi anggota, lalu pinjam bukunya.</h2>
            <p>Satu pendaftaran membuat akun login dan data anggota sekaligus.</p>
        </div>
        <div class="spines" aria-hidden="true">
            <span style="--h:120px;--w:30px;--c:#e2a62c"></span>
            <span style="--h:165px;--w:38px;--c:#1c6b73"></span>
            <span style="--h:140px;--w:26px;--c:#5fa3a9"></span>
            <span style="--h:178px;--w:34px;--c:#8a4b3b"></span>
            <span style="--h:130px;--w:40px;--c:#e9e3d3"></span>
            <span class="lean" style="--h:150px;--w:30px;--c:#2c7a55"></span>
            <span style="--h:170px;--w:36px;--c:#e2a62c"></span>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-box">

            <h1>Daftar anggota</h1>
            <p class="lead">Isi data diri dan buat password.</p>

            <?php if ($error) { ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <?php if ($sukses) { ?>

                <div class="alert ok"><?= htmlspecialchars($sukses) ?></div>

                <a class="btn solid" href="index.php">Ke halaman login</a>

            <?php } else { ?>

                <form method="post">

                    <div class="field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" maxlength="50"
                               value="<?= htmlspecialchars($old['username']) ?>"
                               autocomplete="username" required>
                        <span class="hint">Dipakai untuk login dan sebagai nama anggota.</span>
                    </div>

                    <div class="field">
                        <label for="kelas">Kelas</label>
                        <input type="text" id="kelas" name="kelas" maxlength="50"
                               placeholder="Contoh: XII RPL 1"
                               value="<?= htmlspecialchars($old['kelas']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="alamat">Alamat</label>
                        <input type="text" id="alamat" name="alamat" maxlength="255"
                               value="<?= htmlspecialchars($old['alamat']) ?>"
                               autocomplete="street-address" required>
                    </div>

                    <div class="field">
                        <label for="no_telp">No. telepon</label>
                        <input type="tel" id="no_telp" name="no_telp" maxlength="20"
                               value="<?= htmlspecialchars($old['no_telp']) ?>"
                               autocomplete="tel" required>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               autocomplete="new-password" required>
                    </div>

                    <div class="field">
                        <label for="password2">Ulangi password</label>
                        <input type="password" id="password2" name="password2"
                               autocomplete="new-password" required>
                    </div>

                    <button type="submit" name="daftar">Daftar</button>

                </form>

                <p class="auth-foot">
                    Sudah punya akun?
                    <a href="index.php">Login</a>
                </p>

            <?php } ?>

        </div>
    </main>

</div>

</body>

</html>
