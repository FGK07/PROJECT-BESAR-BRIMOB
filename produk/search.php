<?php
session_start();
include "../koneksi.php";

$q = trim($_GET['q'] ?? "");
$slug = trim($_GET['kategori'] ?? $_POST['kategori'] ?? "");

// Query pencarian
$stmt = $koneksi->prepare("SELECT id, nama, harga, gambar FROM produk WHERE nama LIKE ?");
$search = "%" . $q . "%";
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

// Ambil parameter GET (pakai null-coalescing untuk aman)
$_SESSION['lastFrom'] = 'search';
$_SESSION['lastKategori'] = $_GET['kategori'] ?? '';
$_SESSION['lastQuery'] = $_GET['q'] ?? '';

$from      = $_GET['from']      ?? '';
$kategori  = $_GET['kategori']  ?? '';

// Deteksi role dari session
$isAdmin = isset($_SESSION['admin']);
$isUser  = isset($_SESSION['user']);

// Validasi & atur tombol kembali
if ($isAdmin) {
    // Jika admin login
    if ($from === "dashboard_admin") {
        $_SESSION['backUrl'] = "../admin/dashboard_admin.php";
    } elseif ($from === "kategori" && !empty($kategori)) {
        $_SESSION['backUrl'] = "../admin/kategori.php?nama=" . urlencode($kategori);
    } else {
        // fallback default admin
        $_SESSION['backUrl'] = "../admin/dashboard_admin.php";
    }
} elseif ($isUser) {
    // Jika user login
    if ($from === "homepage") {
        $_SESSION['backUrl'] = "../homepage.php";
    } elseif ($from === "kategori" && !empty($kategori)) {
        $_SESSION['backUrl'] = "../produk/kategori.php?nama=" . urlencode($kategori);
    } else {
        // fallback default user
        $_SESSION['backUrl'] = "../homepage.php";
    }
} else {
    // Jika tidak login (guest)
    $_SESSION['backUrl'] = "../homepage.php";
}

$backUrl = $_SESSION['backUrl'] ?? "../homepage.php";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="px-8 lg:px-30">
    <!-- Tampilkan Notif (flash) -->
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
    <h1 class="text-3xl font-bold mb-6 mt-3">
        Hasil Pencarian: <?= htmlspecialchars($q) ?>
    </h1>

    <?php if ($result->num_rows === 0): ?>
        <p class="text-lg">Produk tidak ditemukan.</p>
    <?php else: ?>
        <div class="grid grid-cols-3 gap-3 lg:grid-cols-4 lg:gap-10">
            <?php while ($row = $result->fetch_assoc()):
                $isFavorit = false;
                if (isset($_SESSION['user'])) {
                    $user_id = $_SESSION['user']['id'];
                    $produk_id = $row['id'];
                    $cekFav = mysqli_query($koneksi, "SELECT 1 FROM favorit WHERE user_id = $user_id AND produk_id = $produk_id");
                    $isFavorit = mysqli_num_rows($cekFav) > 0;
                } ?>
                <div class="w-auto sm:w-[180px] lg:px-2 py-4 h-auto lg:w-auto rounded-2xl flex flex-col justify-between items-center shadow-[0_0_10px_rgba(0,0,0,0.3)] hover:scale-105 transition-transform duration-200 ease-in-out cursor-pointer">
                    <a href="detail_produk.php?id=<?= $row['id'] ?>" class="flex flex-col justify-between items-center">

                        <!-- gambar -->
                        <img src="../img/<?= htmlspecialchars($row['gambar']) ?>"
                            alt="<?= htmlspecialchars($row['nama']) ?>"
                            class="h-20 w-40 px-2 lg:h-40 object-contain mb-4">

                        <!-- nama -->
                        <p class="font-bold px-[8px] text-[10px] text-center lg:text-lg lg:text-center min-h-[60px]">
                            <?= htmlspecialchars($row['nama']) ?>
                        </p>

                        <!-- harga -->
                        <p class="text-[8px] font-bold lg:text-lg text-gray-600">
                            Rp <?= number_format($row['harga'], 2, ',', '.') ?>
                        </p>
                    </a>
                    <?php if (isset($_SESSION['user'])): ?>
                        <div class="cursor-pointer flex items-start justify-between lg:justify-evenly gap-1 sm:gap-2 w-full mt-2 sm:mt-3 lg:px-2 px-1">
                            <a href="detail_produk.php?id=<?= urlencode($row['id']) ?>&from=search&kategori=<?= urlencode($slug) ?>&q=<?= urlencode($q) ?>"
                                class=" w-12 py-[6px] px-2 h-6 sm:w-16 sm:h-10 text-center text-[7px] sm:text-[16px] font-medium bg-black text-white rounded-md sm:rounded-lg hover:bg-gray-700 transition-all shadow-sm">
                                Beli
                            </a>
                            <!-- Tombol favorit -->
                            <form action="../user/favorit.php" method="POST">
                                <input type="hidden" name="produk_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($q) ?>">

                                <?php if ($isFavorit): ?>
                                    <!-- Sudah difavoritkan -->
                                    <button type="submit" name="hapus"
                                        class="bookmarkBtn w-15 h-6 flex py-[4px] px-1 items-center justify-center lg:gap-2 lg:px-4 lg:py-2 lg:w-36 lg:h-10 lg:text-[16px] font-medium bg-amber-500 text-white rounded-md lg:rounded-lg hover:bg-amber-600 transition-all shadow-sm cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                            class="bookmarkIcon w-[12px] h-[14px] lg:w-[25px] lg:h-[24px] align-middle relative top-[1px]">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M5.25 5.25v13.5L12 15l6.75 3.75V5.25A2.25 2.25 0 0016.5 3h-9A2.25 2.25 0 005.25 5.25z" />
                                        </svg>
                                        <span class="bookmarkText align-middle leading-none text-[8px] lg:text-[16px]">Tersimpan</span>
                                    </button>
                                <?php else: ?>
                                    <!-- Belum difavoritkan -->
                                    <button type="submit" name="tambah"
                                        class="bookmarkBtn w-15 h-6 flex py-[4px] px-2 items-center justify-center lg:gap-2 lg:px-4 lg:py-2 lg:w-36 lg:h-10 lg:text-[16px] font-medium bg-gray-200 text-gray-800 lg:rounded-lg rounded-md hover:bg-gray-300 transition-all shadow-sm cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                            class="bookmarkIcon w-[12px] h-[14px] lg:w-[25px] lg:h-[24px] align-middle relative top-[1px]">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M5.25 5.25v13.5L12 15l6.75 3.75V5.25A2.25 2.25 0 0016.5 3h-9A2.25 2.25 0 005.25 5.25z" />
                                        </svg>
                                        <span class="bookmarkText align-middle leading-none text-[7px] lg:text-[16px]">Favorit</span>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['admin'])): ?>
                        <div class="cursor-pointer flex items-start justify-between lg:justify-evenly gap-1 sm:gap-2 w-full mt-2 sm:mt-3 lg:px-2 px-1">
                            <a href="../admin/edit_produk.php?id=<?= $row['id'] ?>&from=search&kategori=<?= htmlspecialchars(urlencode($kategori)) ?>&q=<?= htmlspecialchars($q) ?>"
                                class="w-12 py-[6px] px-2 h-6 sm:w-16 sm:h-10 text-center text-[7px] sm:text-[16px] font-medium bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">Edit</a>
                            <a href="../admin/hapus_produk.php?id=<?= $row['id'] ?>&from=kategori&kategori=<?= htmlspecialchars(urlencode($kategori)) ?>&q=<?= htmlspecialchars($q) ?>"
                                onclick="return confirm('Yakin ingin menghapus produk ini?')"
                                class="w-12 py-[6px] px-2 h-6 sm:w-16 sm:h-10 text-center text-[7px] sm:text-[16px] font-medium bg-red-500 text-white rounded hover:bg-red-600 text-sm">Hapus</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
    <div class="relative mt-10 mb-8 left-0 m-0 p-0 z-[900]">
        <a href="<?= htmlspecialchars($backUrl) ?>"
            class="px-2 lg:px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600">← Kembali</a>
    </div>
    <script>
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0"; // mulai fade out
                setTimeout(() => flash.remove(), 1000); // hapus setelah 1 detik
            }
        }, 3000); // tampil 3 detik dulu
    </script>
</body>

</html>