<?php
session_start();
include "../koneksi.php";

// Validasi admin
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// Validasi ID produk
if (empty($_GET['id'])) {
    die("Produk tidak ditemukan!");
}
$id = intval($_GET['id']);

// Ambil parameter
$from = $_GET['from'] ?? '';
$kategori = $_GET['kategori'] ?? ($_GET['nama'] ?? '');
$source = $_GET['source'] ?? '';
$q = $_GET['q'] ?? '';

// Simpan session 
if (!empty($kategori)) {
    $_SESSION['lastKategori'] = $kategori;
}
if (!empty($from) && $from !== 'detail_produk') {
    $_SESSION['lastFrom'] = $from;
}

// Redirect
switch ($from) {
    case 'dashboard_admin':
        // Jika berasal dari dashboard_admin
        if ($source === 'admin') {
            // Jika sumber (source) admin -> kembali ke dashboard admin
            $_SESSION['backUrl'] = "dashboard_admin.php";
        } else {
            // Default jika from=dashboard_admin tapi bukan source=admin
            $_SESSION['backUrl'] = "kelola_produk.php?from=dashboard_admin";
        }
        break;

    case 'kategori':
        $_SESSION['backUrl'] = "kategori.php?nama=" . urlencode($kategori);
        break;

    case 'detail_produk':
        $asal = $_SESSION['lastFrom'] ?? 'dashboard_admin';
        $lastKategori = $_SESSION['lastKategori'] ?? '';
        $lastQuery = $_SESSION['lastQuery'] ?? '';
        $base = "../produk/detail_produk.php?id=" . urlencode($id);

        if ($asal === 'kategori' && !empty($lastKategori)) {
            $_SESSION['backUrl'] = $base . "&from=kategori&kategori=" . urlencode($lastKategori);
        } elseif ($asal === 'search') {
            $_SESSION['backUrl'] = $base . "&from=search&kategori=" . urlencode($lastKategori) . "&q=" . urlencode($lastQuery);
        } else {
            $_SESSION['backUrl'] = $base . "&from=dashboard_admin";
        }
        break;

    case 'search':
        $_SESSION['backUrl'] = "../produk/search.php?" . http_build_query([
            'from' => 'kategori',
            'kategori' => $kategori,
            'q' => $q
        ]);
        break;
    case 'kelola_produk':
        $_SESSION['backUrl'] = "kelola_produk.php?from=kategori&kategori=" . urlencode($kategori);
        break;

    default:
        $_SESSION['backUrl'] = "dashboard_admin.php";
        break;
}

$backUrl = $_SESSION['backUrl'];

// Ambil data produk lama
$stmt = $koneksi->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$produk) {
    die("Produk tidak valid!");
}

// Ambil slug kategori produk
$stmt = $koneksi->prepare("SELECT slug FROM kategori WHERE id = ?");
$stmt->bind_param("i", $produk['kategori_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$defaultSlug = $row ? $row['slug'] : "";
$stmt->close();

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_produk = trim($_POST['kode_produk']);
    $nama        = trim($_POST['nama']);
    $seri_number = trim($_POST['seri_number']);
    $warna       = trim($_POST['warna']);
    $deskripsi   = trim($_POST['deskripsi']);
    $teks_banner = trim($_POST['teks_banner'] ?? '');
    $harga       = intval($_POST['harga']);
    $stok        = intval($_POST['stok']);
    $kategori_id = intval($_POST['kategori_id']);

    // === Upload gambar (dengan fallback ke gambar lama) ===
    $gambar = $produk['gambar']; 

    if (!empty($_FILES['gambar']['name'])) {
        $targetDir = "../img/";
        $fileName = basename($_FILES['gambar']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif", "webp"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFilePath)) {
                $gambar = $fileName; // simpan nama file baru
            } else {
                $_SESSION['flash'] = "❌ Gagal mengunggah gambar baru.";
                header("Location: edit_produk.php?id=$id");
                exit;
            }
        } else {
            $_SESSION['flash'] = "❌ Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
            header("Location: edit_produk.php?id=$id");
            exit;
        }
    }

    // === Update data produk ===
    $stmt = $koneksi->prepare("UPDATE produk SET kode_produk=?, nama=?, seri_number=?, warna=?, deskripsi=?, teks_banner=?, harga=?, stok=?, kategori_id=?, gambar=? WHERE id=?");
    $stmt->bind_param("ssssssiiisi", $kode_produk, $nama, $seri_number, $warna, $deskripsi, $teks_banner, $harga, $stok, $kategori_id, $gambar, $id);

    if ($stmt->execute()) {
        if (isset($_POST['ukuran']) && is_array($_POST['ukuran'])) {
            $del = $koneksi->prepare("DELETE FROM produk_ukuran WHERE produk_id = ?");
            $del->bind_param("i", $id);
            $del->execute();
            $del->close();

            $ins = $koneksi->prepare("INSERT INTO produk_ukuran (produk_id, ukuran_id, stok) VALUES (?, ?, ?)");
            foreach ($_POST['ukuran'] as $ukuranId) {
                $uid = intval($ukuranId);
                $stokUk = intval($_POST['stok_ukuran'][$uid] ?? 0);
                $ins->bind_param("iii", $id, $uid, $stokUk);
                $ins->execute();
            }
            $ins->close();

            // === Update total stok di tabel produk (sum semua ukuran) ===
            $updateTotal = $koneksi->prepare(" UPDATE produk SET stok = (SELECT COALESCE(SUM(stok), 0) FROM produk_ukuran WHERE produk_id = ?) WHERE id = ?");
            $updateTotal->bind_param("ii", $id, $id);
            $updateTotal->execute();
            $updateTotal->close();
        }

        // === Flash Message ===
        $_SESSION['flash'] = '✅ Produk berhasil diperbarui';

        // === Kirim ulang asal (biar tetap dinamis) ===
        $fromParam = $_GET['from'] ?? ($_SESSION['lastFrom'] ?? '');
        $kategoriParam = $_GET['kategori'] ?? ($_SESSION['lastKategori'] ?? '');
        $qParam = $_GET['q'] ?? ($_SESSION['lastQuery'] ?? '');

        $url = "edit_produk.php?id=$id";
        if ($fromParam) $url .= "&from=" . urlencode($fromParam);
        if ($kategoriParam) $url .= "&kategori=" . urlencode($kategoriParam);
        if ($qParam) $url .= "&q=" . urlencode($qParam);

        header("Location: $url");
        exit;

    } else {
        $_SESSION['flash'] = "❌ Gagal update produk: " . $stmt->error;
        header("location: edit_profil.php?id=$id");
    }

    $stmt->close();
}

// Ambil kategori & ukuran
$kategori = $koneksi->query("SELECT id, nama FROM kategori");
$allUkuran = $koneksi->query("SELECT id, size FROM ukuran ORDER BY size ASC");
$stmt = $koneksi->prepare("SELECT ukuran_id FROM produk_ukuran WHERE produk_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resUkuran = $stmt->get_result();

$selectedUkuran = [];
while ($row = $resUkuran->fetch_assoc()) {
    $selectedUkuran[] = $row['ukuran_id'];
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk</title>
    <link rel="stylesheet" href="../src/output.css">
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center py-12 px-4">

    <!-- Flash Message -->
    <?php if (isset($_SESSION["flash"])): ?>
        <div id="flash"
            class="fixed top-4 left-1/2 -translate-x-1/2 bg-emerald-100 border border-emerald-400 
                    text-emerald-800 text-center font-medium px-6 py-3 rounded-xl shadow-md transition-all z-50">
            <?= htmlspecialchars($_SESSION["flash"]) ?>
        </div>
        <?php unset($_SESSION["flash"]); ?>
    <?php endif; ?>

    <!-- Proses Update -->
    <div class="bg-white shadow-lg rounded-2xl w-full max-w-6xl mx-auto p-8">
        <h1 class="text-2xl font-bold mb-8 text-gray-800 text-center flex items-center justify-center gap-2">
            Edit Produk 🧩
        </h1>

        <!-- Form Update -->
        <form action="" method="post" id="formEdit" enctype="multipart/form-data"
            class="grid grid-cols-3 gap-x-6 gap-y-5 text-sm">

            <!-- Kolom Kode -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Kode Produk</label>
                <input type="text" name="kode_produk" value="<?= htmlspecialchars($produk['kode_produk']) ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Kolom Nama Produk -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Nama Produk</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($produk['nama']) ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Kolom Seri Number -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Seri Number</label>
                <input type="text" name="seri_number" value="<?= htmlspecialchars($produk['seri_number']) ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Kolom Warna -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Warna</label>
                <input type="text" name="warna" value="<?= htmlspecialchars($produk['warna']) ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Kolom Harga -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Harga</label>
                <input type="number" name="harga" value="<?= htmlspecialchars($produk['harga']) ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Kolom Kategori -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Kategori</label>
                <select name="kategori_id"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <?php while ($row = $kategori->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($row['id'] == $produk['kategori_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['nama']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Kolom Deskripsi -->
            <div class="col-span-3">
                <label class="block text-gray-700 font-medium mb-1">Deskripsi</label>
                <textarea name="deskripsi" rows="6"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm leading-relaxed focus:ring-2 focus:ring-blue-500 focus:outline-none resize-y min-h-[160px] shadow-sm"><?= htmlspecialchars($produk['deskripsi']) ?></textarea>
                <p class="text-xs text-gray-400 mt-1">Gunakan untuk menulis deskripsi panjang, bisa multi-baris.</p>
            </div>


            <!-- Kolom Teks Banner -->
            <div class="col-span-3">
                <label class="block text-gray-700 font-medium mb-1">Teks Banner</label>
                <textarea name="teks_banner" rows="2"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"><?= htmlspecialchars($produk['teks_banner'] ?? '') ?></textarea>
            </div>

            <!-- Kolom Upload Gambar -->
            <div class="col-span-3">
                <label class="block text-gray-700 font-medium mb-1">Upload Gambar</label>
                <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.gif,.webp"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengganti</p>
            </div>

            <!-- Kolom Ukuran & Stok -->
            <div class="col-span-3">
                <label class="block text-gray-700 font-medium mb-2">Ukuran & Stok</label>
                <div class="grid grid-cols-8 gap-2 border border-gray-200 rounded-md p-3 bg-gray-50">
                    <?php
                    $stmt = $koneksi->prepare("SELECT ukuran_id, stok FROM produk_ukuran WHERE produk_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stokResult = $stmt->get_result();
                    $stokMap = [];
                    while ($s = $stokResult->fetch_assoc()) {
                        $stokMap[$s['ukuran_id']] = $s['stok'];
                    }
                    $stmt->close();

                    $allUkuran->data_seek(0);
                    while ($row = $allUkuran->fetch_assoc()):
                        $checked = in_array($row['id'], $selectedUkuran);
                        $stokValue = $stokMap[$row['id']] ?? 0;
                    ?>
                        <div class="flex items-center justify-between bg-white border rounded px-2 py-1">
                            <label class="flex items-center gap-1 text-gray-700 text-xs">
                                <input type="checkbox" name="ukuran[]" value="<?= $row['id'] ?>" <?= $checked ? 'checked' : '' ?>
                                    class="w-3 h-3 text-blue-600 focus:ring-blue-500">
                                <?= htmlspecialchars($row['size']) ?>
                            </label>
                            <input type="number" name="stok_ukuran[<?= $row['id'] ?>]" value="<?= $stokValue ?>"
                                class="w-12 border border-gray-300 rounded px-1 text-xs text-center focus:ring-1 focus:ring-blue-500">
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Tombol -->
            <div class="col-span-3 flex justify-end gap-3 pt-6">
                <a href="<?= htmlspecialchars($backUrl) ?>"
                    class="px-5 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 transition text-sm shadow">
                    ← Kembali
                </a>
                <button id="btnUpdate" type="submit"
                    class="px-5 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition text-sm shadow flex items-center gap-2 cursor-pointer">
                    <svg id="spinner" class="animate-spin hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 100 16v-4l3.5 3.5L12 24v-4a8 8 0 01-8-8z"></path>
                    </svg>
                    <span id="btnText">Update</span>
                </button>
            </div>
        </form>
    </div>

    <script>

        //=== Timeout Flash Messsage ===
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0";
                setTimeout(() => flash.remove(), 700);
            }
        }, 2500);

        //=== Animasi loading tombol ===
        const form = document.getElementById('formEdit');
        const btn = document.getElementById('btnUpdate');
        const spinner = document.getElementById('spinner');
        const text = document.getElementById('btnText');

        form.addEventListener('submit', () => {
            spinner.classList.remove('hidden');
            text.textContent = "Menyimpan...";
            btn.disabled = true;
            btn.classList.add('bg-gray-600', 'cursor-not-allowed');
        });
    </script>

</body>

</html>