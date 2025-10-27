<?php
session_start();
include "../koneksi.php";

// Cek login
if (!isset($_SESSION['user'])) {
  $_SESSION['flash'] = "Silakan login terlebih dahulu untuk melakukan pre-order.";
  header("Location: login_user.php");
  exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Ambil asal halaman & kategori
$from = $_POST['from'] ?? $_GET['from'] ?? '';
$kategori = $_POST['kategori'] ?? $_GET['kategori'] ?? '';
$asal = $_POST['home'] ?? $_GET['home'] ?? '';
// var_dump($asal);
// die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['produk_id'])) {
  $_SESSION['flash'] = "Data produk tidak valid.";
  header("Location: ../homepage.php");
  exit;
}

$produk_id = intval($_POST['produk_id']);
$qty = isset($_POST['qty']) ? max(1, intval($_POST['qty'])) : 1;

// Ambil data produk
$stmt = $koneksi->prepare("SELECT p.id, p.nama, p.harga, p.gambar, k.nama AS kategori 
                           FROM produk p 
                           JOIN kategori k ON p.kategori_id = k.id 
                           WHERE p.id = ?");
$stmt->bind_param("i", $produk_id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk) {
  $_SESSION['flash'] = "Produk tidak ditemukan.";
  header("Location: ../homepage.php");
  exit;
}

$total = $produk['harga'] * $qty;

// Ambil ukuran produk
$stmt = $koneksi->prepare("SELECT u.size 
                           FROM produk_ukuran pu 
                           JOIN ukuran u ON pu.ukuran_id = u.id 
                           WHERE pu.produk_id = ?");
$stmt->bind_param("i", $produk_id);
$stmt->execute();
$resultUkuran = $stmt->get_result();
$stmt->close();

// Ambil data user
$stmt = $koneksi->prepare("SELECT nama, email, alamat, nomor_telepon FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil metode pembayaran
$metodeResult = $koneksi->query("SELECT id, nama_metode FROM metode_pembayaran ORDER BY id ASC");
$metodePembayaran = [];
while ($row = $metodeResult->fetch_assoc()) {
  $metodePembayaran[] = $row;
}

// Jika form dikirim
if (isset($_POST['konfirmasi'])) {
  $nama = trim($_POST['nama']);
  $email = trim($_POST['email']);
  $alamat = trim($_POST['alamat']);
  $no_hp = trim($_POST['no_hp']);
  $ukuran = trim($_POST['ukuran'] ?? '');
  $metode_pembayaran_id = intval($_POST['metode_pembayaran_id'] ?? 0);

  if ($nama === "" || $email === "" || $alamat === "" || $no_hp === "" || $ukuran === "" || $metode_pembayaran_id === 0) {
    $_SESSION['flash'] = "Harap lengkapi semua data, termasuk ukuran dan metode pembayaran!";
    header("Location: pre_order_form.php");
    exit;
  }

  // Update data user
  $stmt = $koneksi->prepare("UPDATE users SET nama=?, email=?, alamat=?, nomor_telepon=? WHERE id=?");
  $stmt->bind_param("ssssi", $nama, $email, $alamat, $no_hp, $user_id);
  $stmt->execute();
  $stmt->close();

  // 1️⃣ Simpan pre_order
  $stmt = $koneksi->prepare("INSERT INTO pre_order (user_id, produk_id, qty, total, ukuran, status, tanggal)
                           VALUES (?, ?, ?, ?, ?, 'Menunggu Persetujuan Admin', NOW())");
  $stmt->bind_param("iiids", $user_id, $produk_id, $qty, $total, $ukuran);
  $stmt->execute();
  $preorder_id = $stmt->insert_id;
  $stmt->close();

  // 2️⃣ Buat transaksi otomatis
// 2️⃣ Buat transaksi otomatis
// 2️⃣ Buat transaksi otomatis
if ($metode_pembayaran_id == 1) { // 1 = COD
  $status_awal = 'pending'; // biar tampil normal di riwayat & kelola_transaksi
} else {
  $status_awal = 'pending'; // biar sama-sama nunggu admin
}

$jenis_pesanan = 'pre order';


$stmt = $koneksi->prepare("INSERT INTO transaksi 
  (user_id, metode_pembayaran_id, nama, alamat, no_hp, email, total, status, tanggal, pre_order_id, jenis_pesanan)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
$stmt->bind_param("iissssssis", $user_id, $metode_pembayaran_id, $nama, $alamat, $no_hp, $email, $total, $status_awal, $preorder_id, $jenis_pesanan);
$stmt->execute();
$transaksi_id = $stmt->insert_id;
$stmt->close();


  // 3️⃣ Simpan detail produk ke detail_transaksi
  $subtotal = $produk['harga'] * $qty;

  $stmt = $koneksi->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, harga, subtotal, ukuran)
                              VALUES (?, ?, ?, ?, ?, ?)
");
  $stmt->bind_param("iiidds", $transaksi_id, $produk_id, $qty, $produk['harga'], $subtotal, $ukuran);
  $stmt->execute();
  $stmt->close();


  // 4️⃣ Update stok (stok dikurangi setelah pembayaran disetujui oleh admin, jadi tidak dikurangi di sini)

  // 5️⃣ Redirect ke upload bukti
  // 5️⃣ Redirect berdasarkan metode pembayaran
  if (strtolower($metode_pembayaran_id) == 1) { // 1 = COD (pastikan ID COD kamu memang 1)
    $_SESSION['flash'] = "✅ Pre-order COD berhasil dibuat! Tunggu konfirmasi dari admin.";
    header("Location: ../user/riwayat.php?from=preorder&kategori=" . urlencode(strtolower($produk['kategori'])));
    exit;
  } else {
    $_SESSION['flash'] = "📤 Silakan upload bukti pembayaran untuk menyelesaikan pre-order kamu.";
    header("Location: upload_bukti.php?id=$transaksi_id&from=preorder&kategori=" . urlencode(strtolower($produk['kategori'])) . "&home=" . urlencode($asal));
    exit;
  }


  exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Pre-Order - BRIMOB SPORT</title>
  <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen bg-gray-100 text-gray-800 font-sans flex flex-col items-center">

  <?php if (isset($_SESSION['flash'])): ?>
    <div id="flash" class="fixed top-5 left-1/2 -translate-x-1/2 bg-emerald-600 text-white px-6 py-3 rounded-lg shadow-md z-50 font-medium">
      <?= htmlspecialchars($_SESSION['flash']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <header class="text-center py-5 bg-white w-full border-b border-gray-200 shadow-sm">
    <h1 class="text-4xl font-bold text-gray-800 mb-1">Form Pre-Order</h1>
    <p class="text-gray-500 text-sm">Pastikan data sudah benar sebelum kirim pre-order</p>
  </header>

  <main class="flex flex-col items-center mt-5 mb-5 w-full px-4">
    <form method="post"
      class="w-full max-w-lg bg-white border border-gray-200 rounded-2xl shadow-[0_4px_12px_rgba(0,0,0,0.07)] hover:shadow-[0_6px_16px_rgba(0,0,0,0.1)] transition-all duration-300 p-8 space-y-5">

      <div class="flex gap-4 mb-6 border-b pb-4">
        <img src="../img/<?= htmlspecialchars($produk['gambar']) ?>" alt="<?= htmlspecialchars($produk['nama']) ?>"
          class="w-24 h-24 object-contain rounded-md border border-gray-200">
        <div>
          <p class="font-medium text-gray-800 text-base"><?= htmlspecialchars($produk['nama']) ?></p>
          <p class="text-sm text-gray-600">Qty: <?= $qty ?></p>
          <p class="font-semibold text-emerald-600 mt-1 text-base">Rp<?= number_format($total, 0, ',', '.') ?></p>
        </div>
      </div>

      <div class="space-y-4">
        <label class="block">
          <span class="font-semibold text-gray-700 mb-1 block">Nama Lengkap</span>
          <input type="text" name="nama" value="<?= htmlspecialchars($userData['nama'] ?? '') ?>"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
        </label>

        <label class="block">
          <span class="font-semibold text-gray-700 mb-1 block">Email</span>
          <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
        </label>

        <label class="block">
          <span class="font-semibold text-gray-700 mb-1 block">Alamat Lengkap</span>
          <textarea name="alamat" rows="3"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none" required><?= htmlspecialchars($userData['alamat'] ?? '') ?></textarea>
        </label>

        <label class="block">
          <span class="font-semibold text-gray-700 mb-1 block">Nomor HP</span>
          <input type="text" name="no_hp" value="<?= htmlspecialchars($userData['nomor_telepon'] ?? '') ?>"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
        </label>

        <!-- Pilih Ukuran -->
        <div>
          <span class="font-semibold text-gray-700 mb-2 block">Pilih Ukuran</span>
          <div class="flex flex-wrap gap-3">
            <?php while ($row = $resultUkuran->fetch_assoc()): ?>
              <label class="flex items-center space-x-2">
                <input type="radio" name="ukuran"
                  value="<?= htmlspecialchars($row['size']) ?>" required
                  class="w-5 h-5 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                <span class="text-sm"><?= htmlspecialchars($row['size']) ?></span>
              </label>
            <?php endwhile; ?>
          </div>
        </div>

        <!-- Metode Pembayaran -->
        <label class="block">
          <span class="font-semibold text-gray-700 mb-1 block">Metode Pembayaran</span>
          <select name="metode_pembayaran_id"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white" required>
            <option value="">-- Pilih Metode Pembayaran --</option>
            <?php foreach ($metodePembayaran as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_metode']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <div class="mt-6 flex gap-3">
        <button type="submit" name="konfirmasi"
          class="w-1/2 px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition shadow-md text-sm font-medium cursor-pointer">
          Kirim Pre-Order
        </button>
        <a href="javascript:history.back()"
          class="w-1/2 flex items-center justify-center px-5 py-3 bg-gray-200 text-gray-800 rounded-lg text-sm font-medium hover:bg-gray-300 transition shadow-md">
          ← Kembali
        </a>
      </div>

      <input type="hidden" name="produk_id" value="<?= $produk_id ?>">
      <input type="hidden" name="qty" value="<?= $qty ?>">
      <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
      <input type="hidden" name="home" value="<?= htmlspecialchars($asal) ?>">
      <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
      <input type="hidden" name="konfirmasi" value="1">
    </form>
  </main>

  <script>
    setTimeout(() => {
      const flash = document.getElementById("flash");
      if (flash) {
        flash.style.opacity = "0";
        flash.style.transition = "opacity 0.6s";
        setTimeout(() => flash.remove(), 600);
      }
    }, 3000);
  </script>
</body>

</html>