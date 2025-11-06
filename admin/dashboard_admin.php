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


    <!-- Kotak abu -->
    <div class="w-full h-7 bg-gray-200 px-0"></div>
    <!-- Navbar -->
    <nav class="px-1 lg:px-8 flex flex-row items-center justify-center gap-2 lg:gap-4">

        <!-- Header -->
        <header class="flex items-center py-4 relative">

            <!-- Checkbox trigger -->
            <input type="checkbox" id="menu-toggle" class="hidden peer" />

            <!-- Tombol Hamburger -->
            <label for="menu-toggle"
                class="h-[30px] w-[30px] cursor-pointer flex flex-col items-center justify-center gap-[4px] lg:gap-[5px] transition-all duration-300 peer-checked:opacity-0 peer-checked:pointer-events-none z-50">
                <span class="block h-[2px] w-[20px] lg:h-[3px] lg:w-[30px] bg-black transition-all duration-300"></span>
                <span class="block h-[2px] w-[20px] lg:h-[3px] lg:w-[30px] bg-black transition-all duration-300"></span>
                <span class="block h-[2px] w-[20px] lg:h-[3px] lg:w-[30px] bg-black transition-all duration-300"></span>
            </label>

            <!-- Judul -->
            <h1 class="hidden sm:block ml-4 mr-2 text-sm lg:text-4xl font-lobster">BRIMOB SPORT</h1>

            <!-- Triger -->
            <label for="menu-toggle" class="fixed inset-0 bg-transparent hidden peer-checked:block z-30 transition-opacity duration-800 left-64"></label>

            <!-- Sidebar BRIMOB SPORT -->
            <div id="sidebar"
                class="fixed top-0 left-0 h-screen w-64 bg-white border-r border-gray-200 shadow-[4px_0_15px_rgba(0,0,0,0.05)] transition-transform duration-300 -translate-x-full peer-checked:translate-x-0 z-20 flex flex-col font-inter">

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
            <span class="hidden absolute inset-y-0 left-0 sm:flex items-center pl-[2px] lg:pl-3 text-gray-400 transition-all duration-300 group-focus-within:text-black">
                <svg class="w-3 h-3  lg:w-5 lg:h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z" />
                </svg>
            </span>

            <!-- Input -->
            <input type="text" name="q" placeholder="Cari produk keren di BRIMOB SPORT..."
                class="h-6 rounded-sm w-full pl-[2px] sm:pl-5 lg:pl-10 pr-[20px] lg:pr-20 leading-[1.1rem] lg:leading-[1.25rem] lg:h-10 border border-gray-400 lg:rounded-lg bg-gradient-to-r from-gray-50 to-white text-gray-800 placeholder-gray-400 placeholder:py-1
                focus:outline-none focus:border-black focus:ring-2 focus:ring-gray-700 focus:shadow-[0_0_12px_rgba(0,0,0,0.15)] transition-all duration-300 ease-in-out 
                lg:text-base placeholder:text-xs sm:placeholder:text-sm lg:placeholder:text-base shadow-sm hover:shadow-[0_0_10px_rgba(0,0,0,0.1)]" />

            <!-- Tombol -->
            <button
                type="submit"
                class="absolute right-0 top-0 sm:top-[3.5px] lg:text-[16px] lg:top-0 h-6 sm:h-[17.5px] lg:h-10 px-[5px] sm:px-[7px] lg:px-4 bg-black text-white text-[8.5px] sm:text-[10px] rounded-r-sm lg:rounded-r-lg flex items-center justify-center hover:bg-gray-900 active:scale-[0.97] font-medium transition-all duration-200 ease-in-out cursor-pointer shadow-[0_1px_2px_rgba(0,0,0,0.2)] hover:shadow-[0_2px_4px_rgba(0,0,0,0.25)]">
                Search
            </button>
        </form>

        <!-- Validasi -->
        <?php if (isset($_SESSION['admin'])): ?>
            <div class="w-[2px] h-5 lg:w-1 lg:h-10 bg-gray-300"></div>
            <?php if (!empty($_SESSION['admin']['foto'])): ?>
                <a href="edit_profil_admin.php?from=dashboard_admin" class="flex items-center gap-4 text-2xl">
                    <img src="../<?= htmlspecialchars($_SESSION['admin']['foto']) ?>"
                        alt="Foto Profil"
                        class="size-6 sm:size-8 lg:size-10 rounded-full object-cover">
                </a>

            <?php else: ?>
                <a href="edit_profil_admin.php?from=dashboard_admin" class="flex items-center justify-center sm:justify-start gap-4 text-2xl">
                    <div
                        class="flex items-center justify-center w-8 h-8 sm:w-9 sm:h-9 lg:w-10 lg:h-10 rounded-full bg-gray-200 border border-gray-300">
                        <i class="fas fa-user text-gray-600 text-[18px] sm:text-[20px] lg:text-[22px]"></i>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Logout Button -->
            <button class="flex items-center justify-center cursor-pointer" id="openModal" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 lg:size-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
            </button>
        <?php endif; ?>
    </nav>

    <!-- Garis Hr -->
    <hr class=" border-gray-500 mb-8">

    <!-- Banner -->
    <main class="px-8 lg:px-30">
        <div class="w-full h-full flex flex-col-reverse lg:flex-row lg:px-10 lg:items-center lg:justify-center lg:gap-2 shadow-[0_0_10px_rgba(0,0,0,0.3)] rounded-2xl">
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
                <div class="p-2 w-full lg:w-1/2">
                    <!-- Judul -->
                    <h1 class="font-bold text-2xl text-center lg:text-left lg:text-4xl">
                        <?= htmlspecialchars($banner['nama']) ?> - Lebih Panjang, Lebih Cepat, Lebih Jauh
                    </h1>
                    <br>
                    <img src="../img/<?= htmlspecialchars($banner['gambar']) ?>"
                        alt="<?= htmlspecialchars($row['nama']) ?>"
                        class="block lg:hidden w-full max-w-md object-contain mx-auto mb-4">

                    <!--Gunakan teks_iklan kalau ada, kalau kosong pakai fallback -->
                    <p class="text-justify font-inter text-black mt-3 text-xs lg:text-lg">
                        <?= !empty($banner['teks_banner'])
                            ? htmlspecialchars($banner['teks_banner'])
                            : htmlspecialchars($banner['deskripsi']) ?>
                    </p>
                    <br>
                    <div class="w-full h-max flex flex-wrap items-center gap-1 sm:gap-4 p-2 sm:p-3">
                        <!-- Berat -->
                        <p class="font-inter text-center text-[10px] sm:text-xs lg:text-lg leading-tight">
                            ⏱️ Berat ringan:<br>120 gram
                        </p>

                        <!-- Kategori -->
                        <div class="flex items-center text-[10px] sm:text-xs lg:text-lg leading-tight">
                            <p class="font-inter text-left text-[10px] sm:text-xs lg:text-lg leading-tight">
                                ⏱️ Kategori:<br>Asics Running Shoes
                        </div>
                        <!-- Tombol -->
                        <a href="../produk/detail_produk.php?id=<?= urlencode($row['id']) ?>&from=<?= urlencode('dashboard_admin') ?>"
                            class="inline-flex ml-auto bg-black rounded-md sm:rounded-lg px-2 sm:px-3 py-[4px] sm:py-2 cursor-pointer 
                                hover:bg-gray-800 shadow-md hover:shadow-lg hover:scale-105 transition-transform duration-200 ease-in-out">
                            <div class="flex items-center justify-center lg:h-10 h-full w-full gap-1 sm:gap-2">
                                <p class="font-inter font-bold text-[10px] sm:text-xs lg:text-[16px] text-white">View Product</p>
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

                <div class="hidden lg:flex w-full lg:w-1/2 h-auto items-center justify-center">
                    <img src="../img/<?= htmlspecialchars($banner['gambar']) ?>" class='w-2xl rounded-xl'>
                </div>
            <?php } ?>
        </div>

        <br>

        <!-- Kategori -->
        <div>
            <h1 class="font-bold text-2xl lg:text-3xl font-inter">Categories</h1>
            <br>
            <div class="grid grid-cols-1 gap-5 lg:flex lg:flex-row lg:gap-10">
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
            <div class="grid grid-cols-3 gap-3 lg:grid-cols-4 lg:gap-10">
                <?php
                $result = mysqli_query($koneksi, "SELECT id, nama, gambar, harga FROM produk WHERE kategori_id IN (1,2,3,4)");
                while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="w-auto sm:w-[180px] lg:px-2 py-4 h-auto lg:w-auto rounded-2xl flex flex-col justify-between items-center shadow-[0_0_10px_rgba(0,0,0,0.3)] hover:scale-105 transition-transform duration-200 ease-in-out cursor-pointer">
                        <a href="../produk/detail_produk.php?id=<?= $row['id'] ?>&from=dashboard_admin" class="flex flex-col justify-between items-center">

                            <!-- Gambar -->
                            <img src="../img/<?= $row['gambar'] ?>"
                                alt=""
                                class="h-20 w-40 px-2 lg:h-40 object-contain mb-4">

                            <!-- Nama produk -->
                            <p class="font-bold px-[8px] text-[10px] text-center lg:text-lg lg:text-center min-h-[60px]">
                                <?= $row['nama'] ?>
                            </p>

                            <!-- Harga -->
                            <p class="text-[8px] font-bold lg:text-lg text-gray-600">
                                <?= "Rp " . number_format($row['harga'], 2, ',', '.') ?>
                            </p>
                        </a>

                        <!-- Valiadasi -->
                        <?php if (isset($_SESSION['admin'])): ?>
                            <div class="cursor-pointer flex items-start justify-between lg:justify-evenly gap-1 sm:gap-2 w-full mt-2 sm:mt-3 lg:px-2 px-1">
                                <a href="edit_produk.php?id=<?= $row['id'] ?>&from=dashboard_admin&source=admin"
                                    class="w-12 py-[6px] px-2 h-6 sm:w-16 sm:h-10 text-center text-[7px] sm:text-[16px] font-medium bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">Edit</a>
                                <a href="hapus_produk.php?id=<?= $row['id'] ?>&from=dashboard_admin"
                                    onclick="return confirm('Yakin ingin menghapus produk ini?')"
                                    class="w-12 py-[6px] px-2 h-6 sm:w-16 sm:h-10 text-center text-[7px] sm:text-[16px] font-medium bg-red-500 text-white rounded hover:bg-red-600 text-sm">Hapus</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tombol tambah -->
                <?php } ?>
                <a href="tambah_produk.php?from=dashboard_admin"
                    class="w-auto sm:w-[180px] lg:px-2 py-4 h-auto lg:w-auto rounded-2xl flex flex-col justify-between items-center shadow-[0_0_10px_rgba(0,0,0,0.3)] hover:scale-105 transition-transform duration-200 ease-in-out cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-20 lg:size-60">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="text-sm text-center lg:text-2xl font-bold">Tambah Produk</span>
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