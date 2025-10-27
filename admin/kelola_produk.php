<?php
session_start();
include "../koneksi.php";

// === Pastikan admin login ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Ambil semua kategori ===
$kategori = $koneksi->query("SELECT id, nama, slug FROM kategori ORDER BY id ASC");

// === Ambil semua produk (join kategori) ===
$produk = $koneksi->query("SELECT p.id, p.nama, p.harga, COALESCE(SUM(pu.stok), 0) AS stok, p.gambar, p.kode_produk, k.nama AS kategori
                            FROM produk p
                            LEFT JOIN kategori k ON p.kategori_id = k.id
                            LEFT JOIN produk_ukuran pu ON pu.produk_id = p.id
                            GROUP BY p.id
                            ORDER BY p.id ASC ");

// === Ambil asal url ===
$from = $_GET['from'] ?? '';
$nama = $_GET['kategori'] ?? '';

// === URL kembali dinamis ===
if (isset($_GET['from'])) {
    if ($_GET['from'] === "dashboard_admin") {
        $_SESSION['backUrl'] = "dashboard_admin.php";
    } elseif ($_GET['from'] === "kategori") {
        $_SESSION['backUrl'] = "kategori.php?&nama=" . urlencode($nama);
    }
}
$backUrl = $_SESSION['backUrl'] ?? "dashboard_admin.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Produk & Kategori</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-gray-100 px-8 py-10">

    <!-- Container utama -->
    <div class="max-w-8xl mx-auto">

        <!-- Tombol kembali kiri atas -->
        <div class="flex justify-start mb-6">
            <a href="<?= htmlspecialchars($backUrl) ?>"
                class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-800 shadow-md transition duration-200">
                ← Kembali
            </a>
        </div>

        <!-- Judul -->
        <h1 class="text-4xl font-bold text-gray-800 mb-8 tracking-wide">🛍️ Kelola Produk & Kategori</h1>

        <!-- Flash message -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div id="flash" class="fixed top-0 right-0 left-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md px-4 py-3 mb-6 text-center font-medium shadow-sm">
                <?= htmlspecialchars($_SESSION['flash']) ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- ===================== KATEGORI ===================== -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 mb-10">
            <div class="p-4 border-b bg-gray-800 text-white flex justify-between items-center">
                <h2 class="text-lg font-semibold">📂 Daftar Kategori</h2>
                <a href="tambah_kategori.php?"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">
                    ➕ Tambah Kategori
                </a>
            </div>

            <!-- Form -->
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-sm border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-center">No</th>
                            <th class="py-3 px-4 text-center">ID</th>
                            <th class="py-3 px-4">Nama Kategori</th>
                            <th class="py-3 px-4">Slug</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 text-sm">
                        <?php
                        $no = 1;
                        if ($kategori->num_rows > 0): ?>
                            <?php while ($row = $kategori->fetch_assoc()): ?>
                                <tr class="odd:bg-gray-50 even:bg-gray-100 hover:bg-gray-50 transition duration-200">
                                    <td class="py-3 px-4 text-center font-semibold"><?= htmlspecialchars($no++) ?></td>
                                    <td class="py-3 px-4 text-center font-semibold"><?= htmlspecialchars($row['id']) ?></td>
                                    <td class="py-3 px-4 text-center"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="py-3 px-4 text-gray-600 text-center"><?= htmlspecialchars($row['slug']) ?></td>

                                    <!-- Tombol aksi -->
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <a href="edit_kategori.php?id=<?= $row['id'] ?>"
                                                class="px-4 py-1.5 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                                                Edit
                                            </a>
                                            <form action="hapus_kategori.php" method="POST" onsubmit="return confirmDeleteKategori(event, <?= $row['id'] ?>)" class="inline">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit"
                                                    class="px-4 py-1.5 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition shadow-sm cursor-pointer">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-4 text-center text-gray-500 italic">Belum ada kategori.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================== PRODUK ===================== -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200">
            <div class="p-4 border-b bg-gray-800 text-white flex justify-between items-center">
                <h2 class="text-lg font-semibold">📦 Daftar Produk</h2>
                <a href="tambah_produk.php?from=kelola_produk&source=<?= urlencode($from) ?>"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">
                    ➕ Tambah Produk
                </a>
            </div>

            <!-- Form -->
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-sm border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-center">No</th>
                            <th class="py-3 px-4 text-center">ID</th>
                            <th class="py-3 px-4 text-center">Kode Produk</th>
                            <th class="py-3 px-4">Nama Produk</th>
                            <th class="py-3 px-4 text-center">Kategori</th>
                            <th class="py-3 px-4 text-center">Harga</th>
                            <th class="py-3 px-4 text-center">Stok</th>
                            <th class="py-3 px-4 text-center">Gambar</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 text-sm">
                        <?php
                        $no = 1;
                        if ($produk->num_rows > 0): ?>
                            <?php while ($row = $produk->fetch_assoc()): ?>
                                <tr class="odd:bg-gray-50 even:bg-gray-100 hover:bg-gray-50 transition duration-200">
                                    <td class="py-3 px-4 text-center font-semibold"><?= htmlspecialchars($no++) ?></td>
                                    <td class="py-3 px-4 text-center font-semibold"><?= htmlspecialchars($row['id']) ?></td>
                                    <td class="py-3 px-4 text-center font-semibold"><?= htmlspecialchars($row['kode_produk']) ?></td>
                                    <td class="py-3 px-4 text-center font-medium"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="py-3 px-4 text-center text-gray-700"><?= htmlspecialchars($row['kategori'] ?? '-') ?></td>
                                    <td class="py-3 px-4 text-center text-green-600 font-semibold">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-4 text-center text-gray-700"><?= $row['stok'] ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <img src="../img/<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama']) ?>"
                                            class="w-16 h-16 object-contain mx-auto rounded-md border">
                                    </td>

                                    <!-- Tombol aksi -->
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <?php if ($from === "dashboard_admin"): ?>
                                                <a href="edit_produk.php?id=<?= $row['id'] ?>&from=kelola_produk&from=<?= urlencode($from) ?>"
                                                    class="px-4 py-1.5 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                                                    Edit
                                                </a>
                                                <!-- Tombol Hapus -->
                                                <form action="hapus_produk.php" method="POST" onsubmit="return confirmDeleteProduk(event, <?= $row['id'] ?>)" class="inline">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="from" value="kelola_produk">
                                                    <button type="submit"
                                                        class="px-4 py-1.5 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition shadow-sm cursor-pointer">
                                                        Hapus
                                                    </button>
                                                </form>

                                            <?php elseif ($from !== "dashboard_admin"): ?>
                                                <a href="edit_produk.php?id=<?= $row['id'] ?>&from=kelola_produk&<?= urlencode($from) ?>=<?= urlencode($nama) ?>"
                                                    class="px-4 py-1.5 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                                                    Edit
                                                </a>
                                                <form action="hapus_produk.php" method="POST" onsubmit="return confirmDeleteProduk(event, <?= $row['id'] ?>)" class="inline">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="from" value="kelola_produk">
                                                    <button type="submit"
                                                        class="px-4 py-1.5 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition shadow-sm cursor-pointer">
                                                        Hapus
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500 italic">Belum ada produk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>

        // === Timeout flash message ===
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0";
                setTimeout(() => flash.remove(), 1000);
            }
        }, 3000);

        // Alert style
        function confirmDeleteProduk(e, id) {
            e.preventDefault();

            Swal.fire({
                title: 'Hapus Produk?',
                text: "Apakah kamu yakin ingin menghapus produk ini? Proses ini tidak bisa diurungkan.",
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

        function confirmDeleteKategori(e, id) {
            e.preventDefault();

            Swal.fire({
                title: 'Hapus Kategori?',
                text: "Apakah kamu yakin ingin menghapus kategori ini? Proses ini tidak bisa diurungkan.",
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