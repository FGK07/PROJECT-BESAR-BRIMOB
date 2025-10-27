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
  <title>Riwayat Transaksi - BRIMOB SPORT</title>
  <link rel="stylesheet" href="../src/output.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="min-h-screen bg-gray-100 text-gray-800 font-sans">

  <!-- Flash Message -->
  <?php if (isset($_SESSION["flash"])): ?>
    <div id="flash"
      class="fixed top-5 left-1/2 -translate-x-1/2 bg-emerald-600 text-white font-medium px-6 py-3 rounded-lg shadow-md z-50">
      <?= htmlspecialchars($_SESSION["flash"]) ?>
    </div>
    <?php unset($_SESSION["flash"]); ?>
  <?php endif; ?>

  <!-- Header -->
  <header class="text-center py-10 bg-white border-b border-gray-200 shadow-sm">
    <h1 class="text-4xl font-bold text-gray-800 mb-1 tracking-wide">Riwayat Transaksi</h1>
    <p class="text-gray-500 text-sm">Cek status dan detail pembelianmu di BRIMOB SPORT</p>
  </header>

  <main class="max-w-5xl mx-auto px-5 pb-16 mt-10">
    <?php if ($transaksiList->num_rows === 0): ?>
      <div class="bg-white border border-gray-200 rounded-xl p-10 text-center shadow-sm">
        <p class="text-lg text-gray-600">Belum ada transaksi tercatat.</p>
      </div>
    <?php else: ?>
      <?php while ($trx = $transaksiList->fetch_assoc()): ?>
        <div class="mb-6 bg-white border border-gray-200 rounded-2xl shadow-[0_4px_10px_rgba(0,0,0,0.07)] hover:shadow-[0_6px_14px_rgba(0,0,0,0.1)] transition-all duration-300 overflow-hidden">
          <div class="p-6">

            <!-- Header transaksi -->
            <div class="flex justify-between items-start mb-4">
              <div>
                <h2 class="text-lg font-semibold text-gray-900">
                  Transaksi #<?= $trx['id'] ?>
                  <?php if (!empty($trx['pre_order_id'])): ?>
                    <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 border border-purple-300 font-medium">
                      Pre-Order
                    </span>
                  <?php endif; ?>
                </h2>
                <p class="text-xs text-gray-500 mt-1">Tanggal: <?= $trx['tanggal'] ?></p>
              </div>
            </div>

            <?php
            $status = strtolower(trim($trx['status'] ?? ''));

            switch ($status) {
              // 🟡 COD pre-order baru dibuat
              case 'menunggu konfirmasi penerimaan user':
                $warna = 'bg-yellow-400 w-1/3';
                $label = 'Menunggu Konfirmasi Penerimaan User (COD)';
                break;

              // 🟠 Belum upload bukti
              case 'menunggu pembayaran':
              case 'menunggu upload bukti pembayaran':
                $warna = 'bg-orange-400 w-1/6';
                $label = 'Menunggu Upload Bukti Pembayaran';
                break;

              // 🟡 Admin belum konfirmasi
              case 'pending':
              case 'menunggu konfirmasi admin':
                $warna = 'bg-yellow-400 w-1/3';
                $label = 'Menunggu Konfirmasi Admin';
                break;

              // 🔵 Pesanan sudah disetujui
              case 'disetujui':
              case 'diproses':
                $warna = 'bg-blue-500 w-2/3';
                $label = 'Pesanan Diproses';
                break;

              // 🟢 Selesai
              case 'selesai':
              case 'completed':
                $warna = 'bg-emerald-600 w-full';
                $label = 'Pesanan Selesai';
                break;

              // 🔴 Ditolak / dibatalkan
              case 'ditolak':
              case 'batal':
              case 'dibatalkan':
              case 'dibatalkan oleh user':
              case 'dibatalkan oleh pengguna':
                $warna = 'bg-red-500 w-full';
                $label = 'Dibatalkan / Ditolak';
                break;

              // ⚪ Fallback
              default:
                $warna = 'bg-gray-300 w-0';
                $label = 'Status tidak diketahui';
            }

            ?>

            <!-- ✅ Progress Bar -->
            <div class="mt-4 flex items-center justify-between text-xs font-medium">
              <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full <?= $warna ?> transition-all duration-700"></div>
              </div>
              <div class="ml-3 text-gray-600"><?= htmlspecialchars($label) ?></div>
            </div>

            <!-- Detail transaksi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm mb-4 text-gray-700 mt-4">
              <p><span class="text-gray-500">Pembayaran:</span>
                <?php if (!empty($trx['metode_pembayaran'])): ?>
                  <?= htmlspecialchars($trx['metode_pembayaran']) ?>
                <?php endif; ?>
              </p>
              <p><span class="text-gray-500">Metode:</span>
                <?php if (!empty($trx['pre_order_id'])): ?>
                  <span class="italic text-gray-500">Pre-Order (menunggu proses admin)</span>
                <?php endif; ?>
              </p>
              <p><span class="text-gray-500">Total:</span>
                <span class="text-emerald-600 font-semibold">Rp <?= number_format($trx['total'], 0, ',', '.') ?></span>
              </p>
              <p><span class="text-gray-500">Alamat:</span> <?= htmlspecialchars($trx['alamat']) ?></p>
              <p><span class="text-gray-500">No HP:</span> <?= htmlspecialchars($trx['no_hp']) ?></p>
            </div>

            <!-- Daftar Produk -->
            <div class="mt-4">
              <h3 class="text-sm font-semibold text-gray-500 mb-2 uppercase tracking-wide">Daftar Produk</h3>

              <?php
              $stmt2 = $koneksi->prepare("SELECT d.qty, d.harga, d.ukuran, p.nama AS nama_produk, p.gambar
                                          FROM detail_transaksi d
                                          LEFT JOIN produk p ON d.produk_id = p.id
                                          WHERE d.transaksi_id = ?
                                          ORDER BY d.id ASC
                                        ");
              $stmt2->bind_param("i", $trx['id']);
              $stmt2->execute();
              $items = $stmt2->get_result();

              while ($item = $items->fetch_assoc()):
              ?>
                <div class="border border-gray-200 rounded-xl bg-white shadow-sm p-4 mb-3 hover:shadow-md transition-all duration-200">
                  <div class="flex items-center gap-4">
                    <div class="w-16 h-16 flex items-center justify-center bg-gray-50 border border-gray-300 rounded-md overflow-hidden">
                      <img src="../img/<?= htmlspecialchars($item['gambar'] ?? 'no-image.png') ?>"
                        alt="<?= htmlspecialchars($item['nama_produk']) ?>"
                        class="object-contain w-full h-full">
                    </div>
                    <div class="flex-1">
                      <p class="font-medium text-gray-800"><?= htmlspecialchars($item['nama_produk']) ?></p>
                      <p class="text-xs text-gray-500">
                        Ukuran: <?= htmlspecialchars($item['ukuran'] ?: '-') ?> • x<?= $item['qty'] ?> • Rp<?= number_format($item['harga'], 0, ',', '.') ?>
                      </p>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>

            <!-- Tombol aksi -->
            <div class="mt-6 flex flex-wrap items-center gap-3">
              <?php if ($trx['status'] === 'disetujui'): ?>
                <form action="update_status_user.php" method="post"
                  onsubmit="return confirmPesanan(event, <?= $trx['id'] ?>)">
                  <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                  <button type="submit"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm font-medium shadow-sm transition cursor-pointer hover:scale-[1.03] active:scale-[0.98]">
                    ✅ Pesanan Diterima
                  </button>
                </form>
              <?php elseif (in_array($trx['status'], ['menunggu pembayaran', 'pending'])): ?>
                <!-- Tombol Batalkan Pesanan -->
                <form action="batal_transaksi.php" method="post" class="relative group"
                  onsubmit="return confirmBatal(event, <?= $trx['id'] ?>)">
                  <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                  <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm transition-all duration-200
                            bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 focus:ring-2 focus:ring-red-400 focus:ring-offset-1
                            hover:scale-[1.03] active:scale-[0.98] cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Batalkan Pesanan
                  </button>
                </form>
                <!-- Tombol Upload Bukti Pembayaran -->
                <!-- Tombol Upload Bukti Pembayaran -->
                <?php if (empty($trx['bukti_transfer']) && strtolower($trx['metode_pembayaran']) !== 'cod'): ?>
                  <a href="../user/upload_bukti.php?id=<?= $trx['id'] ?>"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium shadow-sm transition flex items-center gap-2 hover:scale-[1.03] active:scale-[0.98] cursor-pointer">
                    📤 Upload Bukti Pembayaran
                  </a>
                <?php elseif (strtolower($trx['metode_pembayaran']) === 'cod'): ?>
                  <span class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-md text-sm font-medium shadow-sm flex items-center gap-2">
                    💵 Bayar di Tempat (COD)
                  </span>
                <?php else: ?>
                  <span class="px-4 py-2 bg-gray-200 text-gray-600 rounded-md text-sm font-medium shadow-sm flex items-center gap-2">
                    ✅ Bukti sudah diupload
                  </span>
                <?php endif; ?>

              <?php endif; ?>
              <?php if ($trx['status'] === 'selesai' || $trx['status'] === 'batal' || $trx['status'] === 'dibatalkan oleh user' || $trx['status'] === 'ditolak') : ?>
                <form action="hapus_riwayat.php" method="post"
                  onsubmit="return confirmDeleteTransaksi(event, <?= $trx['id'] ?>)">
                  <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                  <button type="submit"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium shadow-sm transition hover:scale-[1.03] active:scale-[0.98 cursor-pointer">
                    🗑 Hapus Riwayat
                  </button>
                </form>
              <?php endif; ?>
            </div>

          </div>
        </div>

      <?php endwhile; ?>
    <?php endif; ?>

    <!-- Tombol Kembali -->
    <div class="mt-10">
      <a href="<?= htmlspecialchars($backUrl) ?>"
        class="inline-flex items-center gap-2 px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition shadow">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Kembali
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
        title: 'Hapus Produk?',
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