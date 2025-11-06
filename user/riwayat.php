<?php
session_start();
include "../koneksi.php";

// Pastikan user sudah login
if (!isset($_SESSION['user'])) {
  $_SESSION['flash'] = "Silakan login untuk melihat riwayat transaksi.";
  header("Location: ../user/login_user.php");
  exit;
}

$user_id = $_SESSION['user']['id'];

// Ambil semua transaksi user (termasuk hasil pre-order)
$stmt = $koneksi->prepare("SELECT t.id, t.nama, t.alamat, t.no_hp, t.total, t.tanggal, t.status, t.bukti_transfer, m.nama_metode AS metode_pembayaran, t.pre_order_id
  FROM transaksi t
  LEFT JOIN metode_pembayaran m ON t.metode_pembayaran_id = m.id
  WHERE t.user_id = ?
  ORDER BY t.tanggal DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$transaksiList = $stmt->get_result();

// Tentukan tombol kembali dinamis
$from = $_GET['from'] ?? '';
$kategori = $_GET['kategori'] ?? '';

switch ($from) {
  case "homepage":
    $backUrl = "../homepage.php";
    break;
  case "kategori":
    $backUrl = "../produk/kategori.php?nama=" . urlencode($kategori);
    break;
  case "keranjang":
    $backUrl = "../user/keranjang.php";
    break;
  case "checkout":
    $backUrl = !empty($kategori)
      ? "../produk/kategori.php?nama=" . urlencode($kategori)
      : "../homepage.php";
    break;
  default:
    $backUrl = "../homepage.php";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Transaksi - BRIMOB SPORT</title>
  <link rel="stylesheet" href="../src/output.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="min-h-screen bg-gray-100 text-gray-800 font-sans">

  <!-- Flash Message -->
  <?php if (isset($_SESSION['flash'])): ?>
    <div id="flash"
      class="fixed top-3 left-0 sm:left-1/2 sm:-translate-x-1/2 
          w-full sm:w-auto sm:max-w-md 
          bg-emerald-100 text-emerald-800 border border-emerald-300 
          rounded-md sm:rounded-lg px-4 sm:px-6 py-2 sm:py-3 
          text-center font-medium text-xs sm:text-sm 
          shadow-md sm:shadow-lg 
          z-[9999] animate-slide-down">
      <?= htmlspecialchars($_SESSION['flash']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <!-- Header -->
  <header class="text-center py-6 sm:py-10 bg-white border-b border-gray-200 shadow-sm">
    <h1 class="text-2xl sm:text-4xl font-bold text-gray-800 mb-1 tracking-wide">Riwayat Transaksi</h1>
    <p class="text-gray-500 text-xs sm:text-sm">Cek status dan detail pembelianmu di BRIMOB SPORT</p>
  </header>

  <main class="max-w-5xl mx-auto px-3 sm:px-5 pb-20 sm:pb-16 mt-6 sm:mt-10 space-y-4 sm:space-y-6">
    <?php if ($transaksiList->num_rows === 0): ?>
      <div class="bg-white border border-gray-200 rounded-xl p-6 sm:p-10 text-center shadow-sm">
        <p class="text-sm sm:text-lg text-gray-600">Belum ada transaksi tercatat.</p>
      </div>
    <?php else: ?>
      <?php while ($trx = $transaksiList->fetch_assoc()): ?>
        <div
          class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-md sm:shadow-[0_4px_10px_rgba(0,0,0,0.07)] hover:shadow-lg transition-all duration-300 overflow-hidden p-4 sm:p-6 text-sm sm:text-base">

          <!-- Header transaksi -->
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4">
            <div class="mb-2 sm:mb-0">
              <h2 class="text-base sm:text-lg font-semibold text-gray-900">
                Transaksi #<?= $trx['id'] ?>
                <?php if (!empty($trx['pre_order_id'])): ?>
                  <span class="ml-1 sm:ml-2 text-[10px] sm:text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 border border-purple-300 font-medium">
                    Pre-Order
                  </span>
                <?php endif; ?>
              </h2>
              <p class="text-[11px] sm:text-xs text-gray-500 mt-1">Tanggal: <?= $trx['tanggal'] ?></p>
            </div>
            <div>
              <?php
              $statusLower = strtolower($trx['status']);
              if ($statusLower === 'disetujui'): ?>
                <span class="px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-300">
                  Disetujui
                </span>
              <?php elseif ($statusLower === 'selesai'): ?>
                <span class="px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-green-100 text-green-700 border border-green-300">
                  Selesai
                </span>
              <?php elseif (in_array($statusLower, ['ditolak', 'batal', 'dibatalkan oleh user'])): ?>
                <span class="px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-red-100 text-red-700 border border-red-300">
                  Dibatalkan
                </span>
              <?php elseif (in_array($statusLower, ['menunggu konfirmasi admin', 'pending'])): ?>
                <span class="px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs font-semibold bg-yellow-100 text-yellow-700 border border-yellow-300">
                  Menunggu
                </span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Progress Bar -->
          <?php
          $status = strtolower(trim($trx['status'] ?? ''));
          switch ($status) {
            case 'menunggu konfirmasi penerimaan user':
              $warna = 'bg-yellow-400 w-1/3';
              $label = 'Menunggu Konfirmasi (COD)';
              break;
            case 'menunggu pembayaran':
            case 'menunggu upload bukti pembayaran':
              $warna = 'bg-orange-400 w-1/6';
              $label = 'Menunggu Upload Bukti Pembayaran';
              break;
            case 'pending':
            case 'menunggu konfirmasi admin':
              $warna = 'bg-yellow-400 w-1/3';
              $label = 'Menunggu Admin Konfirmasi Admin';
              break;
            case 'disetujui':
            case 'diproses':
              $warna = 'bg-blue-500 w-2/3';
              $label = 'Pesanan Disetujui';
              break;
            case 'selesai':
            case 'completed':
              $warna = 'bg-emerald-600 w-full';
              $label = 'Selesai';
              break;
            case 'ditolak':
            case 'batal':
            case 'dibatalkan':
            case 'dibatalkan oleh user':
            case 'dibatalkan oleh pengguna':
              $warna = 'bg-red-500 w-full';
              $label = 'Dibatalkan';
              break;
            default:
              $warna = 'bg-gray-300 w-0';
              $label = 'Tidak Diketahui';
          }
          ?>

          <!-- ✅ Progress Bar Adaptif -->
          <div class="mt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between text-[11px] sm:text-xs font-medium gap-1 sm:gap-2">
            <!-- Bar -->
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
              <div class="h-full <?= $warna ?> transition-all duration-700"></div>
            </div>
            <!-- Label -->
            <div class="text-gray-600 text-center sm:text-left leading-tight whitespace-normal break-words">
              <?= htmlspecialchars($label) ?>
            </div>
          </div>


          <!-- Detail -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 text-[13px] sm:text-sm text-gray-700 mt-4">
            <p><span class="text-gray-500">Pembayaran:</span> <?= htmlspecialchars($trx['metode_pembayaran'] ?? '-') ?></p>
            <p><span class="text-gray-500">Total:</span>
              <span class="text-emerald-600 font-semibold">Rp <?= number_format($trx['total'], 0, ',', '.') ?></span>
            </p>
            <p><span class="text-gray-500">Alamat:</span> <?= htmlspecialchars($trx['alamat']) ?></p>
            <p><span class="text-gray-500">No HP:</span> <?= htmlspecialchars($trx['no_hp']) ?></p>
          </div>

          <!-- Daftar Produk -->
          <div class="mt-3 sm:mt-4">
            <h3 class="text-[11px] sm:text-sm font-semibold text-gray-500 mb-2 uppercase">Daftar Produk</h3>
            <?php
            $stmt2 = $koneksi->prepare("SELECT d.qty, d.harga, d.ukuran, p.nama AS nama_produk, p.gambar
                                        FROM detail_transaksi d
                                        LEFT JOIN produk p ON d.produk_id = p.id
                                        WHERE d.transaksi_id = ?
                                        ORDER BY d.id ASC");
            $stmt2->bind_param("i", $trx['id']);
            $stmt2->execute();
            $items = $stmt2->get_result();
            while ($item = $items->fetch_assoc()):
            ?>
              <div class="border border-gray-200 rounded-lg sm:rounded-xl bg-white shadow-sm p-3 sm:p-4 mb-2 sm:mb-3">
                <div class="flex items-center gap-3 sm:gap-4">
                  <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-50 border border-gray-300 rounded-md overflow-hidden flex items-center justify-center">
                    <img src="../img/<?= htmlspecialchars($item['gambar'] ?? 'no-image.png') ?>"
                      alt="<?= htmlspecialchars($item['nama_produk']) ?>"
                      class="object-contain w-full h-full">
                  </div>
                  <div class="flex-1">
                    <p class="font-medium text-gray-800 text-sm sm:text-base"><?= htmlspecialchars($item['nama_produk']) ?></p>
                    <p class="text-[11px] sm:text-xs text-gray-500">Ukuran: <?= htmlspecialchars($item['ukuran'] ?: '-') ?> • x<?= $item['qty'] ?> • Rp<?= number_format($item['harga'], 0, ',', '.') ?></p>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>

          <!-- Tombol Aksi -->
          <div class="mt-5 flex flex-col sm:flex-wrap sm:flex-row gap-2 sm:gap-3">
            <?php if ($trx['status'] === 'disetujui'): ?>
              <form action="update_status_user.php" method="post" onsubmit="return confirmPesanan(event, <?= $trx['id'] ?>)">
                <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                <button type="submit"
                  class="w-full sm:w-auto px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm shadow-sm transition cursor-pointer">
                  ✅ Pesanan Diterima
                </button>
              </form>
            <?php elseif (in_array(strtolower($trx['status']), ['menunggu pembayaran', 'pending'])): ?>
              <div class="flex flex-col sm:flex-row gap-2 w-full">
                <form action="batal_transaksi.php" method="post" onsubmit="return confirmBatal(event, <?= $trx['id'] ?>)" class="w-full sm:w-auto">
                  <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                  <button type="submit"
                    class="w-full sm:w-auto flex justify-center items-center gap-2 px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700 shadow-sm cursor-pointer">
                    ❌ Batalkan
                  </button>
                </form>

                <?php if (empty($trx['bukti_transfer']) && strtolower($trx['metode_pembayaran']) !== 'cod'): ?>
                  <a href="../user/upload_bukti.php?id=<?= $trx['id'] ?>"
                    class="w-full sm:w-auto flex justify-center items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm shadow-sm cursor-pointer">
                    📤 Upload Bukti Pembayaran
                  </a>
                <?php elseif (strtolower($trx['metode_pembayaran']) === 'cod'): ?>
                  <span class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-md text-sm text-center">💵 COD</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (in_array(strtolower($trx['status']), ['selesai', 'batal', 'dibatalkan oleh user', 'ditolak'])): ?>
              <form action="hapus_riwayat.php" method="post" onsubmit="return confirmDeleteTransaksi(event, <?= $trx['id'] ?>)" class="w-full sm:w-auto">
                <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                <button type="submit"
                  class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm shadow-sm cursor-pointer">
                  🗑 Hapus Riwayat
                </button>
              </form>
            <?php endif; ?>

            <?php if (!empty($trx['bukti_transfer'])): ?>
              <span class="px-4 py-2 bg-gray-200 text-gray-600 rounded-md text-sm shadow-sm text-center">✅ Bukti sudah diupload</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>

    <!-- Tombol Kembali -->
    <div class="mt-10 text-left">
      <a href="<?= htmlspecialchars($backUrl) ?>"
        class="inline-flex items-left justify-start gap-2 px-5 sm:px-6 py-2 sm:py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition shadow text-sm sm:text-base">
        ⬅ Kembali
      </a>
    </div>
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

    function confirmBatal(e, id) {
      e.preventDefault();

      Swal.fire({
        title: 'Batalkan Pesanan?',
        text: "Apakah anda yakin ingin membatalkan pesanan ini? Proses ini tidak bisa diurungkan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak Jadi'
      }).then((result) => {
        if (result.isConfirmed) {
          e.target.submit();
        }
      });

      return false;
    }

    function confirmDeleteTransaksi(e, id) {
      e.preventDefault();

      Swal.fire({
        title: 'Hapus Transaksi?',
        text: "Apakah anda yakin ingin menghapus transaksi ini? Proses ini tidak bisa diurungkan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Tidak'
      }).then((result) => {
        if (result.isConfirmed) {
          e.target.submit();
        }
      });

      return false;
    }

    function confirmPesanan(e, id) {
      e.preventDefault();

      Swal.fire({
        title: 'Konfirmasi Penerimaan Pesanan?',
        text: "Apakah anda sudah menerima pesanan ini? Proses ini tidak bisa diurungkan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Tidak'
      }).then((result) => {
        if (result.isConfirmed) {
          e.target.submit();
        }
      });

      return false;
    }
  </script>
</body>

</html>