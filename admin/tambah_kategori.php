<?php
session_start();
include "../koneksi.php";

// === Cek login admin ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Proses tambah ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $slug = strtolower(str_replace(' ', '-', $nama));

    if (empty($nama)) {
        $_SESSION['flash'] = "⚠️ Nama kategori tidak boleh kosong!";
    } else {
        $stmt = $koneksi->prepare("INSERT INTO kategori (nama, slug) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama, $slug);

        if ($stmt->execute()) {
            $_SESSION['flash'] = "✅ Kategori '$nama' berhasil ditambahkan!";
            header("Location: kelola_produk.php");
            exit;
        } else {
            $_SESSION['flash'] = "❌ Gagal menambahkan kategori: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Kategori</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-center px-6">

    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md border border-gray-200">

        <!-- Judul -->
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Tambah Kategori Baru</h2>

        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="bg-blue-100 text-blue-700 p-3 rounded mb-4 text-center">
                <?= htmlspecialchars($_SESSION['flash']);
                unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
            <label class="block mb-3">
                <span class="text-gray-700 font-medium">Nama Kategori</span>
                <input type="text" name="nama" class="w-full mt-1 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
            </label>

            <button type="submit" class="w-full py-2 mt-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                Simpan
            </button>

            <a href="kelola_produk.php" class="block text-center mt-3 text-gray-600 hover:text-gray-800 text-sm">
                ← Kembali
            </a>
        </form>
    </div>
</body>

</html>