<?php
session_start();
include "../koneksi.php";

// Ambil asal halaman
$from = $_POST['from'] ?? $_GET['from'] ?? '';
$kategori = $_POST['kategori'] ?? $_GET['kategori'] ?? '';

// Cek login
if (!isset($_SESSION['user'])) {
  $_SESSION['flash'] = "Silakan login terlebih dahulu untuk melakukan pembelian.";
  header("Location: login_user.php");
  exit;
}

// Ambil user dan id
$user = $_SESSION['user'];
$user_id = $user['id'];

// Validasi input produk
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['produk_id'])) {
  $_SESSION['flash'] = "Data produk tidak valid.";
  header("Location: ../homepage.php");
  exit;
}

$produk_id = intval($_POST['produk_id']);
$qty       = isset($_POST['qty']) ? max(1, intval($_POST['qty'])) : 1;
$ukuran    = trim($_POST['ukuran'] ?? "");

// Ambil data produk
$stmt = $koneksi->prepare("SELECT id, nama, harga, gambar FROM produk WHERE id = ?");
$stmt->bind_param("i", $produk_id);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();

if ($ukuran === "") {
  $_SESSION['flash'] = "Silakan pilih ukuran terlebih dahulu.";
  header("Location: ../produk/detail_produk.php?id=$produk_id");
  exit;
}

// Cek stok berdasarkan ukuran
$stmtUkuran = $koneksi->prepare("SELECT stok FROM produk_ukuran pu 
    JOIN ukuran u ON u.id = pu.ukuran_id 
    WHERE pu.produk_id = ? AND u.size = ?");
$stmtUkuran->bind_param("is", $produk_id, $ukuran);
$stmtUkuran->execute();
$resUkuran = $stmtUkuran->get_result();
$stokUkuran = $resUkuran->fetch_assoc();
$stokTersedia = $stokUkuran['stok'] ?? 0;
$stmtUkuran->close();

if (!$produk) {
  $_SESSION['flash'] = "Produk tidak ditemukan.";
  header("Location: ../homepage.php");
  exit;
}

if ($stokTersedia < $qty) {
  $_SESSION['flash'] = "Stok ukuran tersebut tidak mencukupi.";
  header("Location: ../produk/detail_produk.php?id=" . urlencode($produk_id));
  exit;
}

$total = $produk['harga'] * $qty;

// Ambil data user
$stmt = $koneksi->prepare("SELECT nama, email, alamat, nomor_telepon FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// Ambil daftar metode pembayaran
$metodeResult = $koneksi->query("SELECT id, nama_metode FROM metode_pembayaran ORDER BY id ASC");
$metodePembayaran = [];
while ($row = $metodeResult->fetch_assoc()) {
  $metodePembayaran[] = $row;
}

// Jika user menekan tombol konfirmasi
if (isset($_POST['konfirmasi'])) {
  $nama   = trim($_POST['nama']);
  $email  = trim($_POST['email']);
  $alamat = trim($_POST['alamat']);
  $no_hp  = trim($_POST['no_hp']);
  $metode_pembayaran_id = intval($_POST['metode_pembayaran_id'] ?? 0);

  if ($nama === "" || $email === "" || $alamat === "" || $no_hp === "" || $metode_pembayaran_id === 0) {
    $_SESSION['flash'] = "Harap lengkapi semua data dan pilih metode pembayaran!";
    header("Location: beli_sekarang.php");
    exit;
  }

  // Sinkronisasi data user
  $stmt = $koneksi->prepare("UPDATE users SET nama=?, email=?, alamat=?, nomor_telepon=? WHERE id=?");
  $stmt->bind_param("ssssi", $nama, $email, $alamat, $no_hp, $user_id);
  $stmt->execute();

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

  // Simpan detail transaksi
  $subtotal = $produk['harga'] * $qty;
  $stmtItem = $koneksi->prepare("INSERT INTO detail_transaksi (transaksi_id, produk_id, qty, harga, subtotal, ukuran)
        VALUES (?, ?, ?, ?, ?, ?)");
  $stmtItem->bind_param("iiiiss", $transaksi_id, $produk_id, $qty, $produk['harga'], $subtotal, $ukuran);
  $stmtItem->execute();

  // Kurangi stok produk
  $stmt = $koneksi->prepare("UPDATE produk_ukuran pu
      JOIN ukuran u ON pu.ukuran_id = u.id
      SET pu.stok = pu.stok - ?
      WHERE pu.produk_id = ? AND u.size = ?");
  $stmt->bind_param("iis", $qty, $produk_id, $ukuran);
  $stmt->execute();

  // Update stok total produk agar sinkron
  $koneksi->query("UPDATE produk p 
      SET p.stok = (
          SELECT COALESCE(SUM(stok),0) FROM produk_ukuran WHERE produk_id = p.id
      )
      WHERE p.id = $produk_id
  ");

  // ✅ Tambahan kecil untuk kirim kategori dan asal halaman
  $redirectParams = "id=$transaksi_id";
  if (!empty($kategori)) {
    $redirectParams .= "&kategori=" . rawurlencode($kategori);
  }
  if (!empty($from)) {
    $redirectParams .= "&from=" . rawurlencode($from);
  }

  // 🔥 Logika upload bukti pembayaran (BRI/DANA)
  if (in_array($metode_pembayaran_id, [2, 3])) { // 2=BRI, 3=DANA
    $_SESSION['flash'] = "📤 Silakan upload bukti transfer untuk menyelesaikan pembayaran.";
    header("Location: upload_bukti.php?$redirectParams");
    exit;
  }

  // Default redirect ke riwayat (COD / lainnya)
  $_SESSION['flash'] = "✅ Pesanan berhasil dibuat! Nomor transaksi: #$transaksi_id";

  $redirectFrom = "from=" . rawurlencode($from);
  if (!empty($kategori)) {
    $redirectFrom .= "&kategori=" . rawurlencode($kategori);
  }

  header("Location: ../user/riwayat.php?$redirectFrom");
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

  <!-- ✅ Flash Message -->
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

  <!-- Form Container -->
  <main class="flex flex-col items-center mt-5 mb-5 w-full px-4">
    <form method="post"
      class="w-full max-w-lg bg-white border border-gray-200 rounded-2xl shadow-[0_4px_12px_rgba(0,0,0,0.07)] hover:shadow-[0_6px_16px_rgba(0,0,0,0.1)] transition-all duration-300 p-8 space-y-5">

      <!-- Produk -->
      <div class="flex gap-4 mb-6 border-b pb-4">
        <img src="../img/<?= htmlspecialchars($produk['gambar']) ?>"
          alt="<?= htmlspecialchars($produk['nama']) ?>"
          class="w-24 h-24 object-contain rounded-md border border-gray-200">
        <div>
          <p class="font-medium text-gray-800 text-base"><?= htmlspecialchars($produk['nama']) ?></p>
          <p class="text-sm text-gray-600">Ukuran: <?= htmlspecialchars($ukuran ?: '-') ?></p>
          <p class="text-sm text-gray-600">Qty: <?= $qty ?></p>
          <p class="font-semibold text-emerald-600 mt-1 text-base">Rp<?= number_format($total, 0, ',', '.') ?></p>
        </div>
      </div>

      <!-- Input Data -->
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

        <textarea name="alamat" rows="3"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none text-left" required><?= htmlspecialchars(trim($userData['alamat'] ?? '')) ?></textarea>


        <label class="block">
          <span class="font-semibold text-gray-700 mb-1 block">Nomor HP</span>
          <input type="text" name="no_hp" value="<?= htmlspecialchars($userData['nomor_telepon'] ?? '') ?>"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
        </label>

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

      <!-- Tombol Aksi -->
      <div class="mt-6 flex gap-3">
        <button type="submit" name="konfirmasi"
          class="w-1/2 px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition shadow-md text-sm font-medium cursor-pointer">
          Konfirmasi Pesanan
        </button>

        <a href="javascript:history.back()"
          class="w-1/2 flex items-center justify-center px-5 py-3 bg-gray-200 text-gray-800 rounded-lg text-sm font-medium hover:bg-gray-300 transition shadow-md">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          <span>Kembali</span>
        </a>
      </div>

      <!-- Hidden Inputs -->
      <input type="hidden" name="produk_id" value="<?= $produk_id ?>">
      <input type="hidden" name="qty" value="<?= $qty ?>">
      <input type="hidden" name="ukuran" value="<?= htmlspecialchars($ukuran) ?>">
      <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
      <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
      <input type="hidden" name="konfirmasi" value="1">
    </form>
  </main>

  <!-- Flash fade -->
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