<?php
session_start();
include "../koneksi.php";

// === Validasi login admin ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Ambil ===
$id     = intval($_POST['id'] ?? 0);
$aksi   = strtolower(trim($_POST['aksi'] ?? ''));
$tipe   = strtolower(trim($_POST['tipe'] ?? '')); // "transaksi" atau "preorder"

// === Validasi ===
if ($id <= 0 || empty($aksi) || empty($tipe)) {
    $_SESSION['flash'] = "Data tidak valid.";
    header("Location: kelola_transaksi.php");
    exit;
}

// === CASE 1: TRANSAKSI BIASA ===
if ($tipe === 'transaksi') {

    // === Ambil data transaksi ===
    $stmt = $koneksi->prepare("SELECT * FROM transaksi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $trx = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$trx) {
        $_SESSION['flash'] = "Transaksi tidak ditemukan.";
        header("Location: kelola_transaksi.php");
        exit;
    }

    // === ✅ SETUJUI TRANSAKSI ===
    if ($aksi === 'setuju') {
        $stmt = $koneksi->prepare("UPDATE transaksi SET status='disetujui' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash'] = "✅ Transaksi #$id disetujui.";
        header("Location: kelola_transaksi.php");
        exit;
    }

    // === ❌ TOLAK TRANSAKSI ===
    if ($aksi === 'tolak') {
        // === Ambil detail transaksi untuk kembalikan stok ===
        $stmt = $koneksi->prepare("SELECT produk_id, qty, ukuran FROM detail_transaksi WHERE transaksi_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $produkList = $stmt->get_result();
        $stmt->close();

        while ($p = $produkList->fetch_assoc()) {
            $stmt2 = $koneksi->prepare(" UPDATE produk_ukuran pu JOIN ukuran u ON pu.ukuran_id = u.id SET pu.stok = pu.stok + ? WHERE pu.produk_id = ? AND u.size = ? ");
            $stmt2->bind_param("iis", $p['qty'], $p['produk_id'], $p['ukuran']);
            $stmt2->execute();
            $stmt2->close();
        }

        // === Sinkronkan stok utama ===
        $koneksi->query(" UPDATE produk p SET p.stok = (SELECT COALESCE(SUM(stok), 0) FROM produk_ukuran WHERE produk_id = p.id)");

        // Update status transaksi
        $stmt = $koneksi->prepare("UPDATE transaksi SET status='ditolak' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash'] = "❌ Transaksi #$id ditolak dan stok dikembalikan.";
        header("Location: kelola_transaksi.php");
        exit;
    }
    
    // === CASE 2: PRE-ORDER ===
} elseif ($tipe === 'preorder') {

    // === Cari ID pre-order asli  ===
    $stmt = $koneksi->prepare("SELECT pre_order_id FROM transaksi WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $preOrderId = $res['pre_order_id'] ?? $id;

    // === Ambil data pre-order ===
    $stmt = $koneksi->prepare("SELECT * FROM pre_order WHERE id=?");
    $stmt->bind_param("i", $preOrderId);
    $stmt->execute();
    $pre = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // === Validasi ===
    if (!$pre) {
        $_SESSION['flash'] = "Pre-order tidak ditemukan.";
        header("Location: kelola_transaksi.php");
        exit;
    }

    // ===✅ SETUJUI PRE-ORDER ===
    if ($aksi === 'setuju') {

        // === Ubah status pre_order ===
        $koneksi->query("UPDATE pre_order SET status='disetujui' WHERE id=$preOrderId");

        // === Cek transaksi lama (kalau ada update aja biar aman) ===
        $trxLama = $koneksi->query("SELECT id, metode_pembayaran_id FROM transaksi WHERE pre_order_id=$preOrderId")->fetch_assoc();

        if ($trxLama) {
            $stmt = $koneksi->prepare("UPDATE transaksi SET status='disetujui', total=?, tanggal=NOW() WHERE id=?");
            $stmt->bind_param("di", $pre['total'], $trxLama['id']);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash'] = "✅ Pre-Order disetujui.";
            header("Location: kelola_transaksi.php");
            exit;
        }

        // === Jika belum ada transaksi sebelumnya ===
        $metode_id = $pre['metode_pembayaran_id'] ?? 1; // fallback ke COD

        // === Tentukan status awal ===
        $status_awal = ($metode_id == 1) ? 'pending' : 'disetujui';


        // === Buat transaksi baru ===
        $stmt = $koneksi->prepare(" INSERT INTO transaksi (user_id, metode_pembayaran_id, nama, alamat, no_hp, total, tanggal, status, pre_order_id, jenis_pesanan)
                                    SELECT u.id, ?, u.nama, u.alamat, u.nomor_telepon, ?, NOW(), ?, po.id, 'pre order' FROM pre_order po
                                    JOIN users u ON po.user_id = u.id WHERE po.id = ?
        ");
        $stmt->bind_param("idss", $metode_id, $pre['total'], $status_awal, $preOrderId);
        $stmt->execute();
        $transaksi_id = $koneksi->insert_id;
        $stmt->close();

        if ($transaksi_id <= 0) {
            $_SESSION['flash'] = "❌ Gagal membuat transaksi baru (ID tidak valid).";
            header("Location: kelola_transaksi.php");
            exit;
        }

        // === Tambahkan detail produk ===
        $stmt2 = $koneksi->prepare(" INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, harga, ukuran) VALUES (?, ?, ?, ?, ?) ");
        $stmt2->bind_param("iiids", $transaksi_id, $pre['produk_id'], $pre['qty'], $pre['total'], $pre['ukuran']);
        $stmt2->execute();
        $stmt2->close();

        $_SESSION['flash'] = "✅ Pre-Order disetujui.";
        header("Location: kelola_transaksi.php");
        exit;
    }

    // === ❌ TOLAK PRE-ORDER ===
    if ($aksi === 'tolak') {

        // === Update status ===
        $stmt = $koneksi->prepare("UPDATE pre_order SET status='ditolak' WHERE id=?");
        $stmt->bind_param("i", $preOrderId);
        $stmt->execute();
        $stmt->close();

        $stmt = $koneksi->prepare("UPDATE transaksi SET status='ditolak' WHERE pre_order_id=?");
        $stmt->bind_param("i", $preOrderId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash'] = "❌ Pre-Order ditolak (stok tidak diubah karena belum dikurangi).";
        header("Location: kelola_transaksi.php");
        exit;
    }
}

// === 🚫 Fallback: Tidak dikenali

else {
    $_SESSION['flash'] = "Jenis data tidak dikenali.";
    header("Location: kelola_transaksi.php");
    exit;
}
?>
