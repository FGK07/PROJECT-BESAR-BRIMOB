<?php
session_start();
include "../koneksi.php";

// Pastikan admin login
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// Ambil id
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = "❌ ID kategori tidak valid!";
    header("Location: kelola_produk.php");
    exit;
}

// Ambil data kategori
$stmt = $koneksi->prepare("SELECT * FROM kategori WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$kategori = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kategori) {
    $_SESSION['flash'] = "⚠️ Kategori tidak ditemukan.";
    header("Location: kelola_produk.php");
    exit;
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $slug = strtolower(str_replace(' ', '-', $nama));

    $stmt = $koneksi->prepare("UPDATE kategori SET nama = ?, slug = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nama, $slug, $id);

    if ($stmt->execute()) {
        $_SESSION['flash'] = "✅ Kategori berhasil diperbarui!";
        header("Location: kelola_produk.php");
        exit;
    } else {
        $_SESSION['flash'] = "❌ Gagal memperbarui kategori.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori</title>
    <link rel="stylesheet" href="../src/output.css">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-b from-gray-50 to-gray-200 font-inter">

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-6 left-1/2 -translate-x-1/2 bg-emerald-600 text-white font-medium px-6 py-3 rounded-lg shadow-lg z-50 animate-fade">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white/90 backdrop-blur-md shadow-[0_8px_30px_rgba(0,0,0,0.08)] border border-gray-200 rounded-3xl p-8 w-full max-w-lg transition-all duration-300 hover:shadow-[0_10px_40px_rgba(0,0,0,0.1)]">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">✏️ Edit Kategori</h1>
            <p class="text-gray-500 text-sm">Perbarui data kategori untuk menjaga katalog produk tetap rapi dan teratur.</p>
        </div>

        <form method="POST" class="space-y-6">
            <!-- Input Nama -->
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Nama Kategori</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($kategori['nama']) ?>" 
                class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-gray-800 focus:ring-4 focus:ring-blue-200 focus:border-blue-500 outline-none shadow-sm transition-all duration-200"
                placeholder="Masukkan nama kategori..."
                required>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex flex-col gap-3">
                <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                    💾 Simpan Perubahan
                </button>

                <a href="kelola_produk.php"
                class="text-center w-full py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-100 hover:text-gray-900 transition-all duration-200">
                    ← Kembali
                </a>
            </div>
        </form>
    </div>

    <script>
        // Timeout flash message
        setTimeout(() => {
            const flash = document.getElementById("flash");
            if (flash) {
                flash.style.opacity = "0";
                flash.style.transition = "opacity 0.6s ease";
                setTimeout(() => flash.remove(), 600);
            }
        }, 3000);
    </script>

</body>
</html>
