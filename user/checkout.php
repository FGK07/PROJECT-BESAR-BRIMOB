<?php
session_start();
include "../koneksi.php";

$user = $_SESSION['user'] ?? null;
if (!$user) {
  $_SESSION['flash'] = "Silakan login terlebih dahulu untuk checkout.";
  header("Location: ../user/login_user.php");
  exit;
}

$user_id = $user['id'];

// Ambil asal halaman dan kategori
$from = $_POST['from'] ?? $_GET['from'] ?? '';
$kategori = $_POST['kategori'] ?? $_GET['kategori'] ?? '';

// Ambil data user
$stmt = $koneksi->prepare("SELECT nama, email, alamat, nomor_telepon FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil daftar metode pembayaran
$metodeResult = $koneksi->query("SELECT id, nama_metode FROM metode_pembayaran ORDER BY id ASC");
$metodePembayaran = [];
while ($row = $metodeResult->fetch_assoc()) {
  $metodePembayaran[] = $row;
}

// Ambil data dari keranjang
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
  $_SESSION['flash'] = "Keranjang kamu masih kosong.";
  header("Location: ../user/keranjang.php");
  exit;
}

$produkList = [];
$total = 0;

// Loop semua item di cart
foreach ($cart as $key => $qty) {
  list($id, $ukuran) = explode("-", $key);

  // Ambil data produk
  $stmt = $koneksi->prepare("SELECT id, nama, harga, gambar FROM produk WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $produk = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$produk) continue;

  // Cek stok berdasarkan ukuran
  $stmtUkuran = $koneksi->prepare("SELECT pu.stok 
      FROM produk_ukuran pu
      JOIN ukuran u ON u.id = pu.ukuran_id
      WHERE pu.produk_id = ? AND u.size = ?");
  $stmtUkuran->bind_param("is", $id, $ukuran);
  $stmtUkuran->execute();
  $resUkuran = $stmtUkuran->get_result();
  $stokData = $resUkuran->fetch_assoc();
  $stokTersedia = $stokData['stok'] ?? 0;
  $stmtUkuran->close();

  if ($stokTersedia < $qty) {
    $_SESSION['flash'] = "Stok ukuran <b>$ukuran</b> untuk produk <b>{$produk['nama']}</b> tidak mencukupi.";
    header("Location: ../user/keranjang.php");
    exit;
  }

  $produk['qty'] = $qty;
  $produk['ukuran'] = $ukuran;
  $produk['subtotal'] = $produk['harga'] * $qty;
  $produkList[] = $produk;
  $total += $produk['subtotal'];
}

// Jika user menekan konfirmasi checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama   = trim($_POST['nama']);
  $email  = trim($_POST['email']);
  $alamat = trim($_POST['alamat']);
  $no_hp  = trim($_POST['no_hp']);
  $metode_pembayaran_id = intval($_POST['metode_pembayaran_id']);

  if ($nama === "" || $email === "" || $alamat === "" || $no_hp === "" || $metode_pembayaran_id === 0) {
    $_SESSION['flash'] = "Harap lengkapi semua data dan pilih metode pembayaran!";
    header("Location: checkout.php");
    exit;
  }

  // Tentukan status awal berdasarkan metode pembayaran
  $status_awal = in_array($metode_pembayaran_id, [2, 3]) ? 'menunggu pembayaran' : 'pending';
  $jenis_pesanan = 'ready stock'; // ✅ Tambahkan ini

  $stmt = $koneksi->prepare("INSERT INTO transaksi 
    (user_id, metode_pembayaran_id, nama, alamat, no_hp, email, total, status, tanggal, jenis_pesanan)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
  $stmt->bind_param("iissssiss", $user_id, $metode_pembayaran_id, $nama, $alamat, $no_hp, $email, $total, $status_awal, $jenis_pesanan);
  $stmt->execute();
  $transaksi_id = $stmt->insert_id;
  $stmt->close();

  // Simpan detail transaksi & kurangi stok
  foreach ($produkList as $p) {
    $subtotal = $p['harga'] * $p['qty'];

    // Simpan detail
    $stmtItem = $koneksi->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, harga, subtotal, ukuran)
      VALUES (?, ?, ?, ?, ?, ?)");
    $stmtItem->bind_param("iiiiss", $transaksi_id, $p['id'], $p['qty'], $p['harga'], $subtotal, $p['ukuran']);
    $stmtItem->execute();
    $stmtItem->close();

    // Kurangi stok di produk_ukuran
    $stmtUpdate = $koneksi->prepare("UPDATE produk_ukuran pu
      JOIN ukuran u ON pu.ukuran_id = u.id
      SET pu.stok = pu.stok - ?
      WHERE pu.produk_id = ? AND u.size = ?");
    $stmtUpdate->bind_param("iis", $p['qty'], $p['id'], $p['ukuran']);
    $stmtUpdate->execute();
    $stmtUpdate->close();
  }

  // Sinkronkan stok total
  $koneksi->query("UPDATE produk p SET p.stok = (SELECT COALESCE(SUM(stok),0) FROM produk_ukuran WHERE produk_id = p.id)");

  // Kosongkan keranjang
  unset($_SESSION['cart']);

  // ✅ Buat parameter redirect dinamis
  $redirectParams = "id=$transaksi_id";
  if (!empty($kategori)) $redirectParams .= "&kategori=" . rawurlencode($kategori);
  if (!empty($from)) $redirectParams .= "&from=" . rawurlencode($from);

  // 🔥 Redirect ke upload bukti (BRI/DANA)
  if (in_array($metode_pembayaran_id, [2, 3])) {
    $_SESSION['flash'] = "📤 Silakan upload bukti transfer untuk menyelesaikan pembayaran.";
    header("Location: upload_bukti.php?$redirectParams");
    exit;
  }

  // Redirect default (COD / lainnya)
  $_SESSION['flash'] = "✅ Pesanan berhasil dibuat! Nomor transaksi: #$transaksi_id";
  header("Location: ../user/riwayat.php?from=checkout");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Checkout - BRIMOB SPORT</title>
  <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen bg-gray-100 text-gray-800 font-sans flex flex-col items-center">

  <!-- Flash Message -->
  <?php if (isset($_SESSION['flash'])): ?>
    <div id="flash"
      class="fixed top-5 left-1/2 -translate-x-1/2 bg-emerald-600 text-white px-6 py-3 rounded-lg shadow-md z-50 font-medium">
      <?= htmlspecialchars($_SESSION['flash']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <!-- Header -->
  <header class="text-center py-5 bg-white w-full border-b border-gray-200 shadow-sm">
    <h1 class="text-4xl font-bold text-gray-800 mb-1">Checkout</h1>
    <p class="text-gray-500 text-sm">Pastikan data dan pesananmu sudah benar sebelum konfirmasi</p>
  </header>

  <main class="flex flex-col items-center mt-5 mb-5 w-full px-4">
    <form method="post" class="w-full max-w-lg bg-white border border-gray-200 rounded-2xl shadow p-8">

      <!-- Produk -->
      <div class="mb-6 border-b pb-4">
        <?php foreach ($produkList as $p): ?>
          <div class="flex gap-4 mb-4">
            <img src="../img/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>"
              class="w-24 h-24 object-contain rounded-md border border-gray-200">
            <div>
              <p class="font-medium text-gray-800 text-base"><?= htmlspecialchars($p['nama']) ?></p>
              <p class="text-sm text-gray-600">Ukuran: <?= htmlspecialchars($p['ukuran']) ?></p>
              <p class="text-sm text-gray-600">Qty: <?= $p['qty'] ?></p>
              <p class="font-semibold text-emerald-600 mt-1 text-base">
                Rp<?= number_format($p['subtotal'], 0, ',', '.') ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="text-right mt-2 font-bold text-lg text-emerald-700">
          Total: Rp<?= number_format($total, 0, ',', '.') ?>
        </div>
      </div>

      <!-- Form Input -->
      <label class="block mb-3">
        <span class="font-semibold text-gray-700 mb-1 block">Nama Lengkap</span>
        <input type="text" name="nama" value="<?= htmlspecialchars($userData['nama'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
      </label>

      <label class="block mb-3">
        <span class="font-semibold text-gray-700 mb-1 block">Email</span>
        <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
      </label>

      <label class="block mb-3">
        <span class="font-semibold text-gray-700 mb-1 block">Alamat Lengkap</span>
        <textarea name="alamat" rows="3"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none" required><?= htmlspecialchars($userData['alamat'] ?? '') ?></textarea>
      </label>

      <label class="block mb-3">
        <span class="font-semibold text-gray-700 mb-1 block">Nomor HP</span>
        <input type="text" name="no_hp" value="<?= htmlspecialchars($userData['nomor_telepon'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
      </label>

      <label class="block mb-3">
        <span class="font-semibold text-gray-700 mb-1 block">Metode Pembayaran</span>
        <select name="metode_pembayaran_id"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white" required>
          <option value="">-- Pilih Metode Pembayaran --</option>
          <?php foreach ($metodePembayaran as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_metode']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <!-- Tombol -->
      <div class="mt-6 flex gap-3">
        <button type="submit"
          class="w-1/2 px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition shadow-md text-sm font-medium cursor-pointer">
          Konfirmasi Pesanan
        </button>
        <a href="javascript:history.back()"
          class="w-1/2 flex items-center justify-center px-5 py-3 bg-gray-200 text-gray-800 rounded-lg text-sm font-medium hover:bg-gray-300 transition shadow-md">
          ← Kembali
        </a>
      </div>

      <!-- Hidden Inputs -->
      <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
      <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">

    </form>
  </main>

  <!-- Flash Fade -->
  <script>
    setTimeout(() => {
      const flash = document.getElementById("flash");
      if (flash) {
        flash.style.opacity = "0";
        flash.style.transition = "opacity 0.6s ease";
        setTimeout(() => flash.remove(), 600);
      }
    }, 3000);
  </script>
</body>

</html>