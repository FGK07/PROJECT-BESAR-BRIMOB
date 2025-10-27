<?php
session_start();
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'user';
    $email = trim($_POST['email']);
    $passwordBaru = trim($_POST['password']);
    $hashed = password_hash($passwordBaru, PASSWORD_DEFAULT);

    // Tentukan tabel berdasarkan role
    $emailColumn = 'email';

    //  Cek apakah email ada di tabel
    $stmt = $koneksi->prepare("SELECT id FROM users WHERE $emailColumn = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        //  Update password
        $stmt = $koneksi->prepare("UPDATE users SET password = ? WHERE $emailColumn = ?");
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            $_SESSION['flash'] = "✅ Password berhasil diperbarui. Silakan login kembali!";
        } else {
            $_SESSION['flash'] = "❌ Gagal memperbarui password.";
        }
        $stmt->close();
    } else {
        $_SESSION['flash'] = "⚠️ Email tidak ditemukan di database $role.";
    }

    header("Location: lupa_password.php?role=" . urlencode($role));
    exit;
}
?>
