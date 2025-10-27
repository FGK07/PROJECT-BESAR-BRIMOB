<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['user'])) {
    header('location: ../homepage.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

// Ambil data user
$stmt = $koneksi->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// rediret dinamis
if (isset($_GET['from'])) {
    if ($_GET['from'] === "homepage") {
        $_SESSION['backUrl'] = "../homepage.php";
    } elseif ($_GET['from'] === "kategori" && isset($_GET['kategori'])) {
        $_SESSION['backUrl'] = "../produk/kategori.php?nama=" . urlencode($_GET['kategori']);
    }
}
$backUrl = $_SESSION['backUrl'] ?? "../homepage.php"; // default

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $nomor_telepon = $_POST['no_telepon'];
    $alamat = $_POST['alamat'];
    $fotoBaru = $data['foto']; // default tetap foto lama

    // Cek apakah ada file foto diupload
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "../uploads/foto_user/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = "user_" . $user_id . "_" . time() . ".jpg";
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        // Validasi tipe file
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                // Simpan path relatif ke DB
                $fotoBaru = "uploads/foto_user/" . $fileName;

                // Hapus foto lama kalau bukan default & bukan dari Google
                if (!empty($data['foto']) && file_exists("../" . $data['foto']) && !str_contains($data['foto'], "googleusercontent")) {
                    unlink("../" . $data['foto']);
                }
            }
        }
    }

    // Update data user
    $stmt = $koneksi->prepare("UPDATE users SET nama = ?, email = ?, nomor_telepon = ?, alamat = ?, foto = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $nama, $email, $nomor_telepon, $alamat, $fotoBaru, $user_id);
    if ($stmt->execute()) {
        // Update session agar langsung sinkron
        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['alamat'] = $alamat;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['foto'] = $fotoBaru;
        $_SESSION['user']['nomor_telepon'] = $nomor_telepon;

        $_SESSION['flash'] = "Profil berhasil diperbarui!";
    }


    // Refresh data setelah update
    header("Location: edit_profil.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil <?= htmlspecialchars($data['nama']) ?></title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-50 font-inter">

    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flash"
            class="fixed top-4 left-1/2 -translate-x-1/2 bg-emerald-100 text-emerald-800 border border-emerald-300 px-6 py-3 rounded-lg font-medium shadow-md z-50">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Tombol kembali -->
    <div class="absolute top-6 left-6">
        <a href="<?= htmlspecialchars($backUrl) ?>"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium rounded-lg shadow-md transition-all w-full">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Kartu Edit Profil -->
    <div class="w-[420px] bg-white rounded-2xl shadow-[0_4px_25px_rgba(0,0,0,0.1)] border border-gray-200 p-8">

        <h1 class="text-center font-bold text-3xl text-gray-800 mb-8 tracking-wide">Edit Profil</h1>

        <form method="post" enctype="multipart/form-data" class="space-y-5">
            <div class="flex flex-col items-center mb-6">
                <?php
                $fotoPath = !empty($data['foto']) && file_exists("../" . $data['foto'])
                    ? "../" . htmlspecialchars($data['foto'])
                    : "../img/default_user.png"; // fallback jika belum ada foto
                ?>

                <div class="relative w-32 h-32">
                    <!-- Foto profil -->
                    <img src="<?= $fotoPath ?>"
                        alt="Foto Profil"
                        class="w-32 h-32 rounded-full object-cover border border-gray-300 shadow-md bg-gray-100">

                    <!-- Tombol edit kecil di pojok bawah kanan -->
                    <label
                        class="absolute bottom-2 right-2 w-8 h-8 bg-gray-800 hover:bg-gray-900 text-white rounded-full flex items-center justify-center shadow-md cursor-pointer transition-all duration-300">
                        <i class="fa-solid fa-camera text-sm"></i>
                        <input type="file" name="foto" accept="image/*" class="hidden">
                    </label>
                </div>

                <p class="text-xs text-gray-500 mt-4">Format: JPG, JPEG, PNG</p>
            </div>
            
            <!-- Nama -->
            <div>
                <label for="nama" class="block font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 outline-none transition-all">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block font-semibold text-gray-700 mb-1">Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($data['email']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 outline-none transition-all">
            </div>

            <!-- Nomor Telepon -->
            <div>
                <label for="no_telepon" class="block font-semibold text-gray-700 mb-1">Nomor Telepon</label>
                <input type="text" name="no_telepon" value="<?= htmlspecialchars($data['nomor_telepon']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 outline-none transition-all">
            </div>

            <!-- Alamat -->
            <div>
                <label for="alamat" class="block font-semibold text-gray-700 mb-1">Alamat</label>
                <input type="text" name="alamat" value="<?= htmlspecialchars($data['alamat']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 outline-none transition-all">
            </div>

            <!-- Tombol -->
            <div class="pt-4">
                <button type="submit"
                    class="w-full py-3 bg-black hover:bg-gray-800 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 cursor-pointer">
                    <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <script>
        // Animasi flash
        setTimeout(() => {
            const flash = document.getElementById('flash');
            if (flash) {
                flash.style.opacity = "0";
                flash.style.transition = "opacity 0.5s ease";
                setTimeout(() => flash.remove(), 600);
            }
        }, 3000);
    </script>
</body>

</html>