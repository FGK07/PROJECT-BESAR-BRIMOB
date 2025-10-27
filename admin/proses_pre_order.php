<?php
session_start();
include "../koneksi.php";

// === Cek login admin ===
if (!isset($_SESSION['admin'])) {
  header("Location: login_admin.php");
  exit;
}

// === Ambil ===
$id   = intval($_GET['id']);
$aksi = $_GET['aksi'] ?? '';

// === Ambil data pre-order
$stmt = $koneksi->prepare("SELECT * FROM pre_order WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$preorder = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$preorder) {
  $_SESSION['flash'] = "Pre-order tidak ditemukan.";
  header("Location: kelola_pre_order.php");
  exit;
}

/* ============================================================
   ✅ SETUJUI PRE-ORDER
============================================================ */
if ($aksi === "setuju") {
  // ✅ Ubah status pre_order
  $koneksi->query("UPDATE pre_order SET status='disetujui' WHERE id=$id");

  // ✅ Cek apakah sudah ada transaksi lama
  $stmt = $koneksi->prepare("
    SELECT id, metode_pembayaran_id 
    FROM transaksi 
    WHERE pre_order_id = ?
  ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $trx_lama = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($trx_lama) {
    // ✅ Jangan hapus transaksi lama, cukup ubah status jadi 'disetujui'
    $stmt = $koneksi->prepare("
      UPDATE transaksi 
      SET status='disetujui'
      WHERE id=?
    ");
    $stmt->bind_param("i", $trx_lama['id']);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = "✅ Pre-order disetujui dan transaksi lama diperbarui (bukti tetap aman).";
    header("Location: kelola_pre_order.php");
    exit;
  }

  // ✅ Jika belum ada transaksi sebelumnya → buat baru
  $metode_id = $trx_lama['metode_pembayaran_id'] ?? null;
  $stmt = $koneksi->prepare("
    INSERT INTO transaksi (
      user_id, metode_pembayaran_id, nama, alamat, no_hp,
      total, tanggal, status, pre_order_id, jenis_pesanan
    )
    SELECT 
      u.id, ?, u.nama, u.alamat, u.nomor_telepon,
      ?, NOW(), 'disetujui', po.id, 'pre order'
    FROM pre_order po
    JOIN users u ON po.user_id = u.id
    WHERE po.id = ?
  ");
  $stmt->bind_param("idi", $metode_id, $preorder['total'], $id);
  $stmt->execute();
  $transaksi_id = $koneksi->insert_id;

  // ✅ Masukkan detail produk ke detail_transaksi
  $stmt = $koneksi->prepare("
    SELECT produk_id, qty, total, ukuran 
    FROM pre_order WHERE id = ?
  ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $data = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $stmt2 = $koneksi->prepare("
    INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, harga, ukuran)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt2->bind_param("iiids", $transaksi_id, $data['produk_id'], $data['qty'], $preorder['total'], $data['ukuran']);
  $stmt2->execute();
  $stmt2->close();

  $_SESSION['flash'] = "✅ Pre-order disetujui dan otomatis masuk ke transaksi (status: Disetujui).";
  header("Location: kelola_pre_order.php");
  exit;
}

/* ============================================================
   ❌ TOLAK PRE-ORDER
============================================================ */
if ($aksi === "tolak") {
  // ❌ Ubah status pre_order dan transaksi tanpa menyentuh bukti_transfer
  $koneksi->query("UPDATE pre_order SET status='ditolak' WHERE id=$id");
  $koneksi->query("UPDATE transaksi SET status='ditolak' WHERE pre_order_id=$id");

  $_SESSION['flash'] = "❌ Pre-order telah ditolak (stok tidak diubah karena belum dikurangi).";
  header("Location: kelola_pre_order.php");
  exit;
}
?>
