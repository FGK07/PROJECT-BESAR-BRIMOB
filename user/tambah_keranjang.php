<?php
session_start();
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) $_POST['produk_id'];
    $ukuran   = $_POST['ukuran'] ?? '';
    $from     = $_POST['from'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $redirect = $_POST['redirect'] ?? "detail_produk.php?id=$id";

    // Buat keranjang jika belum ada
    $_SESSION['cart'] ??= [];

    // Gunakan key unik (id + ukuran)
    $key = "$id-$ukuran";
    $_SESSION['cart'][$key] = ($_SESSION['cart'][$key] ?? 0) + 1;

    // Flash message
    $_SESSION['flash'] = "Produk berhasil ditambahkan ke keranjang!";

    // Bangun URL redirect (tetap di halaman produk)
    $url = "../produk/$redirect";

    // Tambahkan parameter sesuai asal
    $params = [];
    if ($from) $params[] = "from=" . urlencode($from);
    if ($kategori) $params[] = "kategori=" . urlencode($kategori);

    if (!empty($params)) {
        $url .= "&" . implode("&", $params);
    }

    header("Location: $url");
    exit;
}
?>