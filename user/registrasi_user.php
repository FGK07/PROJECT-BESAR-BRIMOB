<?php
session_start();
include "../koneksi.php";

// menyertakan file (wajib ada) 
require_once "../config.php";

// buat url untuk login ke google
$url = $client->createAuthUrl();
$from = $_GET['from']??'';
$kategori = $_GET['kategori']??'';
$backUrl = '';

if ($from === "homepage"){
    $backUrl = "../homepage.php";
}elseif (isset($kategori)){
    $backUrl = "../produk/kategori.php?nama=".$kategori;
}

// cek input form dan ambil inputannya
if (isset($_POST['registrasi'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $pass_raw = $_POST['password'];

    // Validasi form
    if (empty($nama) || empty($email) || empty($pass_raw)) {
        $_SESSION['flash'] = "Data tidak boleh ada yang kosong";
        header('location: registrasi_user.php');
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = "Format email tidak valid";
        header('location: registrasi_user.php');
        exit;
    } elseif (strlen($pass_raw) < 8) {
        $_SESSION['flash'] = "Password minimal 8 karakter";
        header('location: registrasi_user.php');
        exit;
    } else {
        $password = password_hash($pass_raw, PASSWORD_DEFAULT);

        $cek = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $_SESSION['flash'] = "Email sudah terdaftar!";
            header('location: registrasi_user.php');
            exit;
        } else {
            $stmt = $koneksi->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $nama, $email, $password);

            if ($stmt->execute()) {
                $_SESSION['registrasi_sukses'] = true;
                header('location: registrasi_user.php');
                exit;
            } else {
                $_SESSION['flash'] = "Terjadi kesalahan " . $stmt->error;
                header('location: registrasi_user.php');
                exit;
            }
            $stmt->close();
        }

        $cek->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REGISTRASI</title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="min-h-screen flex flex-col items-center py-10 px-8">
    <h2 class="font-lobster text-5xl text-center">BRIMOB SPORT</h2>
    <div class="shadow-[0_0_10px_rgba(0,0,0,0.3)] p-6 rounded-lg w-80 mt-auto mb-auto">

        <form method="post" action="registrasi_user.php">
            <?php if (isset($_SESSION["flash"])): ?>
                <div class="bg-red-100 text-red-700 p-2 rounded mb-3">
                    <?= htmlspecialchars($_SESSION["flash"]) ?>
                </div>
                <?php unset($_SESSION["flash"]); ?>
            <?php endif; ?>
            <h2 class="text-3xl mb-2 text-center font-bold">Daftar Sekarang</h2>
            <p class="mb-7 text-center">Sudah punya akun Brimob? <a href="login_user.php" class="text-blue-900">Masuk</a></p>

            <label for="nama">Nama</label><br>
            <input class="px-5 pb-1 border border-gray-300 mb-3 w-full h-10 rounded-xl focus:border-gray-300 outline-none" type="text" name="nama" placeholder="Masukkan nama" required><br>

            <label for="email">Email</label><br>
            <input class="px-5 pb-1 border border-gray-300 mb-3 w-full h-10 rounded-xl focus:border-gray-300 outline-none" type="text" name="email" placeholder="Masukkan email" required><br>

            <label for="password">Password</label><br>
            <input class="px-5 pb-1 border border-gray-300 mb-3 w-full h-10 rounded-xl focus:border-gray-300 outline-none" type="password" name="password" placeholder="Masukkan password" minlength="8" required><br>

            <button class="mt-3 w-full h-10 text-white font-bold bg-black border rounded-lg cursor-pointer hover:bg-gray-800"
                type="submit" name="registrasi">
                SUBMIT
            </button>

            <div class="flex items-center my-4">
                <div class="flex-grow border-t border-gray-300"></div>
                <span class="px-3 text-gray-500">atau masuk dengan</span>
                <div class="flex-grow border-t border-gray-300"></div>
            </div>

            <button type="button" class="flex items-center justify-center gap-0 w-full h-10 border rounded-lg hover:bg-gray-200 cursor-pointer"
                onclick="window.location.href='<?= $url ?>'">
                <svg class="h-8 w-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                    <path fill="#fbc02d" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12	s5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20	s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"></path>
                    <path fill="#e53935" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039	l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"></path>
                    <path fill="#4caf50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36	c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"></path>
                    <path fill="#1565c0" d="M43.611,20.083L43.595,20L42,20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571	c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"></path>
                </svg>
                <span class="text-sm text-gray-500">Google</span>
            </button>

            <?php if (isset($_SESSION['registrasi_sukses'])): ?>
                <div id="modal" class="fixed inset-0 flex items-center justify-center z-50 w-full">
                    <div class="bg-white p-6 rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.3)] w-80 text-center">
                        <h1 class="text-lg font-semibold mb-4">Registrasi berhasil. Silahkan login ulang!</h1>
                        <div class="flex justify-center gap-4">
                            <button type="button"
                                class="px-4 py-2 bg-black text-white rounded hover:bg-gray-800"
                                onclick="window.location.href='login_user.php'">
                                Iya
                            </button>
                            <button type="button"
                                class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                                onclick="window.location.href='registrasi_user.php'">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['registrasi_sukses']); ?>
            <?php endif; ?>
        </form>
        <?php if ($from === 'homepage'):?>
            <a href="<?=$backUrl?>"
                class="block w-full text-center mt-3 py-2 border border-gray-400 rounded-lg text-gray-700 hover:bg-gray-100 transition font-medium">
                ← Kembali ke Homepage
            </a>
            <?php elseif (isset($kategori)):?>
                <a href="<?=$backUrl?>"
                    class="block w-full text-center mt-3 py-2 border border-gray-400 rounded-lg text-gray-700 hover:bg-gray-100 transition font-medium">
                    ← Kembali ke Kategori
                </a>
        <?php endif;?>
    </div>

</body>

</html>