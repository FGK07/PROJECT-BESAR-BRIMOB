<?php
include "../koneksi.php";
session_start();

// === Pastikan ada ID produk ===
if (!isset($_GET['id'])) {
    header("Location: ../homepage.php");
    exit;
}

// === Ambil url ===
$id = (int) $_GET['id'];
$kategori = $_GET['kategori'] ?? '';

// === Ambil data produk ===
$stmt = $koneksi->prepare("SELECT p.id, p.nama, p.harga, p.gambar, p.deskripsi, p.stok, k.nama AS kategori FROM produk p 
JOIN kategori k ON p.kategori_id = k.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();

if (!$produk) {
    header("Location: ../homepage.php");
    exit;
}


// === Ambil ukuran produk + stok per ukuran ===
$stmt = $koneksi->prepare("SELECT u.size, pu.stok 
    FROM produk_ukuran pu 
    JOIN ukuran u ON pu.ukuran_id = u.id 
    WHERE pu.produk_id = ? 
    ORDER BY u.size ASC");
$stmt->bind_param("i", $produk['id']);
$stmt->execute();
$resultUkuran = $stmt->get_result();

// === Hitung total stok dan simpan semua ukuran ===
$totalStok = 0;
$ukuranList = [];
while ($u = $resultUkuran->fetch_assoc()) {
    $ukuranList[] = $u;
    $totalStok += (int)$u['stok'];
}

$stmt->bind_param("i", $produk['id']);
$stmt->execute();
$resultUkuran = $stmt->get_result();

// === Tombol kembali dinamis ===
$backUrl = "../homepage.php"; // default
$from = $_GET['from'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$q = $_GET['q'] ?? '';

if ($from === "homepage") {
    $backUrl = "../homepage.php";
} elseif ($from === "dashboard_admin") {
    $backUrl = "../admin/dashboard_admin.php";
} elseif ($from === "kategori" && $kategori) {
    // Kalau admin buka dari kategori admin
    if (isset($_SESSION['admin'])) {
        $backUrl = "../admin/kategori.php?nama=" . urlencode($kategori);
    } else {
        // Kalau user biasa
        $backUrl = "kategori.php?nama=" . urlencode($kategori);
    }
} elseif ($from === "favorit") {
    $backUrl = "../user/daftar_favorit.php";
} elseif ($from === "search" && $kategori) {
    $backUrl = "search.php?from=kategori&kategori=" . urlencode($kategori) . "&q=" . urlencode($q);
} elseif (isset($_SESSION['admin'])) {
    // jika admin buka tanpa 'from'
    $backUrl = "../admin/dashboard_admin.php";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($produk['nama']) ?> - Detail Produk</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen bg-gray-100 text-gray-800">

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

    <!-- Tombol Kembali -->
    <div class="mt-10 sm:mt-12 ml-4 sm:ml-8">
        <a href="<?= htmlspecialchars($backUrl) ?>"
            class="inline-flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all text-sm sm:text-base shadow">
            ← Kembali
        </a>
    </div>

    <!-- Container Utama -->
    <div class="flex justify-center items-start py-6 sm:py-10 px-3 sm:px-6">
        <div
            class="px-4 sm:px-8 py-4 sm:py-6 w-full max-w-6xl grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-10 justify-center items-start bg-white rounded-2xl shadow-[0_0_10px_rgba(0,0,0,0.15)] sm:shadow-[0_0_15px_rgba(0,0,0,0.2)]">

            <!-- Gambar Produk -->
            <div class="flex justify-center items-center">
                <img src="../img/<?= htmlspecialchars($produk['gambar']) ?>"
                    alt="<?= htmlspecialchars($produk['nama']) ?>"
                    class="w-64 h-64 sm:w-96 sm:h-96 object-contain rounded-2xl shadow-md">
            </div>

            <!-- Detail Produk -->
            <div class="px-1 sm:px-0">
                <h1 class="text-2xl sm:text-4xl font-bold mb-3 sm:mb-4"><?= htmlspecialchars($produk['nama']) ?></h1>
                <p class="text-xl sm:text-2xl text-gray-800 mb-2 sm:mb-3">
                    <?= "Rp " . number_format($produk['harga'], 2, ',', '.') ?>
                </p>

                <!-- Stok Dinamis -->
                <?php
                if ($totalStok > 10) {
                    $stokClass = "text-emerald-600";
                    $stokLabel = "Stok tersedia ($totalStok)";
                } elseif ($totalStok > 0 && $totalStok <= 10) {
                    $stokClass = "text-yellow-600";
                    $stokLabel = "Stok terbatas ($totalStok)";
                } else {
                    $stokClass = "text-red-600 font-semibold";
                    $stokLabel = "Stok habis";
                }
                ?>
                <p class="text-sm sm:text-base font-medium mb-2 sm:mb-3">
                    <span class="<?= $stokClass ?>">🔸 <?= $stokLabel ?></span>
                </p>

                <p class="text-sm sm:text-lg text-gray-600 mb-3 sm:mb-4">
                    Kategori: <?= htmlspecialchars($produk['kategori']) ?>
                </p>

                <h2 class="text-lg sm:text-xl font-semibold mb-2">Deskripsi Produk</h2>
                <div
                    class="w-full h-32 sm:h-48 p-3 sm:p-4 bg-gray-50 rounded-lg overflow-auto border text-sm sm:text-base">
                    <p class="text-gray-700 leading-relaxed">
                        <?= nl2br(htmlspecialchars($produk['deskripsi'])) ?>
                    </p>
                </div>

                <!-- Tombol Aksi -->
                <?php if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])): ?>
                    <!-- Mode User -->
                    <form method="post" class="mt-5 sm:mt-6 space-y-3 sm:space-y-4">
                        <input type="hidden" name="produk_id" value="<?= $produk['id'] ?>">
                        <input type="hidden" name="redirect" value="detail_produk.php?id=<?= htmlspecialchars($produk['id']) ?>">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($_GET['kategori'] ?? '') ?>">

                        <?php if ($totalStok > 0): ?>
                            <h2 class="text-lg sm:text-xl font-semibold mb-2">Pilih Ukuran</h2>
                            <div class="flex flex-wrap gap-2 sm:gap-3 mb-4 sm:mb-6">
                                <?php foreach ($ukuranList as $row): ?>
                                    <label
                                        class="flex items-center space-x-2 <?= ($row['stok'] <= 0 ? 'opacity-50 cursor-not-allowed' : '') ?>">
                                        <input type="radio" name="ukuran"
                                            value="<?= htmlspecialchars($row['size']) ?>"
                                            <?= $row['stok'] <= 0 ? 'disabled' : '' ?> required
                                            class="w-4 h-4 sm:w-5 sm:h-5 text-black focus:ring-black cursor-pointer">
                                        <span class="text-sm sm:text-lg">
                                            <?= htmlspecialchars($row['size']) ?>
                                            <span class="text-xs sm:text-sm text-gray-500">(<?= $row['stok'] ?>)</span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:gap-4 w-full">
                                <button type="submit"
                                    formaction="../user/beli_sekarang.php?from=detail_produk&id=<?= htmlspecialchars($produk['id']) ?>&kategori=<?= htmlspecialchars($kategori) ?>"
                                    class="px-5 sm:px-6 py-3 w-full sm:w-auto bg-black text-white rounded-lg hover:bg-gray-800 transition-all cursor-pointer text-sm sm:text-base">
                                    Beli Sekarang
                                </button>

                                <button type="submit" formaction="../user/tambah_keranjang.php"
                                    class="px-5 sm:px-6 py-3 w-full sm:w-auto bg-white text-black rounded-lg hover:bg-gray-100 border transition-all cursor-pointer text-sm sm:text-base">
                                    Tambah ke Keranjang
                                </button>
                            </div>

                        <?php else: ?>
                            <div class="mt-4 sm:mt-6">
                                <button type="submit"
                                    formaction="../user/pre_order_form.php?from=detail_produk&id=<?= htmlspecialchars($produk['id']) ?>&home=<?= htmlspecialchars($from) ?>"
                                    class="px-5 sm:px-6 py-3 w-full sm:w-auto bg-black text-white rounded-lg hover:bg-gray-800 transition-all cursor-pointer text-sm sm:text-base">
                                    Pre Order
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>

                <?php else: ?>
                    <!-- Mode Admin -->
                    <div class="mt-5 sm:mt-6 p-3 sm:p-4 bg-gray-50 rounded-lg border text-gray-700 text-sm sm:text-base">
                        <p class="font-semibold mb-2">🔧 Mode Admin</p>
                        <p class="text-gray-600 mb-1">Anda sedang melihat detail produk ini sebagai <span
                                class="font-semibold">Admin</span>.</p>
                        <div class="flex flex-col sm:flex-row gap-2 mt-2">
                            <a href="../admin/edit_produk.php?id=<?= $produk['id'] ?>&from=detail_produk&kategori=<?= urlencode($kategori) ?>"
                                class="px-4 sm:px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center">
                                ✏️ Edit Produk
                            </a>
                            <a href="../admin/hapus_produk.php?id=<?= $produk['id'] ?>&from=detail_produk&kategori=<?= urlencode($kategori) ?>"
                                class="px-4 sm:px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center">
                                🗑️ Hapus Produk
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0";
                setTimeout(() => flash.remove(), 800);
            }
        }, 3000);
    </script>
</body>


</html>