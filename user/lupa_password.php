<?php
session_start();
include "../koneksi.php";

// Ambil role dari URL (default: user)
$role = $_GET['role'] ?? 'user';
$title = ($role === 'admin') ? "admin" : "user";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Lupa Password <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-lg border border-gray-200 p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">🔑 Lupa Password <?= htmlspecialchars($title) ?></h1>

        <?php if (isset($_SESSION['flash'])): ?>
            <div class="bg-emerald-100 text-emerald-700 border border-emerald-300 px-4 py-2 rounded mb-4 text-center font-medium">
                <?= htmlspecialchars($_SESSION['flash']);
                unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>

        <form action="proses_kirim_reset.php" method="POST" class="space-y-4">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

            <div>
                <label class="block text-gray-700 font-medium mb-1">Email</label>
                <input type="email" name="email" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-400 focus:outline-none">
            </div>

            <button type="submit"
                class="w-full bg-black hover:bg-gray-800 text-white font-semibold py-2 rounded-lg transition shadow-sm">
                Reset
            </button>

            <div class="text-center mt-4 text-sm text-gray-600 ">
                <?php if ($role === 'admin'): ?>
                    <a href="../admin/login_admin.php" class="text-blue-600 hover:underline">← Kembali ke Login Admin</a>
                <?php else: ?>
                    <a href="login_user.php" class="text-blue-600 hover:underline">← Kembali ke Login User</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <script>
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0"; // mulai fade out
                setTimeout(() => flash.remove(), 1000); // hapus setelah 1 detik
            }
        }, 3000); // tampil 3 detik dulu
    </script>
</body>

</html>