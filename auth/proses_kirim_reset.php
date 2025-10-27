<?php
session_start();
include "../koneksi.php";
include "mailer.php"; // konfigurasi SMTP

date_default_timezone_set('Asia/Jakarta'); // biar waktu token sinkron

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $role = $_POST['role'] ?? 'user';

    // === 🔍 Cek apakah email ada ===
    $stmt = $koneksi->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        // === 🔒 Buat token unik ===
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // === berlaku 1 jam ===

        // === 💾 Simpan ke tabel password_resets ===
        $stmt2 = $koneksi->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt2->bind_param("sss", $email, $token_hash, $expires_at);
        $stmt2->execute();

        // === 🔗 Buat tautan reset ===
        $resetLink = "http://localhost/TOKO_ONLINE/auth/reset_password.php?token=$token&email=" . urlencode($email);

        // === 📧 Kirim email dengan sapaan berdasarkan role ===
        if (kirimEmailReset($email, $resetLink, $role)) {
            $_SESSION['flash'] = "📩 Link reset password telah dikirim ke email Anda ($role).";
        } else {
            $_SESSION['flash'] = "⚠️ Gagal mengirim email. Periksa pengaturan SMTP.";
        }
    } else {
        $_SESSION['flash'] = "⚠️ Email tidak ditemukan di database $role.";
    }

    header("Location: lupa_password.php?role=" . urlencode($role));
    exit;
}
?>
