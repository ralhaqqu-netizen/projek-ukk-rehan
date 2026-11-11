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

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string(
        $conn,
        $_POST['username']
    );

    $password = mysqli_real_escape_string(
        $conn,
        $_POST['password']
    );

    $query = mysqli_query($conn, "
        SELECT *
        FROM users
        WHERE username = '$username'
        AND password = '$password'
        LIMIT 1
    ");

    $user = mysqli_fetch_assoc($query);

    if ($user) {

        $_SESSION['user_id'] = $user['id_users'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

$cariAnggota = mysqli_query($conn, "
    SELECT id_anggota, nama_anggota
    FROM anggota
    WHERE nama_anggota = '" . mysqli_real_escape_string($conn, $user['username']) . "'
    LIMIT 1
");

$anggotaLogin = mysqli_fetch_assoc($cariAnggota);

if ($anggotaLogin) {
    $_SESSION['id_anggota'] = $anggotaLogin['id_anggota'];
    $_SESSION['nama_anggota'] = $anggotaLogin['nama_anggota'];
} else {
    $_SESSION['id_anggota'] = null;
    $_SESSION['nama_anggota'] = $user['username'];
}
        header("Location: index.php");
        exit;

    } else {

        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Perpustakaan</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="auth">

    <aside class="auth-side">
        <div>
            <span class="brand"><span class="brand-mark" aria-hidden="true"></span>Perpustakaan</span>
            <h2>Pinjam buku, kembalikan tepat waktu.</h2>
            <p>Masuk untuk mengajukan peminjaman dan melihat riwayatmu.</p>
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

            <h1>Login</h1>
            <p class="lead">Masukkan username dan password.</p>

            <?php if ($error) { ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php } ?>

            <form method="post">

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>

                <button type="submit" name="login">Login</button>

            </form>

            <p class="auth-foot">
                Belum punya akun?
                <a href="daftar.php">Daftar jadi anggota</a>
            </p>

        </div>
    </main>

</div>

</body>

</html>
