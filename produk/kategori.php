<?php
include "../koneksi.php";
session_start();

if (!isset($_GET['nama'])) {
    header("Location: ../homepage.php");
    exit;
}

$slug = $_GET['nama']; //ambil slug

// Ambil data kategori
$stmt = $koneksi->prepare("SELECT id, nama FROM kategori WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$res = $stmt->get_result();
$kategoriRow = $res->fetch_assoc();

if (!$kategoriRow) {
    header("Location: ../homepage.php");
    exit;
}
$kategoriId   = $kategoriRow['id'];
$kategoriNama = $kategoriRow['nama'];

// Ambil produk berdasarkan kategori
$stmt2 = $koneksi->prepare("SELECT id ,nama, harga, gambar FROM produk WHERE kategori_id = ?");
$stmt2->bind_param("i", $kategoriId);
$stmt2->execute();
$result2 = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kategori <?= htmlspecialchars($kategoriNama) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="">
    <!-- Tampilkan Notif (flash) -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md px-4 py-3 mb-6 text-center font-medium shadow-sm w-full">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="w-full h-7 bg-gray-200 px-0"></div>

    <nav class="px-8 flex flex-row items-center justify-center gap-4">

        <header class="flex items-center py-4 relative">
            <!-- Checkbox trigger -->
            <input type="checkbox" id="menu-toggle" class="hidden peer" />

            <!-- Tombol Hamburger -->
            <label for="menu-toggle" class="h-[30px] w-[30px] cursor-pointer flex flex-col items-center justify-center gap-[5px] transition-all duration-300 peer-checked:opacity-0 peer-checked:pointer-events-none z-50">
                <span class="block h-[3px] w-[30px] bg-black transition-all duration-300"></span>
                <span class="block h-[3px] w-[30px] bg-black transition-all duration-300"></span>
                <span class="block h-[3px] w-[30px] bg-black transition-all duration-300"></span>
            </label>

            <!-- Judul -->
            <h1 class="ml-4 text-4xl font-lobster">BRIMOB SPORT</h1>

            <label for="menu-toggle"
                class="fixed inset-0 bg-transparent hidden peer-checked:block z-30 transition-opacity duration-800 left-64"></label>

            <!-- Sidebar BRIMOB SPORT -->
            <div id="sidebar"
                class="fixed top-0 left-0 h-screen w-64 bg-white border-r border-gray-200 shadow-[4px_0_15px_rgba(0,0,0,0.05)] transition-transform duration-300 -translate-x-full peer-checked:translate-x-0 z-20 flex flex-col font-inter">
                <!-- Logo -->
                <div class="px-6 py-8 border-b border-gray-200 text-center">
                    <h1 class="text-4xl font-lobster text-gray-800 tracking-wide">BRIMOB <span class="text-blue-600">USER</span></h1>
                    <p class="text-xs text-gray-500 mt-1 font-medium">User Menu</p>
                </div>

                <!-- Menu -->
                <ul class="flex-1 px-4 py-6 space-y-3">
                    <li>
                        <a href="../user/daftar_favorit.php?from=kategori&kategori=<?= urlencode($slug) ?> "
                            class="flex items-center gap-4 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-blue-100hover:text-blue-700 transition-all duration-200 font-semibold shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-receipt text-lg text-gray-600 group-hover:text-blue-700"></i>
                            Favorit
                        </a>
                    </li>

                    <li>
                        <a href="../user/riwayat.php?from=kategori&kategori=<?= urlencode($slug) ?>"
                            class="flex items-center gap-4 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition-all duration-200 font-semibold shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-box-open text-lg text-gray-600 group-hover:text-blue-700"></i>
                            Riwayat Transaksi
                        </a>
                    </li>
                </ul>

                <!-- Footer -->
                <div class="border-t border-gray-200 py-3 text-center text-xs text-gray-500">
                    © <?= date('Y') ?> BRIMOB SPORT • Developed by
                    <span class="text-blue-600 font-semibold">Ferdian Egha Kuncoro</span> &
                    <span class="text-blue-600 font-semibold">Belgi Setiawan</span>
                </div>

            </div>
        </header>

        <div id="modal" class="fixed inset-0 hidden items-center justify-center z-50 w-full">

            <div class="bg-white p-6 rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.3)] w-80 text-center">
                <h1 class="text-lg font-semibold mb-4">Apakah anda yakin ingin logout?</h1>
                <div class="flex justify-center gap-4">
                    <form action="../user/logout.php?role=user" method="post">
                        <button type="submit"
                            class="px-4 py-2 bg-black text-white rounded hover:bg-gray-800 cursor-pointer">
                            Iya
                        </button>
                    </form>
                    <button type="button" id="closeModal"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 cursor-pointer">
                        Batal
                    </button>
                </div>
            </div>

        </div>

        <form action="search.php" method="get" class="relative flex-1 group">

            <!-- Ikon search -->
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 transition-all duration-300 group-focus-within:text-black">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z" />
                </svg>
            </span>

            <!-- Input -->
            <input type="text" name="q" placeholder="Cari produk keren di BRIMOB SPORT..."
                class="w-full pl-10 pr-20 py-2 h-10 border border-gray-400 rounded-lg bg-gradient-to-r from-gray-50 to-white text-gray-800 placeholder-gray-400 focus:outline-none focus:border-black focus:ring-2 focus:ring-gray-700 focus:shadow-[0_0_12px_rgba(0,0,0,0.15)] transition-all duration-300 ease-in-out shadow-sm hover:shadow-[0_0_10px_rgba(0,0,0,0.1)]" />

            <!-- Tombol -->
            <button type="submit"
                class="absolute right-0 top-0 h-10 px-4 bg-black text-white rounded-r-lg hover:bg-gray-900 active:scale-[0.97] font-medium transition-all duration-200 ease-in-out cursor-pointer shadow-[0_2px_6px_rgba(0,0,0,0.2)] hover:shadow-[0_2px_10px_rgba(0,0,0,0.25)]">
                Search
            </button>
        </form>

        <a class="relative" href="../user/keranjang.php?&from=kategori&kategori=<?= urlencode($slug) ?>">
            <svg class="w-10 h-10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" id="Shopping-Cart--Streamline-Lucide">
                <path d="M7 21a1 1 0 1 0 2 0 1 1 0 1 0 -2 0" stroke-width="2"></path>
                <path d="M18 21a1 1 0 1 0 2 0 1 1 0 1 0 -2 0" stroke-width="2"></path>
                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95 -1.57l1.65 -7.43H5.12" stroke-width="2"></path>
            </svg>
        </a>

        <?php if (isset($_SESSION['user'])): ?>
            <!-- Jika user login -->
            <?php if (!empty($_SESSION['user']['foto'])): ?>
                <a href="../user/edit_profil.php?from=kategori&kategori=<?= urlencode($_GET['nama']) ?>" class="flex items-center gap-4 text-2xl">
                    <img src="../<?= htmlspecialchars($_SESSION['user']['foto']) ?>"
                        alt="Foto User"
                        class="size-10 rounded-full object-cover">
                </a>

            <?php else: ?>
                <a href="../user/profil.php?from=kategori&kategori=<?= urlencode($_GET['nama']) ?>" class="flex items-center gap-4 text-2xl">
                    <i class="fas fa-user size-10"></i>
                </a>
            <?php endif; ?>

            <button class="flex items-center justify-center cursor-pointer" id="openModal" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
            </button>

        <?php else: ?>
            <!-- Jika belum login -->
            <form action="../user/login_user.php">
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($slug) ?>">
                <button type="submit"
                    class="px-4 py-2 border rounded-lg hover:bg-gray-300 cursor-pointer">Masuk</button>
            </form>
            <form action="../user/registrasi_user.php">
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($slug) ?>">
                <button type="submit"
                    class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 cursor-pointer">Daftar</button>
            </form>

        <?php endif; ?>

    </nav>

    <hr class=" border-gray-500 mb-8">

    <!-- Kategori Produk -->
    <main class="px-30">
        <div>
            <h1 class="text-3xl font-bold mb-6">Categories: <?= htmlspecialchars($kategoriNama) ?></h1>
            <br>
            <div class="flex flex-row gap-10">
                <?php
                $result = $koneksi->query("SELECT nama, slug FROM kategori");
                while ($row = $result->fetch_assoc()):
                    $active = (isset($_GET['nama']) && $_GET['nama'] === $row['slug'])
                        ? "bg-black"
                        : "bg-gray-400 hover:bg-gray-500";
                ?>
                    <a href="kategori.php?nama=<?= urlencode($row['slug']) ?>"
                        class="px-2 h-12 flex flex-1 items-center justify-center rounded-2xl text-white font-semibold text-2xl text-center cursor-pointer shadow-md hover:shadow-lg hover:scale-105 transition-transform duration-200 ease-in-out <?= $active ?>">
                        <?= htmlspecialchars($row['nama']) ?>
                    </a>
                <?php endwhile; ?>
            </div>

            <br>

            <div class="grid grid-cols-4 gap-10">
                <?php while ($row = $result2->fetch_assoc()):
                    $isFavorit = false;
                    if (isset($_SESSION['user'])) {
                        $user_id = $_SESSION['user']['id'];
                        $produk_id = $row['id'];
                        $cekFav = mysqli_query($koneksi, "SELECT 1 FROM favorit WHERE user_id = $user_id AND produk_id = $produk_id");
                        $isFavorit = mysqli_num_rows($cekFav) > 0;
                    } ?>
                    <div class="px-2 py-4 h-[350px] w-2xs rounded-2xl flex flex-col justify-between items-center shadow-[0_0_10px_rgba(0,0,0,0.3)] hover:scale-105 transition-transform duration-200 ease-in-out cursor-pointer">
                        <a href="detail_produk.php?id=<?= $row['id'] ?>&from=kategori&kategori=<?= urlencode($slug) ?>" class="flex flex-col justify-between items-center">

                            <!-- gambar -->
                            <img src="../img/<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama']) ?>"
                                class="w-40 h-40 object-contain mb-4">

                            <!-- nama -->
                            <p class="font-bold text-xl text-center min-h-[60px]"><?= htmlspecialchars($row['nama']) ?></p>

                            <!-- harga -->
                            <p class="text-lg text-gray-600"><?= "Rp " . number_format($row['harga'], 2, ',', '.') ?></p>

                        </a>
                        <div class="cursor-pointer flex items-start justify-evenly gap-2 w-full">
                            <a href="detail_produk.php?id=<?= htmlspecialchars($row['id']) ?>&from=kategori&kategori=<?= urlencode($slug) ?>"
                                class="px-4 py-2 w-16 h-10 text-center text-[15px] font-medium bg-black text-white rounded-lg hover:bg-gray-700 transition-all shadow-sm">
                                Beli
                            </a>
                            <!-- Tombol favorit -->
                            <form action="../user/favorit.php" method="POST">
                                <input type="hidden" name="produk_id" value="<?= htmlspecialchars($row['id']) ?>">

                                <?php if ($isFavorit): ?>
                                    <!-- Sudah difavoritkan -->
                                    <button type="submit" name="hapus"
                                        class="bookmarkBtn flex items-center justify-center gap-2 px-4 py-2 w-36 text-[15px] font-medium bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all shadow-sm cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                            class="bookmarkIcon w-[25px] h-[24px] align-middle relative top-[1px]">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M5.25 5.25v13.5L12 15l6.75 3.75V5.25A2.25 2.25 0 0016.5 3h-9A2.25 2.25 0 005.25 5.25z" />
                                        </svg>
                                        <span class="bookmarkText align-middle leading-none">Tersimpan</span>
                                    </button>
                                <?php else: ?>
                                    <!-- Belum difavoritkan -->
                                    <button type="submit" name="tambah"
                                        class="bookmarkBtn flex items-center justify-center gap-2 px-4 py-2 w-36 text-[15px] font-medium bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-all shadow-sm cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                            class="bookmarkIcon w-[25px] h-[24px] align-middle relative top-[1px]">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M5.25 5.25v13.5L12 15l6.75 3.75V5.25A2.25 2.25 0 0016.5 3h-9A2.25 2.25 0 005.25 5.25z" />
                                        </svg>
                                        <span class="bookmarkText align-middle leading-none">Favorit</span>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>

                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        </div>
        <div class="mt-8">
            <a href="../homepage.php" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600">← Kembali</a>
        </div>

    </main>

    <script>
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0"; // mulai fade out
                setTimeout(() => flash.remove(), 1000); // hapus setelah 1 detik
            }
        }, 3000); // tampil 3 detik dulu
        const openBtn = document.getElementById("openModal");
        const closeBtn = document.getElementById("closeModal");
        const modal = document.getElementById("modal");
        if (openBtn && closeBtn && modal) {

            openBtn.addEventListener("click", () => {
                modal.classList.remove("hidden");
                modal.classList.add("flex");
            });

            closeBtn.addEventListener("click", () => {
                modal.classList.add("hidden");
                modal.classList.remove("flex");
            });
        }
        const input = document.querySelector('input[name="q"]');
        const placeholders = [
            "Cari sepatu running...",
            "Cari energy gel favoritmu...",
            "Cari outfit sport...",
            "Cari produk keren di BRIMOB SPORT..."
        ];
        let i = 0;
        setInterval(() => {
            input.placeholder = placeholders[i];
            i = (i + 1) % placeholders.length;
        }, 2500);
    </script>

</body>

</html>