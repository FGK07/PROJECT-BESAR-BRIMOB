<?php
session_start();
require 'config.php';
require 'koneksi.php';

/**
 * Fungsi untuk download foto dengan cURL (lebih stabil daripada file_get_contents)
 */
function downloadFoto($url, $savePath)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // biar Google gak blokir

    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $data !== false) {
        file_put_contents($savePath, $data);
        return true;
    }
    return false;
}

if (isset($_GET['code'])) {
    // Ambil access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        die("Error ambil token: " . $token['error_description']);
    }
    $client->setAccessToken($token);

    // Ambil info user dari Google
    $oauth = new Google\Service\Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $googleId = $userInfo->id;
    $nama     = $userInfo->name;
    $email    = $userInfo->email;
    $fotoUrl  = $userInfo->picture;

    // Ganti ukuran default (96px) jadi lebih besar (400px)
    $fotoUrl = str_replace("s96-c", "s800-c", $fotoUrl);

    // Simpan foto ke server lokal
    $namaFile = "user_" . $googleId . ".jpg";
    $pathFoto = "uploads/foto_user/" . $namaFile;
    $fullPath = __DIR__ . "/" . $pathFoto; // path absolut

    // Pastikan folder ada
    if (!is_dir(__DIR__ . "/uploads/foto_user")) {
        mkdir(__DIR__ . "/uploads/foto_user", 0777, true);
    }

    // Download dengan cURL
    if (!downloadFoto($fotoUrl, $fullPath)) {
        $pathFoto = "uploads/foto_user/default.png"; // fallback kalau gagal
    }

    // Cek user di database
    $stmt = $koneksi->prepare("SELECT id, nama, foto, email, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User sudah ada → update foto kalau berubah
        $user = $result->fetch_assoc();

        // 🔹 Cegah penimpaan foto manual
        if (
            empty($user['foto']) || // belum punya foto sama sekali
            str_contains($user['foto'], 'googleusercontent') || // foto lama masih dari google
            str_contains($user['foto'], 'default.png') // masih foto default
        ) {
            // hanya update kalau memang masih pakai foto google atau default
            if ($user['foto'] != $pathFoto) {
                $stmtUpdate = $koneksi->prepare("UPDATE users SET foto = ? WHERE id = ?");
                $stmtUpdate->bind_param("si", $pathFoto, $user['id']);
                $stmtUpdate->execute();
            }
        } else {
            // kalau user sudah pernah upload foto manual → jangan diubah
            $pathFoto = $user['foto'];
        }


        $_SESSION['user'] = [
            'id'    => $user['id'],
            'nama'  => $user['nama'],
            'email' => $user['email'],
            'role'  => $user['role'],
            'foto'  => $pathFoto
        ];
    } else {
        // User baru → insert ke DB
        $stmtInsert = $koneksi->prepare("INSERT INTO users (nama, oauth_uid, foto, email, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())");
        $stmtInsert->bind_param("ssss", $nama, $googleId, $pathFoto, $email);
        $stmtInsert->execute();
        $newId = $stmtInsert->insert_id;

        $_SESSION['user'] = [
            'id'    => $newId,
            'nama'  => $nama,
            'email' => $email,
            'foto'  => $pathFoto,
            'role'  => 'user'
        ];
    }

    // Flash message selamat datang
    $_SESSION['flash'] = "Selamat datang $nama!";
    header('Location: homepage.php');
    exit;
} else {
    echo "❌ Login Google gagal!";
}
