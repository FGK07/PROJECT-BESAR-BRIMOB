<?php
session_start();
include "../koneksi.php";

// Cek apakah admin sudah login
if (!isset($_SESSION['admin']) || $_SESSION['admin']['role'] !== 'admin') {
    // Kalau belum login atau role bukan admin → tendang ke login
    header("Location: login_admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <!-- flash message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md px-4 py-3 mb-6 text-center font-medium shadow-sm w-full">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Kotak abu -->
    <div class="w-full h-7 bg-gray-200 px-0"></div>
    <!-- Navbar -->
    <nav class="px-8 flex flex-row items-center justify-center gap-4">

        <!-- Header -->
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

            <!-- Triger -->
            <label for="menu-toggle" class="fixed inset-0 bg-transparent hidden peer-checked:block z-30 transition-opacity duration-800 left-64"></label>

            <!-- Sidebar BRIMOB SPORT -->
            <div id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-white border-r border-gray-200 shadow-[4px_0_15px_rgba(0,0,0,0.05)] transition-transform duration-300 -translate-x-full peer-checked:translate-x-0 z-20 flex flex-col font-inter">

                <!-- Logo -->
                <div class="px-6 py-8 border-b border-gray-200 text-center">
                    <h1 class="text-4xl font-lobster text-gray-800 tracking-wide">BRIMOB <span class="text-blue-600">ADMIN</span></h1>
                    <p class="text-xs text-gray-500 mt-1 font-medium">Admin Dashboard</p>
                </div>

                <!-- Menu -->
                <ul class="flex-1 px-4 py-6 space-y-3">
                    <li>
                        <a href="kelola_user.php?from=dashboard_admin"
                            class="flex items-center gap-4 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition-all duration-200 font-semibold shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-users text-lg text-gray-600 group-hover:text-blue-700"></i>
                            Kelola User
                        </a>
                    </li>

                    <li>
                        <a href="kelola_transaksi.php?from=dashboard_admin"
                            class="flex items-center gap-4 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition-all duration-200 font-semibold shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-receipt text-lg text-gray-600 group-hover:text-blue-700"></i>
                            Kelola Transaksi
                        </a>
                    </li>

                    <li>
                        <a href="kelola_produk.php?from=dashboard_admin"
                            class="flex items-center gap-4 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition-all duration-200 font-semibold shadow-sm hover:shadow-md">
                            <i class="fa-solid fa-box-open text-lg text-gray-600 group-hover:text-blue-700"></i>
                            Kelola Produk
                        </a>
                    </li>
                </ul>

                <!-- Footer -->
                <div class="border-t border-gray-200 py-3 text-center text-xs text-gray-500">
                    © <?= date('Y') ?> BRIMOB SPORT • Developed by
                    <span class="text-blue-600 font-semibold">Kuncoro</span> &
                    <span class="text-blue-600 font-semibold">Belgi</span>
                </div>
            </div>
        </header>

        <!-- Modal -->
        <div id="modal" class="fixed inset-0 hidden items-center justify-center z-50 w-full">
            <div class="bg-white p-6 rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.3)] w-80 text-center">
                <h1 class="text-lg font-semibold mb-4">Apakah anda yakin ingin logout?</h1>
                <div class="flex justify-center gap-4">
                    <form action="../user/logout.php?role=admin" method="post">
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

        <!-- Search -->
        <form action="../produk/search.php" method="get" class="relative flex-1 group">
            <!-- Ikon search -->
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 transition-all duration-300 group-focus-within:text-black">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z" />
                </svg>
            </span>

            <!-- Input Search-->
            <input type="text" name="q" placeholder="Cari produk keren di BRIMOB SPORT..."
                class="w-full pl-10 pr-20 py-2 h-10 border border-gray-400 rounded-lg bg-gradient-to-r from-gray-50 to-white text-gray-800 placeholder-gray-400
                        focus:outline-none focus:border-black focus:ring-2 focus:ring-gray-700 focus:shadow-[0_0_12px_rgba(0,0,0,0.15)] transition-all duration-300 ease-in-out shadow-sm
                        hover:shadow-[0_0_10px_rgba(0,0,0,0.1)]" />

            <!-- Tombol -->
            <button type="submit"
                class="absolute right-0 top-0 h-10 px-4 bg-black text-white rounded-r-lg hover:bg-gray-900 active:scale-[0.97] font-medium transition-all duration-200 ease-in-out cursor-pointer
                        shadow-[0_2px_6px_rgba(0,0,0,0.2)] hover:shadow-[0_2px_10px_rgba(0,0,0,0.25)]">
                Search
            </button>
        </form>

        <!-- Logo Search -->
        <button class="relative">
            <svg class="w-10 h-10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" id="Shopping-Cart--Streamline-Lucide">
                <path d="M7 21a1 1 0 1 0 2 0 1 1 0 1 0 -2 0" stroke-width="2"></path>
                <path d="M18 21a1 1 0 1 0 2 0 1 1 0 1 0 -2 0" stroke-width="2"></path>
                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95 -1.57l1.65 -7.43H5.12" stroke-width="2"></path>
            </svg>
        </button>

        <!-- Validasi -->
        <?php if (isset($_SESSION['admin'])): ?>
            <?php if (!empty($_SESSION['admin']['foto'])): ?>
                <a href="edit_profil_admin.php?from=dashboard_admin" class="flex items-center gap-4 text-2xl">
                    <img src="../<?= htmlspecialchars($_SESSION['admin']['foto']) ?>"
                        alt="Foto Profil"
                        class="size-10 rounded-full">
                </a>

            <?php else: ?>
                <a href="edit_profil_admin.php?from=dashboard_admin" class="flex items-center gap-4 text-2xl">
                    <i class="fas fa-user size-10"></i>
                </a>
            <?php endif; ?>

            <!-- Logout Button -->
            <button class="flex items-center justify-center cursor-pointer" id="openModal" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
            </button>
        <?php endif; ?>
    </nav>

    <!-- Garis Hr -->
    <hr class=" border-gray-500 mb-8">

    <!-- Banner -->
    <main class="px-30">
        <div class=" w-full h-[350] flex px-10 items-center justify-center gap-2 shadow-[0_0_10px_rgba(0,0,0,0.3)] rounded-2xl">
            <?php
            // Ambil produk banner aktif
            $result = mysqli_query($koneksi, "SELECT id, nama, deskripsi, teks_banner, gambar FROM produk WHERE is_banner = 1 LIMIT 1");
            $banner = mysqli_fetch_assoc($result);
            
            // Validasi
            if (!$banner) {
                echo "<div class='w-full text-center py-10 text-gray-600 font-medium text-lg'>
                🚫 Belum ada produk banner yang aktif.<br>
                <a href='ganti_banner.php' class='text-blue-600 hover:underline'>
                Pilih produk untuk dijadikan banner
                </a>
                </div>";
            } else { ?>
                <div class="p-2 w-1/2 ">
                    <!-- Judul -->
                    <h1 class="font-inter font-bold text-4xl text-gray-900">
                        <?= htmlspecialchars($banner['nama']) ?> - Lebih Panjang, Lebih Cepat, Lebih Jauh
                    </h1>
                    <br>

                    <!--Gunakan teks_iklan kalau ada, kalau kosong pakai fallback -->
                    <p class="text-justify font-inter text-black mt-3 text-lg">
                        <?= !empty($banner['teks_banner'])
                            ? htmlspecialchars($banner['teks_banner'])
                            : htmlspecialchars($banner['deskripsi']) ?>
                    </p>
                    <br>
                    <!-- Deskripsi dari database -->
                    <br>
                    <div class="w-full h-max flex gap-4">
                        <p class="font-inter text-center text-lg">⏱️Berat ringan: <br> 120 gram</p>
                        <div class="flex">
                            <i>👟</i>
                            <p class="font-inter text-lg">Kategori: <br> Asics Running Shoes</p>
                        </div>
                        <a class="inline-flex ml-auto bg-black rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-800 shadow-md hover:shadow-lg hover:scale-105 transition-transform duration-200 ease-in-out" href="../produk/detail_produk.php?id=<?= urlencode($row['id']) ?>&from=<?= urlencode('dashboard_admin') ?>">
                            <div class="flex items-center justify-center h-auto w-full gap-4">
                                <p class="font-inter font-bold text-white">View Product</p>
                                <img src="../img/PlayCircle.png">
                            </div>
                        </a>
                    </div>

                    <!-- Tombol Edit, Hapus, Ganti -->
                    <div class="flex gap-2 mt-4 justify-end">
                        <a href="edit_produk.php?id=<?= $banner['id'] ?>&from=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                            Edit
                        </a>
                        <a href="ganti_banner.php"
                            class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 text-sm">
                            Ganti
                        </a>
                    </div>
                </div>

                <div class="w-1/2 h-auto flex items-center justify-center">
                    <img src="../img/<?= htmlspecialchars($banner['gambar']) ?>" class='w-2xl rounded-xl'>
                </div>
            <?php } ?>
        </div>

        <br>

        <!-- Kategori -->
        <div>
            <h1 class="font-bold text-3xl font-inter">Categories</h1>
            <br>
            <div class="flex flex-row gap-10">
                <?php
                $result = $koneksi->query("SELECT nama, slug FROM kategori");
                while ($row = $result->fetch_assoc()): ?>
                    <a href="../admin/kategori.php?nama=<?= urlencode($row['slug']) ?>"
                        class="px-2 h-12 flex flex-1 items-center justify-center rounded-2xl text-white font-semibold text-2xl text-center bg-gray-400 cursor-pointer hover:bg-gray-500 shadow-md hover:shadow-lg hover:scale-105 transition-transform duration-200 ease-in-out">
                        <?= htmlspecialchars($row['nama']) ?>
                    </a>
                <?php endwhile; ?>
            </div>

            <br>

            <!-- Produk -->
            <div class="grid grid-cols-4 gap-10">
                <?php
                $result = mysqli_query($koneksi, "SELECT id, nama, gambar, harga FROM produk WHERE kategori_id IN (1,2,3,4)");
                while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="px-2 py-4 h-[320px] w-2xs rounded-2xl flex flex-col justify-between items-center shadow-[0_0_10px_rgba(0,0,0,0.3)] hover:scale-105 transition-transform duration-200 ease-in-out cursor-pointer">
                        <a href="../produk/detail_produk.php?id=<?= $row['id'] ?>&from=dashboard_admin" class="flex flex-col justify-between items-center">

                            <!-- Gambar -->
                            <img src="../img/<?= $row['gambar'] ?>"
                                alt=""
                                class="w-40 h-40 object-contain mb-4">

                            <!-- Nama produk -->
                            <p class="font-bold text-lg text-center min-h-[60px]">
                                <?= $row['nama'] ?>
                            </p>

                            <!-- Harga -->
                            <p class="text-lg text-gray-600">
                                <?= "Rp " . number_format($row['harga'], 2, ',', '.') ?>
                            </p>
                        </a>

                        <!-- Valiadasi -->
                        <?php if (isset($_SESSION['admin'])): ?>
                            <div class="flex gap-2 mt-2">
                                <a href="edit_produk.php?id=<?= $row['id'] ?>&from=dashboard_admin&source=admin"
                                    class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">Edit</a>
                                <a href="hapus_produk.php?id=<?= $row['id'] ?>&from=dashboard_admin"
                                    onclick="return confirm('Yakin ingin menghapus produk ini?')"
                                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">Hapus</a>
                            </div>
                        <?php endif; ?>
                    </div>

                <!-- Tombol tambah -->
                <?php } ?>
                <a href="tambah_produk.php?from=dashboard_admin"
                    class="h-[320px] w-2xs rounded-2xl flex flex-col justify-center items-center shadow-[0_0_10px_rgba(0,0,0,0.3)] hover:scale-105 transition-transform duration-200 ease-in-out cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-60">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="text-2xl font-bold">Tambah Produk</span>
                </a>
            </div>
        </div>

        <br>
        <br>

    </main>

    <script>
        // Timeout flash message
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0"; // mulai fade out
                setTimeout(() => flash.remove(), 1000); // hapus setelah 1 detik
            }
        }, 3000); // tampil 3 detik dulu

        // Dom logout 
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

        // Placeholder search
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