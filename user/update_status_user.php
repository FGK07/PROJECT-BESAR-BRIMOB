<?php
session_start();
include "../koneksi.php";

// Pastikan user login
if (!isset($_SESSION['user'])) {
    $_SESSION['flash'] = "Silakan login terlebih dahulu.";
    header("Location: ../user/login_user.php");
    exit;
}

// Pastikan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: riwayat.php");
    exit;
}

$transaksi_id = (int) ($_POST['transaksi_id'] ?? 0);
$user_id = $_SESSION['user']['id'];

if ($transaksi_id <= 0) {
    $_SESSION['flash'] = "Transaksi tidak valid.";
    header("Location: riwayat.php");
    exit;
}

// Cek transaksi milik user & status
$stmt = $koneksi->prepare("SELECT status FROM transaksi WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaksi_id, $user_id);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trx) {
    $_SESSION['flash'] = "Transaksi tidak ditemukan atau bukan milik Anda.";
    header("Location: riwayat.php");
    exit;
}

if ($trx['status'] !== 'disetujui') {
    $_SESSION['flash'] = "Pesanan ini belum disetujui oleh admin atau sudah selesai.";
    header("Location: riwayat.php");
    exit;
}

// Update status menjadi selesai
$stmt = $koneksi->prepare("UPDATE transaksi SET status = 'selesai' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $transaksi_id, $user_id);
$stmt->execute();
$stmt->close();

// Redirect ke riwayat dengan asal halaman
$from = $_POST['from'] ?? '';
$kategori = $_POST['kategori'] ?? '';
$query = http_build_query(array_filter([
    'from' => $from,
    'kategori' => $kategori
]));

$_SESSION['flash'] = "✅ Pesanan #$transaksi_id telah diterima. Terima kasih telah berbelanja!";
header("Location: riwayat.php" . ($query ? "?$query" : ""));
exit;
