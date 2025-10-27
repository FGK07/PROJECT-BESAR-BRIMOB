<?php
session_start();
include "../koneksi.php"; 
$q = trim($_GET['search']);
// Pastikan user login
if (!isset($_SESSION['user'])) {
    header("Location: login_user.php?from=daftar_favorit");
    exit;
}

$user_id   = $_SESSION['user']['id'];
$produk_id = intval($_POST['produk_id'] ?? 0);

// Tambah ke Favorit
if (isset($_POST['tambah'])) {
    $query = "INSERT IGNORE INTO favorit (user_id, produk_id) VALUES ($user_id, $produk_id)";
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['flash'] = "✅ Produk berhasil ditambahkan ke favorit.";
    } else {
        $_SESSION['flash'] = "❌ Gagal menambahkan: " . mysqli_error($koneksi);
    }
}

// Hapus dari Favorit
if (isset($_POST['hapus'])) {
    $query = "DELETE FROM favorit WHERE user_id = $user_id AND produk_id = $produk_id";
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['flash'] = "🗑️ Produk dihapus dari favorit.";
    } else {
        $_SESSION['flash'] = "❌ Gagal menghapus: " . mysqli_error($koneksi);
    }
}

// Redirect balik ke halaman sebelumnya
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
