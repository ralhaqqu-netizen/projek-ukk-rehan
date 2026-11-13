
<?php
$hal = basename($_SERVER['PHP_SELF'], '.php');

$menu = [
    'index'        => 'Dashboard',
    'buku'         => 'Data Buku',
    'anggota'      => 'Data Anggota',
    'users'        => 'Data User',
    'peminjaman'   => 'Peminjaman',
    'pengembalian' => 'Pengembalian',
];

$role = $_SESSION['role'] ?? 'user';
?>

<aside class="sidebar">
    <a class="brand" href="index.php">
        <span class="brand-mark"></span>
        <span>Perpustakaan</span>
    </a>

    <nav class="nav">
        <?php foreach ($menu as $file => $label) { ?>
            <?php if ($file !== 'users' || $role === 'admin') { ?>
                <a href="<?= $file ?>.php"
                   <?= $hal === $file ? 'aria-current="page"' : '' ?>>
                    <?= $label ?>
                </a>
            <?php } ?>
        <?php } ?>

        <a href="logout.php" class="logout-link">Logout</a>
    </nav>

    <div class="sidebar-footer">
        Sistem Informasi Perpustakaan
    </div>
</aside>

<header class="topbar">
    <div class="topbar-in">
        <div class="who">
            <span class="who-name">
                <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
            </span>
            <span class="who-role">
                <?= htmlspecialchars($role) ?>
            </span>
        </div>
    </div>
</header>
