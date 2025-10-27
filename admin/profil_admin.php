<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) {
    header('location: dashboard_admin.php');
    exit;
}
// ambil data admin
$admin_id = $_SESSION['admin']['id'];

$stmt = $koneksi->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?= htmlspecialchars($data['nama']) ?></title>
    <link rel="stylesheet" href="../src/output.css">
</head>

<body class="relative min-h-screen flex items-center justify-center bg-white">
    <?php
    $backUrl = "dashboard_admin.php"; // default

    if (isset($_GET['from'])) {
        if ($_GET['from'] === "homepage") {
            $backUrl = "dashboard_admin.php";
        } elseif ($_GET['from'] === "kategori" && isset($_GET['kategori'])) {
            $backUrl = "../produk/kategori.php?nama=" . urlencode($_GET['kategori']);
        }
    }
    ?>
    <div class="fixed top-4 left-4 py-2 px-8 z-[900]">
        <a href="<?=htmlspecialchars($backUrl)?>"
            class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600">← Kembali</a>
    </div>
    <div class="w-[400px] border rounded-lg  p-8 shadow-[0_0_10px_rgba(0,0,0,0.3)] bg-white ">
        <h1 class="text-center font-bold font-inter text-2xl mb-8">PROFIL</h1>

        <div class="flex flex-col items-center mb-8">
            <img src="../img/<?= htmlspecialchars($data['foto']) ?>"
                alt="Foto Profil"
                class="w-32 h-32 rounded-full border object-cover border-none" />
            <input value="<?= htmlspecialchars($data['nama']) ?>" class="mt-4 text-lg font-semibold text-center outline-none" readonly>
        </div>

        <div class="space-y-4">
            <div class="">
                <label for="email" class="font-semibold block mb-1">Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($data['email']) ?>" class="border rounded px-3 py-2 bg-gray-50 w-full" readonly>
            </div>

            <div>
                <label for="no_telepon" class="font-semibold block mb-1">NO. Telp</label>
                <input type="text" name="no_telepon" value="<?= htmlspecialchars($data['nomor_telepon']) ?>" class="border rounded px-3 py-2 bg-gray-50 w-full" readonly>
            </div>

            <div>
                <label for="alamat" class="font-semibold block mb-1">Alamat</label>
                <input type="text" name="alamat" value="<?= htmlspecialchars($data['alamat']) ?>" class="border rounded px-3 py-2 bg-gray-50 w-full" readonly>
            </div>
            <div class="flex items-center justify-center">
                <a href="edit_profil_admin.php" class="px-6 py-3 bg-black text-white rounded-lg hover:bg-gray-800 cursor-pointer">
                    EDIT
                </a>
            </div>
        </div>
    </div>
</body>

</html>