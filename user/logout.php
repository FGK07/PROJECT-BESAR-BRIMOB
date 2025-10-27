<?php
// Mulai session di awal
session_start();

// Simpan dulu role sebelum hancurkan session
$role = $_GET['role'] ?? 'user';

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session (jika ada)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_regenerate_id(true); // Buat ID session baru agar bersih

// 🔄 Session baru untuk flash
$_SESSION['flash'] = ($role === 'admin')
    ? "✅ Admin berhasil logout!"
    : "✅ Berhasil logout!";

// Redirect sesuai role
$redirect = ($role === 'admin')
    ? '../admin/login_admin.php'
    : '../homepage.php';

// Tutup session dengan aman
session_write_close();

// Anti-cache dan redirect
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: $redirect");
exit;
