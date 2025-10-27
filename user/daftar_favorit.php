<?php
session_start();
include "../koneksi.php";

// Cek login
if (!isset($_SESSION['user'])) {
    $_SESSION['flash'] = "Silakan login terlebih dahulu untuk melihat daftar favorit.";
    header("Location: login_user.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Ambil produk favorit user
$result = mysqli_query($koneksi, "
    SELECT p.id, p.nama, p.harga, p.gambar
    FROM favorit f
    JOIN produk p ON p.id = f.produk_id
    WHERE f.user_id = $user_id
");

// Default tombol kembali ke homepage
$backUrl = "../homepage.php";

// Buat tombol "Kembali" dinamis berdasarkan asal halaman
if (isset($_GET['from'])) {
    $from = $_GET['from'];

    switch ($from) {
        case "homepage":
            $backUrl = "../homepage.php";
            break;

        case "dashboard_admin":
            $backUrl = "../dashboard_admin.php";
            break;

        case "kategori":
            // kalau user datang dari kategori tertentu
            if (isset($_GET['kategori'])) {
                $kategori = urlencode($_GET['kategori']);
                $backUrl = "../produk/kategori.php?nama={$kategori}";
            } else {
                // fallback kalau kategori nggak dikirim
                $backUrl = "../homepage.php";
            }
            break;

        case "favorit":
            // kalau datang dari daftar favorit lagi
            $backUrl = "../user/daftar_favorit.php";
            break;

        default:
            $backUrl = "../homepage.php";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorit Saya</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="bg-gray-100 font-inter">

    <!-- Notifikasi Flash -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md px-4 py-3 mb-6 text-center font-medium shadow-sm w-full">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="max-w-6xl mx-auto px-6 py-10">
        <h1 class="text-3xl font-bold mb-8 text-gray-800 border-b-2 border-amber-400 pb-3">Daftar Favorit Saya</h1>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="bg-white rounded-2xl shadow-md hover:shadow-lg transition-shadow duration-300 flex flex-col justify-between p-4">

                        <!-- Klik gambar → ke detail produk -->
                        <a href="../produk/detail_produk.php?id=<?= htmlspecialchars($row['id']) ?>&from=favorit"
                            class="block group">
                            <img src="../img/<?= htmlspecialchars($row['gambar']) ?>"
                                alt="<?= htmlspecialchars($row['nama']) ?>"
                                class="w-full h-48 object-contain rounded-lg mb-4 bg-gray-50 group-hover:scale-105 transition-transform duration-200">
                        </a>

                        <!-- Nama dan harga (nama juga bisa diklik) -->
                        <div class="text-center">
                            <a href="../produk/detail_produk.php?id=<?= htmlspecialchars($row['id']) ?>&from=favorit"
                                class="font-bold text-lg text-gray-800 hover:text-amber-600 transition-colors duration-150 block mb-2">
                                <?= htmlspecialchars($row['nama']) ?>
                            </a>
                            <p class="text-gray-600 mb-4">
                                <?= "Rp " . number_format($row['harga'], 0, ',', '.') ?>
                            </p>
                        </div>

                        <!-- Tombol Hapus dari Favorit -->
                        <form action="favorit.php" method="POST" class="mt-auto">
                            <input type="hidden" name="produk_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="hapus"
                                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 cursor-pointer">
                                Hapus dari Favorit
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- Jika belum ada produk favorit -->
            <div class="text-center py-20 text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.8" stroke="currentColor" class="w-16 h-16 mx-auto mb-4 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M5.25 5.25v13.5L12 15l6.75 3.75V5.25A2.25 2.25 0 0016.5 3h-9A2.25 2.25 0 005.25 5.25z" />
                </svg>
                <p class="text-xl font-medium">Belum ada produk yang difavoritkan.</p>
                <a href="../homepage.php"
                    class="inline-block mt-6 bg-black text-white px-5 py-2 rounded-lg hover:bg-gray-800 transition">
                    Lihat Produk
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tombol Kembali (fixed di bawah layar) -->
    <div class="fixed bottom-6 left-6 z-[900]">
        <a href="<?= htmlspecialchars($backUrl) ?>"
            class="px-6 py-3 bg-gray-500 text-white rounded-lg shadow-md hover:bg-gray-600 hover:shadow-lg transition-all duration-200">
            ← Kembali
        </a>
    </div>


    <!-- Timeout  -->
    <script>
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0";
                setTimeout(() => flash.remove(), 1000);
            }
        }, 3000);
    </script>

</body>

</html>