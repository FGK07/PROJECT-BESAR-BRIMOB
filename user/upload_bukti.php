<?php
session_start();
include "../koneksi.php";

// Pastikan user sudah login
if (!isset($_SESSION['user'])) {
  header("Location: ../auth/login_user.php");
  exit;
}

// Ambil parameter dari GET/POST
$kategori = $_GET['kategori'] ?? $_POST['kategori'] ?? '';
$from     = $_GET['from'] ?? $_POST['from'] ?? '';
$asal     = $_GET['home'] ?? $_POST['home'] ?? '';
$id       = intval($_GET['id'] ?? 0);

if ($id <= 0) die("Transaksi tidak valid.");

// Ambil data transaksi + metode pembayaran
$stmt = $koneksi->prepare("
  SELECT t.id, t.total, t.metode_pembayaran_id, m.nama_metode, t.bukti_transfer 
  FROM transaksi t 
  LEFT JOIN metode_pembayaran m ON t.metode_pembayaran_id = m.id 
  WHERE t.id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trx) die("Transaksi tidak ditemukan.");

// Tentukan nomor rekening / e-wallet
$rekening = "";
$atas_nama = "BRIMOB SPORT Official";
if ($trx['nama_metode'] === 'BRI') {
  $rekening = "7303-0102-7722-537";
} elseif ($trx['nama_metode'] === 'DANA') {
  $rekening = "089513309321";
}

// =========================================================
// 🟩 PROSES UPLOAD BUKTI PEMBAYARAN
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_FILES['bukti']['name'])) {

    $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
    $newName = "bukti_" . $id . "_" . time() . "." . $ext;
    $path = "../uploads/bukti/" . $newName;

    if (!is_dir("../uploads/bukti")) mkdir("../uploads/bukti", 0777, true);

    // 🔒 Validasi ekstensi
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowed_ext)) {
      $_SESSION['flash'] = "⚠️ Hanya file JPG, PNG, atau PDF yang diperbolehkan.";
      header("Location: upload_bukti.php?id=$id&from=$from&kategori=" . urlencode($kategori));
      exit;
    }

    // 🔒 Validasi ukuran file (maks 10MB)
    if ($_FILES['bukti']['size'] > 10 * 1024 * 1024) {
      $_SESSION['flash'] = "⚠️ Ukuran file maksimal 10MB.";
      header("Location: upload_bukti.php?id=$id&from=$from&kategori=" . urlencode($kategori));
      exit;
    }

    // ✅ Upload file ke server
    if (move_uploaded_file($_FILES['bukti']['tmp_name'], $path)) {
      // ✅ Update database
      $query = "
        UPDATE transaksi 
        SET bukti_transfer = ?, waktu_upload = NOW(), status = 'pending'
        WHERE id = ?
      ";
      $stmt = $koneksi->prepare($query);
      $stmt->bind_param("si", $newName, $id);
      $stmt->execute();
      $stmt->close();

      // ✅ Flash message dan redirect
      $_SESSION['flash'] = "✅ Bukti pembayaran berhasil diunggah dan Pesanan berhasil dibuat. Menunggu konfirmasi admin.";
      header("Location: ../user/riwayat.php");
      exit;
    } else {
      $_SESSION['flash'] = "⚠️ Gagal upload file, coba lagi.";
    }
  } else {
    $_SESSION['flash'] = "⚠️ Pilih file terlebih dahulu.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Upload Bukti Pembayaran</title>
  <link rel="stylesheet" href="../src/output.css">
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center text-gray-800">

  <div class="bg-white p-8 rounded-2xl shadow-xl w-[420px] border border-gray-200">
    <h1 class="text-2xl font-bold text-center mb-4">Upload Bukti Pembayaran</h1>
    <p class="text-center text-gray-600 mb-3">
      Total Pembayaran: <br>
      <span class="text-emerald-600 font-semibold text-lg">
        Rp<?= number_format($trx['total'], 0, ',', '.') ?>
      </span>
    </p>

    <!-- ✅ Info Rekening -->
    <?php if ($rekening): ?>
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-5 text-sm">
        <p class="font-medium text-gray-700 mb-1">Transfer ke rekening:</p>
        <p class="text-gray-800">
          <span class="font-semibold"><?= htmlspecialchars($trx['nama_metode']) ?>:</span>
          <?= htmlspecialchars($rekening) ?>
        </p>
        <p class="text-gray-800">
          <span class="font-semibold">a.n.</span> <?= htmlspecialchars($atas_nama) ?>
        </p>
        <p class="text-xs text-gray-500 mt-1 italic">
          Setelah transfer, upload bukti pembayaran (foto/screenshot) di bawah ini.
        </p>
      </div>
    <?php endif; ?>

    <!-- ✅ Form Upload -->
    <form method="post" enctype="multipart/form-data" class="space-y-4">
      <input type="file" name="bukti" accept="image/*,.pdf" required
        class="block w-full border border-gray-300 rounded-md p-2 text-sm bg-white focus:ring-2 focus:ring-emerald-500">

      <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
      <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">

      <button type="submit"
        class="w-full py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
        Upload Sekarang
      </button>
    </form>

    <!-- ✅ Tombol kembali -->
    <div class="mt-3">
      <?php if ($asal === 'homepage'): ?>
        <button type="button"
          onclick="window.location.href='../homepage.php'"
          class="w-full py-2 text-black border border-gray-300 rounded-lg hover:bg-gray-200 transition">
          ← Kembali ke Homepage
        </button>
      <?php elseif (isset($kategori)): ?>
        <button type="button"
          onclick="window.location.href='../produk/kategori.php?nama=<?= urlencode($kategori) ?>'"
          class="w-full py-2 text-black border border-gray-300 rounded-lg hover:bg-gray-200 transition">
          ← Kembali ke Kategori
        </button>
      <?php else: ?>
        <button type="button"
          onclick="window.location.href='../user/riwayat.php'"
          class="w-full py-2 text-black border border-gray-300 rounded-lg hover:bg-gray-200 transition">
          ← Kembali ke Riwayat
        </button>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
