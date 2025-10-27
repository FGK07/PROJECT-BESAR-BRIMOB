<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? null;

    // Hapus item dari session cart
    if ($key && isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['flash'] = "✅ Produk dihapus dari keranjang!";
    }

    // Ambil asal halaman (default: homepage)
    $from = $_POST['from'] ?? 'homepage';
    $kategori = $_POST['kategori'] ?? '';

    // Buat redirect dinamis ke keranjang
    $redirect = "keranjang.php?from=" . urlencode($from);

    // Jika asal dari kategori, tambahkan param kategori
    if ($from === "kategori" && !empty($kategori)) {
        $redirect .= "&kategori=" . urlencode($kategori);
    }

    // Redirect ke halaman keranjang
    header("Location: $redirect");
    exit;
}
?>