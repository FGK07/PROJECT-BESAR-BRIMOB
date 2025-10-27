<?php
session_start();
include "../koneksi.php";

// === Pastikan admin login ===
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// === Validasi ID user ===
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = "❌ ID user tidak valid!";
    header("Location: kelola_user.php");
    exit;
}

// === Cek apakah user ada ===
$stmt = $koneksi->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result->fetch_assoc()) {
    $_SESSION['flash'] = "❌ User tidak ditemukan!";
    header("Location: kelola_user.php");
    exit;
}
$stmt->close();

// === Hapus user ===
$stmt = $koneksi->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

// === Tentukan pesan ===
$_SESSION['flash'] = $success ? "✅ User berhasil dihapus!" : "❌ Gagal menghapus user!";

// === Jika ada parameter "from", tentukan arah balik ===
if (isset($_GET['from'])) {
    switch ($_GET['from']) {
        case "kelola_user":
            // === Kalau dihapus dari halaman kelola user ===
            $redirect = "kelola_user.php";
            break;

        case "detail_user":
            // === Kalau dihapus dari halaman detail user → kembali ke kelola user ===
            $redirect = $_SESSION['kelolaUserBack'] ?? "kelola_user.php";
            break;

        case "kategori":
            // === Kalau dipicu dari kategori tertentu ===
            $kategori = urlencode($_GET['kategori'] ?? '');
            $redirect = "kelola_user.php?from=kategori&kategori=$kategori";
            break;

        default:
            // === Default fallback ===
            $redirect = "kelola_user.php";
    }
} else {
    // === Kalau tidak ada parameter from, fallback ke kelola user ===
    $redirect = "kelola_user.php";
}

// === Redirect ke halaman yang sesuai ===
header("Location: $redirect");
exit;
?>