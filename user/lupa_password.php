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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password <?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-0 sm:px-0 py-10 sm:py-0">

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash"
            class="fixed top-3 left-0 sm:left-1/2 sm:-translate-x-1/2 w-full sm:w-auto sm:max-w-md bg-emerald-100 text-emerald-800 border border-emerald-300 
                            rounded-md sm:rounded-lg px-4 sm:px-6 py-2 sm:py-3 
                            text-center font-medium text-xs sm:text-sm 
                            shadow-md sm:shadow-lg 
                            z-[9999] animate-slide-down">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Card utama -->
<div class="bg-white w-[min(95%,420px)] sm:w-full sm:max-w-md rounded-2xl shadow-lg border border-gray-200 
            p-6 sm:p-8 mx-auto transition-all duration-300">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-5 sm:mb-6 text-center leading-tight flex items-center justify-center gap-2">
            <span class="text-yellow-500 text-lg sm:text-2xl">🔑</span> Lupa Password <?= htmlspecialchars($title) ?>
        </h1>

        <form action="proses_kirim_reset.php" method="POST" class="space-y-4 sm:space-y-5">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

            <div>
                <label class="block text-gray-700 font-medium mb-1 text-sm sm:text-base">Email</label>
                <input type="email" name="email" required
                    class="w-full px-3 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-400 focus:outline-none">
            </div>

            <button type="submit"
                class="w-full bg-black hover:bg-gray-800 text-white font-semibold py-2.5 sm:py-3 rounded-lg text-sm sm:text-base transition shadow-sm active:scale-[0.98]">
                Kirim Link Reset
            </button>

            <div class="text-center mt-3 sm:mt-4 text-[13px] sm:text-sm text-gray-600">
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
                flash.style.opacity = "0";
                flash.style.transition = "opacity 0.8s ease";
                setTimeout(() => flash.remove(), 800);
            }
        }, 3000);
    </script>
</body>

</html>