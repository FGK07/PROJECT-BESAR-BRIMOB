<?php
session_start();
include "../koneksi.php";

// === Cek login admin ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Cek ID kategori ===
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = "❌ ID kategori tidak valid!";
    header("Location: kelola_produk.php");
    exit;
}

// === Hapus kategori ===
$stmt = $koneksi->prepare("DELETE FROM kategori WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['flash'] = "🗑️ Kategori berhasil dihapus!";
} else {
    $_SESSION['flash'] = "❌ Gagal menghapus kategori: " . $stmt->error;
}
$stmt->close();

// Redirect kembali
header("Location: kelola_produk.php");
exit;
?>