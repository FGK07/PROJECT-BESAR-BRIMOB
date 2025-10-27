<?php
session_start();
include "../koneksi.php";

// === Cek login admin ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Ambil semua transaksi ===
$filter = $_GET['filter'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'desc'; // default urut dari besar ke kecil
$orderDirection = ($sort === 'asc') ? 'ASC' : 'DESC';
$where = [];

if (!empty($filter)) {
    $filter = $koneksi->real_escape_string($filter);
    $where[] = "t.jenis_pesanan = '$filter'";
}

if (!empty($status)) {
    $status = $koneksi->real_escape_string($status);
    $where[] = "t.status = '$status'";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$result = $koneksi->query(" SELECT t.id, t.total, t.status, t.tanggal, t.bukti_transfer, t.jenis_pesanan, u.nama AS nama_user, m.nama_metode AS metode_pembayaran
                            FROM transaksi t JOIN users u ON t.user_id = u.id
                            LEFT JOIN metode_pembayaran m ON t.metode_pembayaran_id = m.id
                            $whereClause
                            ORDER BY t.id $orderDirection ");

// === Ambil semua detail transaksi sekaligus ===
$detailAll = [];
$detailQuery = $koneksi->query(" SELECT d.transaksi_id, d.qty, d.harga, d.ukuran, p.nama, p.gambar FROM detail_transaksi d JOIN produk p ON d.produk_id = p.id ");
while ($d = $detailQuery->fetch_assoc()) {
    $detailAll[$d['transaksi_id']][] = $d;
}


// === Tentukan URL kembali ===
if (isset($_GET['from'])) {
    if ($_GET['from'] === "dashboard_admin") {
        $_SESSION['backUrl'] = "dashboard_admin.php";
    } elseif ($_GET['from'] === "kategori" && isset($_GET['kategori'])) {
        $_SESSION['backUrl'] = "kategori.php?nama=" . urlencode($_GET['kategori']);
    }
}
$backUrl = $_SESSION['backUrl'] ?? "dashboard_admin.php"; // default
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Transaksi</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen bg-gray-100 px-8 py-10">

    <!-- Tombol kembali -->
    <div class="mt-4 flex justify-start">
        <a href="<?= htmlspecialchars($backUrl) ?>"
            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 shadow-md transition">
            ← Kembali
        </a>
    </div>

    <!-- Judul -->
    <h1 class="text-4xl font-bold text-center mb-8 mt-8 text-gray-800 tracking-wide">📦 Kelola Transaksi</h1>

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-0 left-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md mr-auto py-3 mb-6 text-center font-medium shadow-sm w-full">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <!-- 🔽 Filter Dropdown Ganda: Jenis Pesanan + Status -->
            <div class="flex items-center justify-end gap-4 px-4 py-3 bg-gray-50 border-b border-gray-200">
                <form method="get" class="flex items-center gap-3">

                    <!-- Filter Jenis Pesanan -->
                    <div class="flex items-center gap-2">
                        <label for="filter" class="text-sm text-gray-700 font-medium">Jenis:</label>
                        <div class="relative">
                            <select
                                name="filter"
                                id="filter"
                                onchange="this.form.submit()"
                                class="text-center appearance-none bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 pr-8 transition cursor-pointer hover:border-gray-400 shadow-sm">
                                <option class="text-center" value="" <?= ($filter ?? '') === '' ? 'selected' : '' ?>>Semua</option>
                                <option class="text-center" value="pre order" <?= ($filter ?? '') === 'pre order' ? 'selected' : '' ?>>Pre-Order</option>
                                <option class="text-center" value="ready stock" <?= ($filter ?? '') === 'ready stock' ? 'selected' : '' ?>>Ready Stock</option>
                            </select>
                            <!-- Ikon panah -->
                            <svg
                                class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Status Transaksi -->
                    <div class="flex items-center gap-2">
                        <label for="status" class="text-sm text-gray-700 font-medium">Status:</label>
                        <div class="relative">
                            <select
                                name="status"
                                id="status"
                                onchange="this.form.submit()"
                                class="text-center appearance-none bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 pr-8 transition cursor-pointer hover:border-gray-400 shadow-sm">
                                <option class="text-center" value="" <?= empty($_GET['status']) ? 'selected' : '' ?>>Semua</option>
                                <option class="text-center" value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option class="text-center" value="disetujui" <?= ($_GET['status'] ?? '') === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                                <option class="text-center" value="selesai" <?= ($_GET['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option class="text-center" value="ditolak" <?= ($_GET['status'] ?? '') === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                <option class="text-center" value="batal" <?= ($_GET['status'] ?? '') === 'batal' ? 'selected' : '' ?>>Batal</option>
                                <option class="text-center" value="dibatalkan oleh user" <?= ($_GET['status'] ?? '') === 'dibatalkan oleh user' ? 'selected' : '' ?>>Dibatalkan oleh User</option>
                            </select>
                            <!-- Ikon panah -->
                            <svg
                                class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Urutkan Berdasarkan ID -->
                    <div class="flex items-center gap-2">
                        <label for="sort" class="text-sm text-gray-700 font-medium">Urutkan ID :</label>
                        <div class="relative">
                            <select
                                name="sort"
                                id="sort"
                                onchange="this.form.submit()"
                                class="text-center appearance-none bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 pr-8 transition cursor-pointer hover:border-gray-400 shadow-sm">
                                <option value="desc" <?= ($sort === 'desc') ? 'selected' : '' ?>>Terbaru</option>
                                <option value="asc" <?= ($sort === 'asc') ? 'selected' : '' ?>>Terlama</option>
                            </select>
                            <svg
                                class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Tombol reset -->
                    <?php if (!empty($filter) || !empty($_GET['status'])): ?>
                        <a href="kelola_transaksi.php"
                            class="text-sm text-gray-500 hover:text-gray-700 underline ml-1 transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table -->
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-sm uppercase tracking-wide">
                        <th class="p-3 text-center">ID</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Metode Pembayaran</th>
                        <th class="p-3 text-center">Total</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Tanggal</th>
                        <th class="p-3 text-center">Jenis Pesanan</th>
                        <th class="p-3 text-center">Bukti Transfer</th>
                        <th class="p-3 text-center">Waktu Upload Bukti</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 text-sm">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            // Status
                            $status = strtolower($row['status']);
                            $warna = match ($status) {
                                'pending' => 'bg-yellow-100 text-yellow-700 border border-yellow-300',
                                'disetujui' => 'bg-blue-100 text-blue-700 border border-blue-300',
                                'selesai' => 'bg-green-100 text-green-700 border border-green-300',
                                'ditolak' => 'bg-red-100 text-red-700 border border-red-300',
                                'batal' => 'bg-red-100 text-red-700 border border-red-300',
                                'dibatalkan oleh user' => 'bg-red-100 text-red-600 border border-red-300 font-semibold',
                                'ditolak oleh admin' => 'bg-red-100 text-red-700 border border-red-300 font-semibold',
                                default => 'bg-gray-100 text-gray-700 border border-gray-300'
                            };

                            ?>
                            <tr class="hover:bg-gray-50 transition duration-200 align-top">

                                <!-- Kolom Data -->
                                <td class="p-3 text-center font-semibold text-gray-600"><?= $row['id'] ?></td>
                                <td class="p-3 font-medium text-center"><?= htmlspecialchars($row['nama_user']) ?></td>
                                <td class="p-3 font-medium text-center"><?= htmlspecialchars($row['metode_pembayaran'] ?? '-') ?></td>
                                <td class="p-3 text-center text-green-600 font-semibold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                <td class="p-3 text-center">
                                    <span class="px-3 py-1.5 rounded-full text-xs font-semibold <?= $warna ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <!-- 🗓️ Kolom Tanggal Transaksi -->
                                <td class="p-2 text-center text-gray-600">
                                    <?php
                                    if (!empty($row['tanggal'])) {
                                        $tanggal = date("Y-m-d", strtotime($row['tanggal']));
                                        $jam = date("H:i:s", strtotime($row['tanggal']));
                                        echo "<span class='text-sm font-medium text-gray-700'>{$tanggal}<br><span class='text-xs text-gray-500'>{$jam}</span></span>";
                                    } else {
                                        echo "<span class='text-gray-400 italic'>-</span>";
                                    }
                                    ?>
                                </td>

                                <!-- Jenis Pesanan -->
                                <td class="p-3 text-center">
                                    <?php if (!empty($row['jenis_pesanan'])): ?>
                                        <?php if ($row['jenis_pesanan'] === 'pre order'): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-300">
                                                Pre-Order
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-300">
                                                Ready Stock
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-300">
                                            Tidak Diketahui
                                        </span>
                                    <?php endif; ?>
                                </td>


                                <!-- 🔍 Kolom Bukti Transfer -->
                                <td class="p-3 text-center">
                                    <?php if (!empty(trim($row['bukti_transfer']))): ?>
                                        <button onclick="openModal('../uploads/bukti/<?= urlencode($row['bukti_transfer']) ?>')"
                                            class="text-blue-600 hover:underline font-medium">
                                            Lihat Bukti
                                        </button>
                                    <?php elseif ($row['metode_pembayaran'] === 'COD'): ?>
                                        <span class="text-gray-500 italic">Pembayaran COD</span>
                                    <?php else: ?>
                                        <span class="text-gray-500 italic">Belum ada</span>
                                    <?php endif; ?>
                                </td>

                                <!-- 🕒 Kolom Waktu Upload -->
                                <td class="p-3 text-center text-gray-600">
                                    <?php
                                    // === Ambil waktu_upload dari transaksi ===
                                    $timeQuery = $koneksi->query("SELECT waktu_upload FROM transaksi WHERE id = {$row['id']}");
                                    $timeData = $timeQuery ? $timeQuery->fetch_assoc() : null;
                                    $waktuUpload = $timeData['waktu_upload'] ?? null;

                                    if (!empty($waktuUpload)) {
                                        $tanggal = date("Y-m-d", strtotime($waktuUpload));
                                        $jam = date("H:i:s", strtotime($waktuUpload));
                                        echo "<span class='text-sm font-medium text-gray-700'>{$tanggal}<br><span class='text-xs text-gray-500'>{$jam}</span></span>";
                                    } else {
                                        echo "<span class='text-gray-400 italic'>-</span>";
                                    }
                                    ?>
                                </td>


                                <!-- Tombol aksi -->
                                <td class="p-3 text-center space-x-2">
                                    <?php if ($row['status'] === 'pending' && $row['jenis_pesanan'] === 'pre order'): ?>
                                        <!-- Pre-order -->
                                        <form action="kelola_status.php" method="post" class="inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="setuju">
                                            <input type="hidden" name="tipe" value="preorder">
                                            <button type="submit" class="px-4 py-1.5 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">Setujui</button>
                                        </form>
                                        <form action="kelola_status.php" method="post" class="inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="tolak">
                                            <input type="hidden" name="tipe" value="preorder">
                                            <button type="submit" class="px-4 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700">Tolak</button>
                                        </form>

                                    <?php elseif ($row['status'] === 'pending'): ?>
                                        <!-- Transaksi biasa -->
                                        <form action="kelola_status.php" method="post" class="inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="setuju">
                                            <input type="hidden" name="tipe" value="transaksi">
                                            <button type="submit" class="px-4 py-1.5 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">Setujui</button>
                                        </form>
                                        <form action="kelola_status.php" method="post" class="inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="tolak">
                                            <input type="hidden" name="tipe" value="transaksi">
                                            <button type="submit" class="px-4 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700">Tolak</button>
                                        </form>

                                    <!-- Status Aksi-->
                                    <?php elseif ($row['status'] === 'disetujui'): ?>
                                        <span class="italic text-gray-500">Menunggu konfirmasi penerimaan user</span>
                                    <?php else: ?>
                                        <?php if ($row['status'] === 'selesai'): ?>
                                            <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold 
                                                bg-emerald-100 text-emerald-700 border border-emerald-300">
                                                Disetujui ✅
                                            </span>
                                        <?php elseif ($row['status'] === 'dibatalkan oleh user'): ?>
                                            <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold 
                                                bg-red-100 text-red-700 border border-red-300">
                                                Dibatalkan oleh User 🚫
                                            </span>
                                        <?php elseif ($row['status'] === 'batal' || $row['status'] === 'ditolak'): ?>
                                            <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold 
                                                bg-red-100 text-red-700 border border-red-300">
                                                Ditolak oleh Admin ❌
                                            </span>
                                        <?php else: ?>
                                            <span class="italic text-gray-500">Tidak ada aksi</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Barang yang dibeli -->
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <td colspan="8" class="p-3">
                                    <?php if (!empty($detailAll[$row['id']])): ?>
                                        <div class="grid md:grid-cols-2 gap-3">
                                            <?php foreach ($detailAll[$row['id']] as $item): ?>

                                                <!-- Gambar -->
                                                <div class="flex items-center gap-3 p-2 bg-white rounded-lg border border-gray-200 shadow-sm">
                                                    <img src="../img/<?= htmlspecialchars($item['gambar']) ?>"
                                                        alt="<?= htmlspecialchars($item['nama']) ?>"
                                                        class="w-12 h-12 object-contain rounded border">

                                                    <!-- Nama dll -->
                                                    <div>
                                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($item['nama']) ?></p>
                                                        <p class="text-xs text-gray-500">
                                                            x<?= $item['qty'] ?> <?= $item['ukuran'] ? '• Ukuran: ' . htmlspecialchars($item['ukuran']) : '' ?>
                                                        </p>
                                                        <p class="text-sm text-emerald-600 font-semibold">
                                                            Rp<?= number_format($item['harga'], 0, ',', '.') ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-gray-500 text-sm italic">Tidak ada detail produk.</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-6 text-center text-gray-500 italic">Belum ada transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Popup -->
    <div id="modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="relative bg-white rounded-2xl p-4 shadow-lg">
            <button onclick="closeModal()" class="absolute -top-3 -right-3 bg-red-600 text-white w-7 h-7 rounded-full">×</button>
            <img id="modalImage" src="" alt="Bukti Transfer" class="max-w-[90vw] max-h-[80vh] rounded-lg object-contain">
        </div>
    </div>

    <script>
        // === Open modal ===
        function openModal(src) {
            const modal = document.getElementById('modal');
            const img = document.getElementById('modalImage');
            img.src = src;
            modal.classList.remove('hidden');
        }

        // === Close modal
        function closeModal() {
            const modal = document.getElementById('modal');
            modal.classList.add('hidden');
        }

        // Timeout flash message
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