<?php
session_start();
include "../koneksi.php";

// === Validasi admin ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Ambil semua produk untuk dropdown ===
$produk = $koneksi->query("SELECT id, nama, is_banner FROM produk ORDER BY nama ASC");

// === Proses submit form ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['hapus_banner'])) {
        // === Nonaktifkan semua banner tanpa hapus produk ===
        $koneksi->query("UPDATE produk SET is_banner = 0");
        $_SESSION['flash'] = "🧹 Banner berhasil dihapus.";
    } else {
        $idBaru = intval($_POST['produk_id']);
        // === Reset semua dan aktifkan yang baru ===
        $koneksi->query("UPDATE produk SET is_banner = 0");
        $stmt = $koneksi->prepare("UPDATE produk SET is_banner = 1 WHERE id = ?");
        $stmt->bind_param("i", $idBaru);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = "✅ Produk banner berhasil diganti!";
    }

    // === Fallback === 
    header("Location: dashboard_admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Produk Banner</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="bg-gradient-to-br from-gray-100 via-gray-200 to-gray-100 min-h-screen flex items-center justify-center">

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash"
            class="fixed top-6 left-1/2 -translate-x-1/2 bg-emerald-100 border border-emerald-400 text-emerald-700 font-medium px-6 py-3 rounded-xl shadow-md z-50 animate-fade-in">
            <?= htmlspecialchars($_SESSION['flash']);
            unset($_SESSION['flash']); ?>
        </div>
    <?php endif; ?>

    <!-- Card Form -->
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-[420px] border border-gray-200">
        <h1 class="text-3xl font-bold mb-6 text-center text-gray-800 tracking-wide">
            Kelola Produk Banner 🔁
        </h1>

        <form method="post" class="space-y-5">
            <div>
                <label class="block mb-2 font-semibold text-gray-700">
                    Pilih Produk untuk Ditampilkan sebagai Banner
                </label>
                <div class="relative">

                <!-- Dropdown -->
                    <select name="produk_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-gray-800 focus:ring-2 focus:ring-black focus:border-black bg-white appearance-none cursor-pointer shadow-sm transition-all duration-150 ease-in-out">
                        <?php while ($p = $produk->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['is_banner'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <svg class="w-5 h-5 text-gray-500 absolute right-3 top-3 pointer-events-none"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Tombol -->
            <div class="flex justify-between items-center pt-2">
                <!-- Tombol hapus banner -->
                <button type="submit" name="hapus_banner"
                    class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 active:scale-95 shadow transition">
                    ❌ Hapus Banner
                </button>

                <div class="flex gap-3">

                    <!-- Tombol batal -->
                    <a href="dashboard_admin.php"
                        class="px-4 py-2 bg-gray-400 text-white rounded-lg font-medium hover:bg-gray-500 active:scale-95 shadow transition">
                        Batal
                    </a>

                    <!-- Tombol simpan -->
                    <button type="submit"
                        class="px-4 py-2 bg-black text-white rounded-lg font-medium hover:bg-gray-800 active:scale-95 shadow transition">
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>

        // === Timeout Flash Message ===  
        const flash = document.getElementById('flash');
        if (flash) {
            setTimeout(() => {
                flash.style.opacity = '0';
                flash.style.transform = 'translateY(-10px)';
                setTimeout(() => flash.remove(), 700);
            }, 2500);
        }
    </script>

</body>

</html>