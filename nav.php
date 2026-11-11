<?php
$hal = basename($_SERVER['PHP_SELF'], '.php');
$menu = [
    'index'        => 'Dashboard',
    'buku'         => 'Buku',
    'anggota'      => 'Anggota',
    'users'        => 'User',
    'peminjaman'   => 'Peminjaman',
    'pengembalian' => 'Pengembalian',
];
?>
<header class="topbar">
    <div class="topbar-in">
        <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true"></span>Perpustakaan</a>

        <nav class="nav" aria-label="Menu utama">
            <?php foreach ($menu as $file => $label) { ?>
                <a href="<?= $file ?>.php"<?= $hal === $file ? ' aria-current="page"' : '' ?>><?= $label ?></a>
            <?php } ?>
        </nav>

        <div class="who">
            <span class="who-name"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
            <span class="who-role"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
            <a href="logout.php">Keluar</a>
        </div>
    </div>
</header>
