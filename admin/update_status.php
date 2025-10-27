<?php
session_start();
include "../koneksi.php";

// === Cek login admin ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}
//  Ambil dan simpan ===
$transaksi_id = intval($_POST['transaksi_id'] ?? 0);
$status       = strtolower(trim($_POST['status'] ?? ''));

// === Validasi ===
if ($transaksi_id <= 0 || $status === '') {
    $_SESSION['flash'] = "Data tidak valid.";
    header("Location: kelola_transaksi.php");
    exit;
}

// === Ambil transaksi ===
$stmt = $koneksi->prepare("SELECT * FROM transaksi WHERE id = ?");
$stmt->bind_param("i", $transaksi_id);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trx) {
    $_SESSION['flash'] = "Transaksi tidak ditemukan.";
    header("Location: kelola_transaksi.php");
    exit;
}

// === ✅ SETUJUI TRANSAKSI ===

if ($status === 'disetujui') {
    // Hanya mengubah status
    $stmt = $koneksi->prepare(" UPDATE transaksi SET status = 'disetujui' WHERE id = ? ");
    $stmt->bind_param("i", $transaksi_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash'] = "✅ Transaksi #$transaksi_id telah disetujui.";
    header("Location: kelola_transaksi.php");
    exit;
}

// === ❌ TOLAK / BATALKAN TRANSAKSI(kembalikan stok & set status 'ditolak')

if ($status === 'batal') {
    // === Mulai transaksi DB agar konsisten ===
    $koneksi->begin_transaction();

    try {
        // === Ambil detail barang yang mau dikembalikan stoknya ===
        $stmt = $koneksi->prepare(" SELECT produk_id, qty, ukuran FROM detail_transaksi WHERE transaksi_id = ? FOR UPDATE ");
        $stmt->bind_param("i", $transaksi_id);
        $stmt->execute();
        $produkList = $stmt->get_result();
        $stmt->close();

        // === Kumpulkan produk_id yang tersentuh agar sinkron stok utamanya nanti ===
        $produkIds = [];

        while ($p = $produkList->fetch_assoc()) {
            // === Kembalikan stok berdasarkan ukuran === 
            $stmt2 = $koneksi->prepare(" UPDATE produk_ukuran pu JOIN ukuran u ON pu.ukuran_id = u.id SET pu.stok = pu.stok + ? WHERE pu.produk_id = ? AND u.size = ? ");
            $stmt2->bind_param("iis", $p['qty'], $p['produk_id'], $p['ukuran']);
            $stmt2->execute();
            $stmt2->close();

            // === Simpan id produk untuk disinkron stok-totalnya ===
            $produkIds[] = (int)$p['produk_id'];
        }

        // === Sinkronkan stok utama HANYA untuk produk yang berubah ===
        if (!empty($produkIds)) {
            $produkIds = array_unique($produkIds);
            // === Buat placeholder (?, ?, ...) sesuai jumlah id ===
            $placeholders = implode(',', array_fill(0, count($produkIds), '?'));
            $types = str_repeat('i', count($produkIds));

            // === Query update per id (menggunakan IN) ===
            $sqlSync = " UPDATE produk p SET p.stok = ( SELECT COALESCE(SUM(pu.stok), 0) FROM produk_ukuran pu WHERE pu.produk_id = p.id ) WHERE p.id IN ($placeholders) ";
            $stmtSync = $koneksi->prepare($sqlSync);
            $stmtSync->bind_param($types, ...$produkIds);
            $stmtSync->execute();
            $stmtSync->close();
        }

        // === Update status transaksi -> ditolak (bukti_transfer & waktu_upload tidak diubah) ===
        $stmt = $koneksi->prepare(" UPDATE transaksi SET status = 'ditolak' WHERE id = ? ");
        $stmt->bind_param("i", $transaksi_id);
        $stmt->execute();
        $stmt->close();

        // === Commit semua perubahan ===
        $koneksi->commit();

        // === Flash message ===
        $_SESSION['flash'] = "❌ Transaksi #$transaksi_id ditolak dan stok dikembalikan.";
        header("Location: kelola_transaksi.php");
        exit;
    } catch (Throwable $e) {
        // === Kalau ada error, rollback agar data tidak setengah jadi ===
        $koneksi->rollback();

        $_SESSION['flash'] = "❌ Transaksi #$transaksi_id ditolak dan stok dikembalikan.";
        header("Location: kelola_transaksi.php");
        exit;
    }
}

// === Status tidak dikenali ===
$_SESSION['flash'] = "Status tidak dikenali.";
header("Location: kelola_transaksi.php");
exit;
