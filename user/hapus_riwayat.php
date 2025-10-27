<?php
session_start();
include "../koneksi.php";

// Cek login
if (!isset($_SESSION['user'])) {
    $_SESSION['flash'] = "Silakan login terlebih dahulu.";
    header("Location: ../user/login_user.php");
    exit;
}

// Hapus transaksi jika request valid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaksi_id = (int) ($_POST['transaksi_id'] ?? 0);
    $user_id      = $_SESSION['user']['id'];

    // Cek apakah transaksi milik user
    $stmt = $koneksi->prepare("SELECT id FROM transaksi WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transaksi_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['flash'] = "Transaksi tidak ditemukan atau bukan milik Anda.";
        header("Location: riwayat.php");
        exit;
    }

    // (Opsional) Kembalikan stok jika transaksi masih pending
    $stmt = $koneksi->prepare("SELECT status FROM transaksi WHERE id = ?");
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();
    $status = $stmt->get_result()->fetch_assoc()['status'] ?? '';

    if ($status === 'pending') {
        $stmt = $koneksi->prepare("SELECT produk_id, qty FROM detail_transaksi WHERE transaksi_id = ?");
        $stmt->bind_param("i", $transaksi_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $stmt2 = $koneksi->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
            $stmt2->bind_param("ii", $row['qty'], $row['produk_id']);
            $stmt2->execute();
        }
    }

    // Hapus detail transaksi
    $stmt = $koneksi->prepare("DELETE FROM detail_transaksi WHERE transaksi_id = ?");
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();

    // Hapus transaksi utama
    $stmt = $koneksi->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();

    // Flash message & redirect
    $_SESSION['flash'] = "Riwayat transaksi berhasil dihapus.";

    // Flash message & redirect
    $_SESSION['flash'] = "Riwayat transaksi berhasil dihapus.";

    $from = $_POST['from'] ?? '';
    $kategori = $_POST['kategori'] ?? '';

    if ($from === 'kategori' && !empty($kategori)) {
        $redirect = "riwayat.php?from=kategori&kategori=" . urlencode($kategori);
    } elseif ($from === 'checkout' && !empty($kategori)) {
        $redirect = "riwayat.php?from=checkout&kategori=" . urlencode($kategori);
    } elseif ($from === 'keranjang' && !empty($kategori)) {
        $redirect = "riwayat.php?from=keranjang&kategori=" . urlencode($kategori);
    } elseif ($from === 'homepage') {
        $redirect = "riwayat.php?from=homepage";
    } else {
        $redirect = "riwayat.php";
    }

    // lakukan redirect ke URL dinamis
    header("Location: $redirect");
    exit;
}
