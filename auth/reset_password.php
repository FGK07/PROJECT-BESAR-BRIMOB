<?php
include "../koneksi.php";
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="../src/output.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <form action="proses_reset_password.php" method="POST"
        class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md space-y-4">
    <h1 class="text-xl font-bold text-center">🔒 Ganti Password</h1>
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <div>
      <label class="block text-gray-700 mb-1">Password Baru</label>
      <input type="password" name="password" required minlength="6" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-400">
    </div>

    <button type="submit" class="w-full bg-black text-white py-2 rounded-lg font-semibold">
      Simpan Password
    </button>
  </form>
</body>
</html>
