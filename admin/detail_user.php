<?php
session_start();
include "../koneksi.php";

// Cek login admin
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// Validasi ID user
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = "❌ ID user tidak valid!";
    header("Location: kelola_user.php");
    exit;
}

// Ambil data user
$stmt = $koneksi->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Validasi
if (!$user) {
    $_SESSION['flash'] = "❌ Data user tidak ditemukan!";
    header("Location: kelola_user.php");
    exit;
}

// Foto user
$foto = trim($user['foto'] ?? '');
if ($foto) {
    $fotoPath = str_starts_with($foto, 'http') ? $foto : "../" . $foto;
} else {
    $fotoPath = "";
}

// 🔙 Back url
if (isset($_GET['from']) && $_GET['from'] === "kelola_user") {
    $backUrl = "kelola_user.php";

} else {
    // 🔙 Balik ke halaman kelola user, bukan asal utama
    $backUrl = $_SESSION['kelolaUserBack'] ?? "kelola_user.php";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pengguna</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-200 flex flex-col items-center py-10">

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash"
            class="fixed top-6 left-1/2 -translate-x-1/2 bg-emerald-600 text-white font-medium px-6 py-3 rounded-lg shadow-md z-50">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Kartu Profil -->
    <div class="w-full max-w-3xl bg-white rounded-3xl shadow-[0_8px_30px_rgba(0,0,0,0.08)] border border-gray-200 overflow-hidden">

        <!-- Header -->
        <div class="bg-gray-800 text-white py-6 px-8 flex flex-col sm:flex-row items-center gap-5">

        <!-- Validasi -->
            <?php if (!empty($fotoPath)): ?>
                <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Foto Profil"
                    class="w-28 h-28 rounded-full object-cover border-4 border-emerald-400 shadow-lg">
            <?php else: ?>
                <div class="w-28 h-28 rounded-full bg-gray-200 flex items-center justify-center border-4 border-emerald-400 shadow-lg">
                    <i class="fas fa-user text-5xl text-gray-600"></i>
                </div>
            <?php endif; ?>

            <div class="text-center sm:text-left">
                <h1 class="text-3xl font-bold"><?= htmlspecialchars($user['nama']) ?></h1>
                <p class="text-emerald-200 text-sm"><?= htmlspecialchars($user['email']) ?></p>
                <p class="text-gray-300 text-xs mt-1 italic">ID Pengguna: <?= $user['id'] ?></p>
            </div>
        </div>

        <!-- Detail Informasi -->
        <div class="px-8 py-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-gray-700">
            <p><span class="font-semibold text-gray-900">Nomor Telepon:</span><br> <?= htmlspecialchars($user['nomor_telepon'] ?? '-') ?></p>
            <p><span class="font-semibold text-gray-900">Oauth UID:</span><br> <?= htmlspecialchars($user['oauth_uid'] ?? '-') ?></p>
            <p><span class="font-semibold text-gray-900">Alamat:</span><br> <?= htmlspecialchars($user['alamat'] ?? '-') ?></p>
            <p><span class="font-semibold text-gray-900">Role:</span><br>
                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium border
                    <?php if ($user['role'] === 'admin'): ?>
                        bg-blue-100 text-blue-700 border-blue-300
                    <?php else: ?>
                        bg-green-100 text-green-700 border-green-300
                    <?php endif; ?>">
                    <?= ucfirst($user['role']) ?>
                </span>
            </p>
            <p><span class="font-semibold text-gray-900">Tanggal Dibuat:</span><br>
                <?= htmlspecialchars($user['created_at']) ?>
            </p>
        </div>

        <!-- Tombol Aksi -->
        <div class="px-8 py-6 border-t border-gray-200 flex flex-wrap justify-between gap-3">
            <a href="<?= htmlspecialchars($backUrl) ?>"
                class="flex items-center gap-2 px-6 py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition shadow-md text-sm font-medium">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>

            <a href="hapus_user.php?id=<?= $user['id'] ?>&from=detail_user"
                onclick="return confirm('Yakin ingin menghapus user ini?')"
                class="flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition shadow-md text-sm font-medium">
                <i class="fas fa-trash"></i> Hapus User
            </a>
        </div>
    </div>

    <script>
        // Timeout flash
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