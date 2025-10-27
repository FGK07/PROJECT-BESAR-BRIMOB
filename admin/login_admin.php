<?php
session_start();
include "../koneksi.php";
// === Pastikan user session hilang ===
unset($_SESSION['user']);

if (isset($_POST['login_admin'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // === Validasi awal ===  
    if (empty($email) || empty($password)) {
        echo "❌ Email dan password wajib diisi!";
        exit;
    }

    // === Query ===
    $stmt = $koneksi->prepare("SELECT id, nama, foto, email, password FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // === Cek password hash ===
        if (password_verify($password, $row['password'])) {

            // === Simpan data admin di session ===
            $_SESSION['admin'] = [
                'id'   => $row['id'],
                'nama' => $row['nama'],
                'foto' => $row['foto'],
                'role' => 'admin'
            ];

            // === Redirect ke dashboard ===
            $_SESSION['flash'] = "Selamat datang admin " . $row['nama'] . " !";
            header("Location: dashboard_admin.php");
            exit;

        } else {
            $_SESSION['flash'] = "❌ Email atau password admin salah!";
        }
    } else {
        $_SESSION['flash'] = "❌ Admin tidak ditemukan!";
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen flex flex-col items-center py-10 px-8">
    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash" class="fixed top-0 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md px-4 py-3 mb-6 text-center font-medium shadow-sm w-full">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Container Form -->
    <div class="shadow-xl/30 p-6 rounded-lg w-80 mt-auto mb-auto">

        <!-- Form -->
        <form method="post" action="">
            <h2 class="text-3xl mb-2 text-center font-bold">Login Admin</h2>
            <label for="email">Email</label>
            <input class="px-5 pb-1 border border-gray-300 mb-3 w-full h-10 rounded-xl focus:border-gray-300 outline-none" type="text" name="email" placeholder="Masukkan email" required><br>

            <label for="password">Password</label>
            <input class="px-5 pb-1 border border-gray-300 mb-3 w-full h-10 rounded-xl focus:border-gray-300 outline-none" type="password" name="password" placeholder="Masukkan password" required><br>
            <div class="text-right mt-1 text-sm text-gray-600">
                <a href="../auth/lupa_password.php?role=admin"
                    class="text-blue-600 hover:text-blue-700">
                    Lupa Password?
                </a>
            </div>

            <button class="mt-3 w-full h-10 text-white font-bold bg-black border rounded-lg cursor-pointer hover:bg-gray-800"
                type="submit" name="login_admin">Submit</button>
        </form>
        </form>
    </div>
    <script>

        // === Timeout flash message
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