<?php
session_start();
include "../koneksi.php";

$cart = $_SESSION['cart'] ?? [];
$produkList = [];
$total = 0;

if (!empty($cart)) {
  foreach ($cart as $key => $qty) {
    // pecah key jadi id dan ukuran
    list($id, $ukuran) = explode("-", $key);

    // ambil data produk
    $stmt = $koneksi->prepare("SELECT id, nama, harga, gambar FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
      $row['qty'] = $qty;
      $row['subtotal'] = $row['harga'] * $qty;
      $row['ukuran'] = $ukuran; // simpan ukuran yang dipilih user
      $produkList[] = $row;
      $total += $row['subtotal'];
    }
  }
}
if (isset($_GET['from'])) {
  // Jika user datang dari homepage
  if ($_GET['from'] === "homepage") {
    $backUrl = "../homepage.php";
  }
  // Jika user datang dari kategori tertentu
  elseif ($_GET['from'] === "kategori" && !empty($_GET['kategori'])) {
    $backUrl = "../produk/kategori.php?nama=" . urlencode($_GET['kategori']);
  }
  // Jika user datang dari riwayat setelah checkout
  elseif ($_GET['from'] === "checkout" && !empty($_GET['kategori'])) {
    // Kembalikan ke kategori asal dari mana dia checkout
    $backUrl = "../produk/kategori.php?nama=" . urlencode($_GET['kategori']);
  }
  // Jika asal tidak dikenal, fallback ke homepage
  else {
    $backUrl = "../homepage.php";
  }
}
// Jika tidak ada parameter from, tapi ada referer (bukan dari riwayat)
elseif (!empty($_SERVER['HTTP_REFERER']) && !str_contains($_SERVER['HTTP_REFERER'], 'riwayat.php')) {
  $backUrl = $_SERVER['HTTP_REFERER'];
}
// Fallback default
else {
  $backUrl = "../homepage.php";
}


?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Keranjang Belanja - BRIMOB SPORT</title>
  <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen bg-gray-100 text-gray-800 font-sans px-3 py-6 sm:px-6 sm:py-8">

  <!-- Flash Message -->
  <?php if (isset($_SESSION["flash"])): ?>
    <div id="flash" class="fixed top-5 left-1/2 -translate-x-1/2 bg-emerald-600 text-white font-medium px-6 py-3 rounded-lg shadow-md z-50">
      <?= htmlspecialchars($_SESSION["flash"]) ?>
    </div>
    <?php unset($_SESSION["flash"]); ?>
  <?php endif; ?>

  <!--  Header -->
  <header class="text-center mb-10">
    <h1 class="text-4xl font-bold text-gray-900 mb-2 tracking-wide">Keranjang Belanja</h1>
    <p class="text-gray-500 text-sm">Lihat dan kelola produk yang ingin kamu beli</p>
  </header>

  <!-- Kontainer utama -->
  <main class="max-w-7xl mx-auto bg-white border border-gray-200 rounded-2xl shadow-[0_4px_10px_rgba(0,0,0,0.07)] p-6 sm:p-8">

    <?php if (empty($produkList)): ?>
      <div class="text-center py-16">
        <p class="text-lg text-gray-600">Keranjang kamu masih kosong 🛍️</p>
        <a href="../homepage.php" class="inline-block mt-6 px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-800 transition">
          Belanja Sekarang
        </a>
      </div>

    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full border border-gray-200 rounded-lg overflow-hidden text-sm">
          <thead class="bg-gray-100 border-b border-gray-200">
            <tr class="text-left text-gray-700">
              <th class="p-3 font-semibold">Gambar</th>
              <th class="p-3 font-semibold">Nama</th>
              <th class="p-3 font-semibold">Ukuran</th>
              <th class="p-3 font-semibold">Harga</th>
              <th class="p-3 font-semibold">Qty</th>
              <th class="p-3 font-semibold">Subtotal</th>
              <th class="p-3 font-semibold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($produkList as $item): ?>
              <tr class="border-b hover:bg-gray-50 transition">
                <td class="p-3">
                  <img src="../img/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>" class="w-16 h-16 object-contain rounded border border-gray-200">
                </td>
                <td class="p-3 text-gray-800 font-medium"><?= htmlspecialchars($item['nama']) ?></td>
                <td class="p-3 text-gray-600"><?= htmlspecialchars($item['ukuran']) ?></td>
                <td class="p-3 text-gray-800">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                <td class="p-3 text-gray-700"><?= $item['qty'] ?></td>
                <td class="p-3 font-semibold text-emerald-600">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                <td class="p-3 text-center">
                  <div class="flex justify-center items-center gap-2">
                    <form action="hapus_keranjang.php" method="post">
                      <input type="hidden" name="key" value="<?= $item['id'] . '-' . $item['ukuran'] ?>">
                      <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? 'homepage') ?>">
                      <input type="hidden" name="kategori" value="<?= htmlspecialchars($_GET['kategori'] ?? '') ?>">
                      <button type="submit"
                        class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs font-medium cursor-pointer">
                        Hapus
                      </button>
                    </form>

                    <form action="checkout.php" method="get">
                      <input type="hidden" name="from" value="keranjang">
                      <input type="hidden" name="single" value="true">
                      <input type="hidden" name="kategori" value="<?= urlencode($_GET['kategori'] ?? '') ?>">
                      <input type="hidden" name="produk_id" value="<?= htmlspecialchars($item['id']) ?>">
                      <input type="hidden" name="ukuran" value="<?= htmlspecialchars($item['ukuran']) ?>">
                      <button type="submit"
                        class="px-3 py-1 bg-black text-white rounded hover:bg-gray-800 text-xs font-medium cursor-pointer">
                        Checkout
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>

            <tr class="font-bold bg-gray-100 text-gray-800">
              <td colspan="5" class="p-3 text-right border-t border-gray-200">Total</td>
              <td class="p-3 border-t border-gray-200 text-emerald-600">Rp <?= number_format($total, 0, ',', '.') ?></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tombol Checkout Semua -->
      <div class="mt-6 flex justify-between items-center">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition shadow">
          ← Kembali
        </a>
        <a href="checkout.php?from=keranjang" class="inline-block px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-900 transition shadow">
          Checkout Semua
        </a>
      </div>
    <?php endif; ?>
  </main>

  <script>
    setTimeout(() => {
      const flash = document.getElementById('flash');
      if (flash) {
        flash.style.opacity = "0";
        flash.style.transition = "opacity 0.6s ease";
        setTimeout(() => flash.remove(), 600);
      }
    }, 3000);
  </script>

</body>

</html>