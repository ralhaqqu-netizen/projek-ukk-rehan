Sistem Informasi Perpustakaan Berbasis Web

Cara Menjalankanya
1. Salin folder project ke dalam folder "htdocs" (XAMPP) atau "www" (Laragon), misalnya "htdocs/perpustakaan".
2. Jalankan Apache dan MySQL melalui XAMPP.
3. Buka phpMyAdmin melalui "http://localhost/phpmyadmin".
4. Buat database dengan nama "db_rehan_xiirpl1_paket4".
5. Import file database SQL ke dalam database tersebut.
6. Sesuaikan pengaturan koneksi database pada file "connection.php".
7. Buka aplikasi melalui alamat "http://localhost/perpustakaan/".

Akun Contoh

- Admin: "admin" / "admin123"
- Petugas: "petugas" / "petugas123"
- User: "andi" / "andi123"
- User: "budi" / "budi123"

Struktur Database

Database yang digunakan bernama "db_rehan_xiirpl1_paket4" dan terdiri dari enam tabel:

- "anggota" — menyimpan data anggota perpustakaan.
- "buku" — menyimpan data koleksi buku.
- "peminjaman" — menyimpan transaksi peminjaman.
- "peminjaman_detail" — menyimpan rincian buku yang dipinjam.
- "pengembalian" — menyimpan data pengembalian
- "users" — menyimpan akun dan level pengguna.

Fitur Aplikasi

- Login dan Logout — mengelola akses pengguna.
- Dashboard — menampilkan ringkasan data perpustakaan.
- Data Buku — menambah, menampilkan, mengubah, dan menghapus data buku.
- Data Anggota — mengelola informasi anggota perpustakaan.
- Peminjaman — mencatat transaksi peminjaman buku.
- Persetujuan Peminjaman — admin atau petugas menyetujui pengajuan peminjaman.
- Pengembalian — mencatat pengembalian buku 
- Data Pengguna — mengelola akun pengguna.

Hak Akses Pengguna

- Admin — mengelola seluruh data dan akun pengguna.
- Petugas — mengelola data buku, anggota, peminjaman, dan pengembalian.
- User — melihat koleksi buku dan melakukan peminjaman.

Teknologi yang Digunakan

- PHP — bahasa pemrograman aplikasi.
- MySQL/MariaDB — pengelolaan database.
- HTML — membuat struktur halaman.
- CSS — mengatur tampilan aplikasi.
- JavaScript — mendukung interaksi halaman.
- XAMPP — server lokal untuk menjalankan aplikasi.
