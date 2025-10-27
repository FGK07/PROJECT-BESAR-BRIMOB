<?php
session_start();
include "../koneksi.php";

// Cek login admin
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

$from = $_GET['from'] ?? '';
$kategori = $_GET['kategori'] ?? '';


// 🔹 Filter tambahan
$filter = $_GET['filter'] ?? ''; // jenis pesanan
$status = $_GET['status'] ?? ''; // status pre-order
$sort = $_GET['sort'] ?? 'desc'; // urutan ID

$where = [];

if (!empty($filter)) {
    $filter = $koneksi->real_escape_string($filter);
    $where[] = "t.jenis_pesanan = '$filter'";
}

if (!empty($status)) {
    $status = $koneksi->real_escape_string($status);
    $where[] = "po.status = '$status'";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$orderDirection = ($sort === 'asc') ? 'ASC' : 'DESC';

// Ambil semua data pre-order
$result = $koneksi->query("
    SELECT 
        po.id, po.qty, po.total, po.ukuran, po.status, po.tanggal,
        u.nama AS nama_user, 
        p.nama AS nama_produk, 
        p.gambar,
        t.jenis_pesanan
    FROM pre_order po
    JOIN users u ON po.user_id = u.id
    JOIN produk p ON po.produk_id = p.id
    LEFT JOIN transaksi t ON t.pre_order_id = po.id
    $whereClause
    ORDER BY po.id $orderDirection
");

// fallback default
$backUrl = "dashboard_admin.php";
if ($from == 'dashboard_admin') {
    $backUrl = "dashboard_admin.php";
}
if (isset($kategori)) {
    $backUrl = "kategori.php?nama=" . urlencode($kategori);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Pre-Order</title>
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

    <h1 class="text-4xl font-bold text-center mb-8 mt-8 text-gray-800 tracking-wide">🛒 Kelola Pre-Order</h1>

    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-0 left-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md py-3 mb-6 text-center font-medium shadow-sm w-full">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">

            <!-- 🔹 Filter gabungan -->
            <div class="flex items-center justify-end gap-4 px-4 py-3 bg-gray-50 border-b border-gray-200">
                <form method="get" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                    <?php if (!empty($kategori)): ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
                    <?php endif; ?>

                    <!-- Filter Jenis Pesanan -->
                    <div class="flex items-center gap-2">
                        <label for="filter" class="text-sm text-gray-700 font-medium">Jenis:</label>
                        <div class="relative">
                            <select name="filter" id="filter" onchange="this.form.submit()"
                                class="text-center appearance-none bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 pr-8 transition cursor-pointer hover:border-gray-400 shadow-sm">
                                <option value="" <?= ($filter === '') ? 'selected' : '' ?>>Semua</option>
                                <option value="pre order" <?= ($filter === 'pre order') ? 'selected' : '' ?>>Pre-Order</option>
                                <option value="ready stock" <?= ($filter === 'ready stock') ? 'selected' : '' ?>>Ready Stock</option>
                            </select>
                            <svg class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Status -->
                    <div class="flex items-center gap-2">
                        <label for="status" class="text-sm text-gray-700 font-medium">Status:</label>
                        <div class="relative">
                            <select name="status" id="status" onchange="this.form.submit()"
                                class="text-center appearance-none bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 pr-8 transition cursor-pointer hover:border-gray-400 shadow-sm">
                                <option value="" <?= ($status === '') ? 'selected' : '' ?>>Semua</option>
                                <option value="menunggu persetujuan admin" <?= ($status === 'menunggu persetujuan admin') ? 'selected' : '' ?>>Menunggu persetujuan admin</option>
                                <option value="disetujui" <?= ($status === 'disetujui') ? 'selected' : '' ?>>Disetujui</option>
                                <option value="ditolak" <?= ($status === 'ditolak') ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                            <svg class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Urutkan ID -->
                    <div class="flex items-center gap-2">
                        <label for="sort" class="text-sm text-gray-700 font-medium">Urutkan ID:</label>
                        <div class="relative">
                            <select name="sort" id="sort" onchange="this.form.submit()"
                                class="text-center appearance-none bg-white border border-gray-300 text-gray-800 text-sm rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 pr-8 transition cursor-pointer hover:border-gray-400 shadow-sm">
                                <option value="desc" <?= ($sort === 'desc') ? 'selected' : '' ?>>Terbaru (ID besar dulu)</option>
                                <option value="asc" <?= ($sort === 'asc') ? 'selected' : '' ?>>Terlama (ID kecil dulu)</option>
                            </select>
                            <svg class="w-4 h-4 text-gray-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Tombol reset -->
                    <?php if (!empty($filter) || !empty($status) || ($sort !== 'desc')): ?>
                        <a href="kelola_pre_order.php"
                            class="text-sm text-gray-500 hover:text-gray-700 underline ml-1 transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABEL -->
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-sm uppercase tracking-wide">
                        <th class="p-3 text-center">ID</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Produk</th>
                        <th class="p-3 text-center">Ukuran</th>
                        <th class="p-3 text-center">Qty</th>
                        <th class="p-3 text-center">Total</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Tanggal</th>
                        <th class="p-3 text-center">Jenis Pesanan</th>
                        <th class="p-3 text-center">Bukti Transfer</th>
                        <th class="p-3 text-center">Waktu Upload</th>
                        <th class="p-3 text-center">Aksi</th>

                    </tr>
                </thead>
                <tbody class="text-gray-800 text-sm">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $statusLower = strtolower($row['status']);
                            $warna = match ($statusLower) {
                                'menunggu persetujuan admin' => 'bg-yellow-100 text-yellow-700 border border-yellow-300',
                                'disetujui' => 'bg-green-100 text-green-700 border border-green-300',
                                'ditolak' => 'bg-red-100 text-red-700 border border-red-300',
                                default => 'bg-gray-100 text-gray-700 border border-gray-300'
                            };
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-200 align-top">
                                <td class="p-3 text-center font-semibold text-gray-600"><?= $row['id'] ?></td>
                                <td class="p-3 font-medium text-center"><?= htmlspecialchars($row['nama_user']) ?></td>
                                <td class="p-3 flex items-center gap-2">
                                    <img src="../img/<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>" class="w-10 h-10 object-contain border rounded">
                                    <span><?= htmlspecialchars($row['nama_produk']) ?></span>
                                </td>
                                <td class="p-3 text-center"><?= htmlspecialchars($row['ukuran']) ?></td>
                                <td class="p-3 text-center"><?= $row['qty'] ?></td>
                                <td class="p-3 text-center text-emerald-600 font-semibold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                <td class="p-3 text-center">
                                    <span class="px-3 py-1.5 rounded-full text-xs font-semibold <?= $warna ?>">
                                        <?= ucfirst($statusLower) ?>
                                    </span>
                                </td>
                                <!-- 🗓️ Kolom Tanggal Transaksi dengan styling seperti Waktu Upload -->
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
                                <td class="p-3 text-center">
                                    <?php if (!empty($row['jenis_pesanan'])): ?>
                                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold <?= $row['jenis_pesanan'] === 'pre order' ? 'bg-purple-100 text-purple-700 border border-purple-300' : 'bg-blue-100 text-blue-700 border border-blue-300' ?>">
                                            <?= ucfirst($row['jenis_pesanan']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-300">Belum ada</span>
                                    <?php endif; ?>
                                </td>
                                <!-- 🖼️ Bukti Transfer -->
                                <td class="p-3 text-center">
                                    <?php
                                    // Ambil bukti_transfer berdasarkan pre_order_id (tanpa ubah logika utama)
                                    $buktiQuery = $koneksi->query("SELECT bukti_transfer FROM transaksi WHERE pre_order_id = {$row['id']}");
                                    $buktiData = $buktiQuery ? $buktiQuery->fetch_assoc() : null;
                                    $buktiTransfer = $buktiData['bukti_transfer'] ?? null;

                                    if (!empty($buktiTransfer)) {
                                        echo "<button onclick=\"openModal('../uploads/bukti/" . urlencode($buktiTransfer) . "')\" class='text-blue-600 hover:underline font-medium'>Lihat Bukti</button>";
                                    } else {
                                        echo "<span class='text-gray-500 italic'>Belum ada</span>";
                                    }
                                    ?>
                                </td>

                                <!-- 🕒 Waktu Upload -->
                                <td class="p-3 text-center text-gray-600">
                                    <?php
                                    $timeQuery = $koneksi->query("SELECT waktu_upload FROM transaksi WHERE pre_order_id = {$row['id']}");
                                    $timeData = $timeQuery ? $timeQuery->fetch_assoc() : null;
                                    $waktuUpload = $timeData['waktu_upload'] ?? null;

                                    if (!empty($waktuUpload)) {
                                        $tanggal = date("Y-m-d", strtotime($waktuUpload));
                                        $jam = date("H:i:s", strtotime($waktuUpload));
                                        echo "<span class='text-sm font-medium text-gray-700'>{$tanggal}<br><span class=\"text-xs text-gray-500\">{$jam}</span></span>";
                                    } else {
                                        echo "<span class='text-gray-400 italic'>-</span>";
                                    }
                                    ?>
                                </td>

                                <td class="p-3 text-center align-middle">
                                    <div class="flex items-center justify-center gap-2 min-h-[48px]">
                                        <?php if ($statusLower === 'menunggu persetujuan admin'): ?>
                                            <a href="proses_pre_order.php?id=<?= $row['id'] ?>&aksi=setuju"
                                                class="px-4 py-1.5 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition shadow-sm text-sm font-medium">
                                                Setujui
                                            </a>
                                            <a href="proses_pre_order.php?id=<?= $row['id'] ?>&aksi=tolak"
                                                class="px-4 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 transition shadow-sm text-sm font-medium">
                                                Tolak
                                            </a>
                                        <?php elseif ($statusLower === 'disetujui'): ?>
                                            <span
                                                class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 border border-green-300 whitespace-nowrap">
                                                ✅ Disetujui
                                            </span>
                                        <?php elseif ($statusLower === 'ditolak'): ?>
                                            <span
                                                class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 border border-red-300 whitespace-nowrap">
                                                ❌ Ditolak
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="py-6 text-center text-gray-500 italic">Belum ada pre-order masuk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="relative bg-white rounded-2xl p-4 shadow-lg">
            <button onclick="closeModal()" class="absolute -top-3 -right-3 bg-red-600 text-white w-7 h-7 rounded-full">×</button>
            <img id="modalImage" src="" alt="Bukti Transfer" class="max-w-[90vw] max-h-[80vh] rounded-lg object-contain">
        </div>
    </div>

    <script>
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0";
                setTimeout(() => flash.remove(), 1000);
            }
        }, 3000);

        function openModal(src) {
            const modal = document.getElementById('modal');
            const img = document.getElementById('modalImage');
            img.src = src;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }
    </script>
</body>

</html>