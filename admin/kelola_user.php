<?php
session_start();
include "../koneksi.php";

// === Simpan halaman asal (hanya dari GET) ===
if (isset($_GET['from'])) {
    switch ($_GET['from']) {
        case "dashboard_admin":
            $_SESSION['backUrl'] = "dashboard_admin.php";
            break;

        case "kategori":
            $kategori = urlencode($_GET['kategori'] ?? '');
            $_SESSION['backUrl'] = "kategori.php?nama=$kategori";
            break;

        default:
            $_SESSION['backUrl'] = "dashboard_admin.php";
    }
}

// === Simpan halaman ini agar detail_user bisa balik ke sini ===
$_SESSION['kelolaUserBack'] = "kelola_user.php?from=" . ($_GET['from'] ?? 'dashboard_admin') . (!empty($_GET['kategori']) ? "&kategori=" . urlencode($_GET['kategori']) : '');
$backUrl = $_SESSION['backUrl'] ?? "dashboard_admin.php";

// === Tambahan: Filter urutan ID user ===
$sort = $_GET['sort'] ?? 'desc';
$orderDirection = ($sort === 'asc') ? 'ASC' : 'DESC';

// === Query data user untuk filter ===
$stmt = mysqli_query($koneksi, " SELECT id, nama, oauth_uid, nomor_telepon, email, alamat, created_at FROM users WHERE role = 'user' ORDER BY id $orderDirection");

// === Hitung total baris untuk nomor urut ===
$totalRows = mysqli_num_rows($stmt);
$no = ($orderDirection === 'ASC') ? 1 : $totalRows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar User</title>
    <link rel="stylesheet" href="../src/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-gray-100 px-6 py-10">

    <!-- Flash message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-0 left-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md mr-auto py-3 mb-6 text-center font-medium shadow-sm w-full">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Tombol kembali -->
    <div class="mt-4 flex justify-start w-full max-w-7xl ">
        <a href="<?= htmlspecialchars($backUrl) ?>"
            class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-800 shadow-md transition">
            ← Kembali
        </a>
    </div>

    <!-- Judul Halaman -->
    <h1 class="text-4xl text-center font-bold text-gray-800 mb-8 tracking-wide">👥 Daftar Pengguna</h1>

    <!-- Container Tabel -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200 w-full max-w-8xl mx-auto">
        <div class="p-4 border-b bg-gray-800 text-white flex justify-between items-center">
            <h2 class="text-lg font-semibold">Tabel User Terdaftar</h2>

            <!-- === Dropdown ID disamping Role -->
            <div class="flex items-center gap-3">
                <form method="get" class="flex items-center gap-2">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? 'dashboard_admin') ?>">
                    <?php if (!empty($_GET['kategori'])): ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($_GET['kategori']) ?>">
                    <?php endif; ?>

                    <!-- Dropdown urutkan id -->
                    <label for="sort" class="text-sm text-white/80 font-medium">Urutkan ID:</label>
                    <div class="relative">
                        <select name="sort" id="sort" onchange="this.form.submit()"
                            class="text-center appearance-none bg-gray-700 border border-gray-500 text-white text-sm rounded-lg focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 px-3 py-1.5 pr-8 transition cursor-pointer hover:border-gray-400 shadow-sm">
                            <option value="desc" <?= ($sort === 'desc') ? 'selected' : '' ?>>Terbaru</option>
                            <option value="asc" <?= ($sort === 'asc') ? 'selected' : '' ?>>Terlama</option>
                        </select>
                        <svg class="w-4 h-4 text-gray-300 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <?php if ($sort !== 'desc'): ?>
                        <a href="kelola_user.php" class="text-sm text-gray-300 hover:text-white underline ml-1 transition">Reset</a>
                    <?php endif; ?>
                </form>

                <span class="text-sm opacity-80">Role: User</span>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-100 text-gray-700 uppercase text-sm">
                    <tr class="border-b border-gray-200">
                        <th class="py-3 px-4 text-center">No</th>
                        <th class="py-3 px-4">Nama</th>
                        <th class="py-3 px-4">Oauth UID</th>
                        <th class="py-3 px-4 text-center">No. Telp</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Alamat</th>
                        <th class="py-3 px-4 text-center">Created At</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="text-gray-800 text-sm">
                    <?php while ($row = mysqli_fetch_assoc($stmt)) { ?>

                        <!-- Kolom data -->
                        <tr class="odd:bg-gray-50 even:bg-gray-100 hover:bg-gray-50 transition duration-200">
                            <td class="py-3 px-4 text-center font-semibold text-gray-700"><?= $no ?></td>
                            <td class="py-3 px-4 font-medium text-center"><?= htmlspecialchars($row['nama']) ?></td>
                            <td class="py-3 px-4 text-gray-600 text-center"><?= htmlspecialchars($row['oauth_uid']) ?></td>
                            <td class="py-3 px-4 text-center"><?= htmlspecialchars($row['nomor_telepon']) ?></td>
                            <td class="py-3 px-4 text-center"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="py-3 px-4 text-center"><?= htmlspecialchars($row['alamat']) ?></td>
                            <td class="py-3 px-4 text-center text-gray-500"><?= htmlspecialchars($row['created_at']) ?></td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <!-- Tombol Lihat Profil -->
                                    <a href="../admin/detail_user.php?id=<?= $row['id'] ?>&from=kelola_user"
                                        class="px-4 py-1.5 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                                        Lihat
                                    </a>

                                    <!-- Tombol Hapus -->
                                    <form action="../admin/hapus_user.php" method="POST" onsubmit="return confirmDelete(event, <?= $row['id'] ?>)" class="inline">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="from" value="kelola_user">
                                        <button type="submit"
                                            class="px-4 py-1.5 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition shadow-sm cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php
                        // === Update nomor sesuai arah sort ===
                        $no = ($orderDirection === 'ASC') ? $no + 1 : $no - 1;
                        ?>
                    <?php } ?>
                </tbody>
            </table>
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

        // === Alert hapus pengguna === 
        function confirmDelete(e, id) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus Pengguna?',
                text: "Apakah kamu yakin ingin menghapus pengguna ini? Proses ini tidak bisa diurungkan.",
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