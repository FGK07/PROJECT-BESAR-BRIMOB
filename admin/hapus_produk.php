<?php
session_start();
include "../koneksi.php";

// === Pastikan admin login ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Validasi input ID ===
if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    die("Produk tidak valid!");
}
$produkId = intval($_POST['id']);

// === Ambil parameter asal ===
$from = $_POST['from'] ?? '';
$kategori = $_POST['kategori'] ?? '';

// === Default halaman kembali ===
$backUrl = "dashboard_admin.php";

// === Tentukan URL kembali dinamis ====
if (!empty($kategori)) {
    // === Jika ada parameter kategori → prioritas utama ke halaman kategori ===
    $backUrl = "kategori.php?nama=" . urlencode($kategori);

    // === Jika dari halaman kelola produk ===
} elseif ($from === 'kelola_produk') {

    if (!empty($_POST['kategori'])) {
        $backUrl = "kelola_produk.php?from=kategori&kategori=" . urlencode($kategori);
    } else {
        $backUrl = "kelola_produk.php?from=dashboard_admin";
    }

    // === Jika hapus dari halaman detail produk yang punya kategori ===
} elseif ($from === 'detail_produk' && !empty($_POST['kategori'])) {
    $backUrl = "kategori.php?nama=" . urlencode($kategori);
}

// === Cek apakah produk ada ===
$stmt = $koneksi->prepare("SELECT id FROM produk WHERE id = ?");
$stmt->bind_param("i", $produkId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    $_SESSION['flash'] = "❌ Produk tidak ditemukan!";
    header("Location: " . $backUrl);
    exit;
}

// === Hapus juga ukuran terkait ===
$delUkuran = $koneksi->prepare("DELETE FROM produk_ukuran WHERE produk_id = ?");
$delUkuran->bind_param("i", $produkId);
$delUkuran->execute();
$delUkuran->close();

// === Hapus produk utama ===
$stmt = $koneksi->prepare("DELETE FROM produk WHERE id = ?");
$stmt->bind_param("i", $produkId);

if ($stmt->execute()) {
    $_SESSION['flash'] = "✅ Produk berhasil dihapus!";
} else {
    $_SESSION['flash'] = "❌ Gagal menghapus produk: " . $stmt->error;
}
$stmt->close();

// === Redirect kembali ke halaman asal ===
header("Location: " . $backUrl);
exit;
?>