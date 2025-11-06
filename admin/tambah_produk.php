<?php
session_start();
include "../koneksi.php";

// === Validasi admin ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Ambil url ===
$from     = $_GET['from'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$source   = $_GET['source'] ?? '';

// === URL Kembali ===
if ($from === 'dashboard_admin') {
    $_SESSION['backUrlTambah'] = "dashboard_admin.php";
} elseif ($from === 'kategori' && !empty($kategori)) {
    $_SESSION['backUrlTambah'] = "kategori.php?nama=" . urlencode($kategori);
} elseif ($from === 'kelola_produk') {
    if ($source === 'kategori' && !empty($kategori)) {
        $_SESSION['backUrlTambah'] = "kelola_produk.php?from=kategori&kategori=" . urlencode($kategori);
    } elseif ($source === 'dashboard_admin') {
        $_SESSION['backUrlTambah'] = "kelola_produk.php?from=dashboard_admin";
    } else {
        $_SESSION['backUrlTambah'] = "kelola_produk.php";
    }
} else {
    $_SESSION['backUrlTambah'] = "dashboard_admin.php";
}

// === Backurl ===
$backUrl = $_SESSION['backUrlTambah'];

// === Ambil kategori default ===
$defaultKategoriId = 0;
if (isset($_GET['kategori_id'])) {
    $id = intval($_GET['kategori_id']);
    $stmt = $koneksi->prepare("SELECT id FROM kategori WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $defaultKategoriId = $row['id'];
    $stmt->close();
}

// === Ambil ukuran ===
$allUkuran = $koneksi->query("SELECT id, size FROM ukuran ORDER BY size ASC");

// === Proses tambah produk ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_produk = trim($_POST['kode_produk']);
    $nama        = trim($_POST['nama']);
    $seri_number = trim($_POST['seri_number']);
    $warna       = trim($_POST['warna']);
    $deskripsi   = trim($_POST['deskripsi']);
    $harga       = intval($_POST['harga']);
    $stok        = intval($_POST['stok']);
    $kategori_id = intval($_POST['kategori_id']);

    // === Validasi dan upload gambar ===
    $targetDir = "../img/";
    $fileName = basename($_FILES["gambar"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    $allowedTypes = ["jpg", "jpeg", "png", "gif", "webp"];
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['flash'] = "❌ Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
        header("Location: tambah_produk.php?from=" . urlencode($from) . "&kategori=" . urlencode($kategori));
        exit;
    }

    // === Upload file ===
    if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFilePath)) {
        $_SESSION['flash'] = "❌ Gagal mengunggah file gambar.";
        header("Location: tambah_produk.php?from=" . urlencode($from) . "&kategori=" . urlencode($kategori));
        exit;
    }

    $gambar = $fileName;

    // === Insert ===
    $stmt = $koneksi->prepare("INSERT INTO produk (kode_produk, nama, seri_number, warna, deskripsi, harga, stok, kategori_id, gambar)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiiis", $kode_produk, $nama, $seri_number, $warna, $deskripsi, $harga, $stok, $kategori_id, $gambar);

    if ($stmt->execute()) {
        $produkId = $stmt->insert_id;

        // 🔹 Tambahkan ukuran & stok per ukuran
        if (isset($_POST['ukuran']) && is_array($_POST['ukuran'])) {
            $ins = $koneksi->prepare("INSERT INTO produk_ukuran (produk_id, ukuran_id, stok) VALUES (?, ?, ?)");
            foreach ($_POST['ukuran'] as $ukuranId) {
                $uid = intval($ukuranId);
                $stokUk = intval($_POST['stok_ukuran'][$uid] ?? 0);
                $ins->bind_param("iii", $produkId, $uid, $stokUk);
                $ins->execute();
            }
            $ins->close();

            // === Update total stok di tabel produk ===
            $updateTotal = $koneksi->prepare(" UPDATE produk SET stok = (SELECT COALESCE(SUM(stok), 0) FROM produk_ukuran WHERE produk_id = ?) WHERE id = ?");
            $updateTotal->bind_param("ii", $produkId, $produkId);
            $updateTotal->execute();
            $updateTotal->close();
        }

        $_SESSION['flash'] = "✅ Berhasil menambahkan produk!";
        header("Location: tambah_produk.php?from=" . urlencode($from) . "&kategori=" . urlencode($kategori) . "&success=1");
        exit;
    } else {
        $_SESSION['flash'] = "❌ Gagal menambahkan produk: " . $stmt->error;
        header("Location: tambah_produk.php?from=" . urlencode($from) . "&kategori=" . urlencode($kategori));
        exit;
    }
    $stmt->close();
}

// === Ambil kategori untuk dropdown ===
$kategori = $koneksi->query("SELECT id, nama FROM kategori");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen bg-gray-100 flex flex-col items-center py-12 px-4">

    <!-- Flash Message -->
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


    <div class="bg-white shadow-lg rounded-2xl w-full max-w-6xl mx-auto p-6 sm:p-8">

        <!-- Judul -->
        <h1 class="text-2xl font-bold mb-6 sm:mb-8 text-gray-800 text-center flex items-center justify-center gap-2">
            Tambah Produk 🛍️
        </h1>

        <!-- ======= DESKTOP: dua form berdampingan ======= -->
        <div class="hidden sm:grid sm:grid-cols-2 sm:gap-8">
            <!-- === Form Data Produk === -->
            <form action="" method="post" enctype="multipart/form-data" id="formProduk" class="space-y-4 text-sm">
                <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-2">Data Produk</h2>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Kode Produk</label>
                    <input type="text" name="kode_produk" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Nama Produk</label>
                    <input type="text" name="nama" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Seri Number</label>
                    <input type="text" name="seri_number"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Warna</label>
                    <input type="text" name="warna"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Harga</label>
                    <input type="number" name="harga" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Kategori</label>
                    <select name="kategori_id" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Pilih Kategori --</option>
                        <?php $kategori->data_seek(0);
                        while ($row = $kategori->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $defaultKategoriId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['nama']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="6"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm leading-relaxed focus:ring-2 focus:ring-blue-500 focus:outline-none resize-y min-h-[160px] shadow-sm"></textarea>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Upload Gambar</label>
                    <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.gif,.webp" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">File akan disimpan di folder <code>img/</code></p>
                </div>
            </form>

            <!-- === Form Ukuran & Stok === -->
            <form action="" method="post" enctype="multipart/form-data" id="formUkuran" class="space-y-4 text-sm flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-2">Ukuran & Stok</h2>
                    <div class="grid grid-cols-4 gap-2 border border-gray-200 rounded-md p-3 bg-gray-50 max-h-[500px] overflow-y-auto">
                        <?php $allUkuran->data_seek(0);
                        while ($row = $allUkuran->fetch_assoc()): ?>
                            <div class="flex items-center justify-between bg-white border rounded px-2 py-1">
                                <label class="flex items-center gap-1 text-gray-700 text-xs">
                                    <input type="checkbox" name="ukuran[]" value="<?= $row['id'] ?>"
                                        class="w-3 h-3 text-blue-600 focus:ring-blue-500">
                                    <?= htmlspecialchars($row['size']) ?>
                                </label>
                                <input type="number" name="stok_ukuran[<?= $row['id'] ?>]" value="0"
                                    class="w-12 border border-gray-300 rounded px-1 text-xs text-center focus:ring-1 focus:ring-blue-500">
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6">
                    <a href="<?= htmlspecialchars($backUrl) ?>"
                        class="px-5 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 transition text-sm shadow">
                        ← Kembali
                    </a>
                    <button id="btnSimpanDesktop" type="submit"
                        class="px-5 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition text-sm shadow flex items-center gap-2 cursor-pointer">
                        <svg id="spinnerDesktop" class="animate-spin hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 100 16v-4l3.5 3.5L12 24v-4a8 8 0 01-8-8z"></path>
                        </svg>
                        <span id="btnTextDesktop">Tambah Produk</span>
                    </button>
                </div>
            </form>
        </div>


        <!-- ======= MOBILE VERSION (tetap 2 tahap) ======= -->
        <!-- ======= MOBILE VERSION (3 langkah) ======= -->
        <div class="sm:hidden flex flex-col gap-6 text-sm">

            <!-- STEP 1: Data Produk -->
            <form id="formStep1" class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-800 text-center">Data Produk</h2>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Kode Produk</label>
                    <input type="text" name="kode_produk" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Nama Produk</label>
                    <input type="text" name="nama" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Harga</label>
                    <input type="number" name="harga" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Kategori</label>
                    <select name="kategori_id" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">-- Pilih Kategori --</option>
                        <?php $kategori->data_seek(0);
                        while ($row = $kategori->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $defaultKategoriId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['nama']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Upload Gambar</label>
                    <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.gif,.webp" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div class="flex flex-col gap-3 mt-4">
                    <button type="button" id="btnNext1"
                        class="w-full bg-black text-white py-2.5 rounded-md hover:bg-gray-700 font-semibold">
                        Lanjut ke Deskripsi →
                    </button>
                    <a href="<?= htmlspecialchars($backUrl) ?>"
                        class="block w-full bg-gray-400 text-white py-2.5 rounded-md hover:bg-gray-500 text-center font-semibold">
                        ← Kembali
                    </a>
                </div>
            </form>

            <!-- STEP 2: Deskripsi & Banner -->
            <form id="formStep2" class="hidden space-y-4">
                <h2 class="text-lg font-semibold text-gray-800 text-center">Deskripsi & Banner</h2>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="6"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none min-h-[160px] shadow-sm"></textarea>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-1">Teks Banner (Opsional)</label>
                    <textarea name="teks_banner" rows="2"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                </div>

                <div class="flex flex-col gap-3 mt-4">
                    <button type="button" id="btnNext2"
                        class="w-full bg-black text-white py-2.5 rounded-md hover:bg-gray-700 font-semibold">
                        Lanjut ke Ukuran & Stok →
                    </button>
                    <button type="button" id="btnPrev1"
                        class="w-full bg-gray-400 text-white py-2.5 rounded-md hover:bg-gray-500 font-semibold">
                        ← Kembali ke Data Produk
                    </button>
                </div>
            </form>

            <!-- STEP 3: Ukuran & Stok -->
            <form action="" method="post" enctype="multipart/form-data" id="formStep3" class="hidden space-y-4">
                <h2 class="text-lg font-semibold text-gray-800 text-center">Ukuran & Stok</h2>

                <div class="grid grid-cols-2 gap-2 border border-gray-200 rounded-md p-3 bg-gray-50">
                    <?php $allUkuran->data_seek(0);
                    while ($row = $allUkuran->fetch_assoc()): ?>
                        <div class="flex items-center justify-between bg-white border rounded px-2 py-1">
                            <label class="flex items-center gap-1 text-gray-700 text-xs">
                                <input type="checkbox" name="ukuran[]" value="<?= $row['id'] ?>"
                                    class="w-3 h-3 text-blue-600 focus:ring-blue-500">
                                <?= htmlspecialchars($row['size']) ?>
                            </label>
                            <input type="number" name="stok_ukuran[<?= $row['id'] ?>]" value="0"
                                class="w-12 border border-gray-300 rounded px-1 text-xs text-center focus:ring-1 focus:ring-blue-500">
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="flex flex-col-reverse justify-between gap-3 pt-4">
                    <button type="button" id="btnPrev2"
                        class="w-1/2 bg-gray-400 text-white py-2 rounded-md hover:bg-gray-500 font-semibold w-full">
                        ← Kembali
                    </button>
                    <button id="btnSubmit" type="submit"
                        class="w-1/2 bg-black text-white py-2 rounded-md hover:bg-gray-800 font-semibold flex justify-center items-center gap-2 w-full">
                        <svg id="spinner" class="animate-spin hidden w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 100 16v-4l3.5 3.5L12 24v-4a8 8 0 01-8-8z"></path>
                        </svg>
                        <span id="btnText">Tambah Produk</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const step1 = document.getElementById("formStep1");
        const step2 = document.getElementById("formStep2");
        const step3 = document.getElementById("formStep3");

        document.getElementById("btnNext1").addEventListener("click", () => {
            step1.classList.add("hidden");
            step2.classList.remove("hidden");
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
        document.getElementById("btnNext2").addEventListener("click", () => {
            step2.classList.add("hidden");
            step3.classList.remove("hidden");
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
        document.getElementById("btnPrev1").addEventListener("click", () => {
            step2.classList.add("hidden");
            step1.classList.remove("hidden");
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
        document.getElementById("btnPrev2").addEventListener("click", () => {
            step3.classList.add("hidden");
            step2.classList.remove("hidden");
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    </script>

</body>



</html>