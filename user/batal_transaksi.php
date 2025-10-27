<?php
session_start();
include "../koneksi.php";

// ✅ Pastikan user sudah login
if (!isset($_SESSION['user'])) {
  $_SESSION['flash'] = "Silakan login terlebih dahulu.";
  header("Location: ../user/login_user.php");
  exit;
}

$user_id = $_SESSION['user']['id'];
$transaksi_id = intval($_POST['transaksi_id'] ?? 0);

// ✅ Validasi ID transaksi
if ($transaksi_id <= 0) {
  $_SESSION['flash'] = "Transaksi tidak valid.";
  header("Location: ../user/riwayat.php");
  exit;
}

// =============================================================
// 🔹 Ambil status & jenis pesanan transaksi
// =============================================================
$stmt = $koneksi->prepare("SELECT status, jenis_pesanan, pre_order_id FROM transaksi WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $transaksi_id, $user_id);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trx) {
  $_SESSION['flash'] = "❌ Transaksi tidak ditemukan.";
  header("Location: ../user/riwayat.php");
  exit;
}

$status = strtolower(trim($trx['status']));
$jenis = strtolower(trim($trx['jenis_pesanan']));
$preOrderId = $trx['pre_order_id'] ?? null;

// =============================================================
// 🔹 Hanya bisa dibatalkan jika masih menunggu proses admin
// =============================================================
if (in_array($status, ['menunggu pembayaran', 'pending'])) {

  // =========================================================
  // ✅ Update status transaksi jadi "dibatalkan oleh user"
  // =========================================================
  $stmt = $koneksi->prepare("UPDATE transaksi SET status='dibatalkan oleh user' WHERE id=? AND user_id=?");
  $stmt->bind_param("ii", $transaksi_id, $user_id);
  $stmt->execute();
  $stmt->close();

  // =========================================================
  // 🔹 Jika pre-order → ubah status di tabel pre_order juga
  // =========================================================
  if ($jenis === 'pre order' && $preOrderId) {
    $stmt = $koneksi->prepare("UPDATE pre_order SET status='dibatalkan oleh user' WHERE id=?");
    $stmt->bind_param("i", $preOrderId);
    $stmt->execute();
    $stmt->close();
  }

  // =========================================================
  // 🔹 Jika ready stock → kembalikan stok produk
  // =========================================================
  if ($jenis === 'ready stock') {
    $stmt = $koneksi->prepare("SELECT produk_id, qty, ukuran FROM detail_transaksi WHERE transaksi_id=?");
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
      $produk_id = $row['produk_id'];
      $qty = $row['qty'];
      $ukuran = $row['ukuran'];

      // Kembalikan stok per ukuran
      $stmt2 = $koneksi->prepare("
        UPDATE produk_ukuran pu
        JOIN ukuran u ON u.id = pu.ukuran_id
        SET pu.stok = pu.stok + ?
        WHERE pu.produk_id = ? AND u.size = ?
      ");
      $stmt2->bind_param("iis", $qty, $produk_id, $ukuran);
      $stmt2->execute();
      $stmt2->close();
    }

    $stmt->close();

    // Sinkronkan total stok utama produk
    $koneksi->query("
      UPDATE produk p
      SET p.stok = (
        SELECT COALESCE(SUM(stok), 0) FROM produk_ukuran WHERE produk_id = p.id
      )
    ");
  }

  // =========================================================
  // 🎉 Pesan sukses
  // =========================================================
  $_SESSION['flash'] = "❌ Pesanan berhasil dibatalkan.";

} else {
  // =========================================================
  // 🚫 Tidak bisa dibatalkan (sudah diproses admin)
  // =========================================================
  $_SESSION['flash'] = "⚠️ Pesanan tidak dapat dibatalkan (sudah diproses admin).";
}

// =============================================================
// 🔁 Redirect kembali ke riwayat
// =============================================================
header("Location: ../user/riwayat.php");
exit;
?>
