<?php
session_start();
include "../koneksi.php";
date_default_timezone_set('Asia/Jakarta'); // === penting biar waktu sinkron ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $token = $_POST['token'];
    $newPassword = $_POST['password'];

    // === 🔥 Ubah token jadi hash SHA-256 sebelum dicek ===
    $token_hash = hash('sha256', $token);

    // === Ambil record berdasarkan email + token_hash ===
    $stmt = $koneksi->prepare("SELECT * FROM password_resets WHERE email=? AND token_hash=? AND used=0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // === Token valid, ubah password user ===
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $koneksi->prepare("UPDATE users SET password=? WHERE email=?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();

        // === Tandai token sebagai used ===
        $use = $koneksi->prepare("UPDATE password_resets SET used=1 WHERE id=?");
        $use->bind_param("i", $row['id']);
        $use->execute();

        // === ✅ Tentukan redirect berdasarkan role pengguna ===
        $role = '';
        $cekRole = $koneksi->prepare("SELECT role FROM users WHERE email=?");
        $cekRole->bind_param("s", $email);
        $cekRole->execute();
        $resRole = $cekRole->get_result();
        if ($dataRole = $resRole->fetch_assoc()) {
            $role = $dataRole['role'];
        }

        $_SESSION['flash'] = "✅ Password berhasil diubah. Silakan login kembali.";

        if ($role === 'admin') {
            header("Location: ../admin/login_admin.php");
        } else {
            header("Location: ../user/login_user.php");
        }
        exit;
    } else {
        $_SESSION['flash'] = "❌ Link reset tidak valid atau sudah kedaluwarsa.";
        header("Location: lupa_password.php");
        exit;
    }
}
?>
