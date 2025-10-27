<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // === path ===

function kirimEmailReset($to, $resetLink, $role = 'user') {
    $mail = new PHPMailer(true);

    try {
        // === Konfigurasi SMTP ===
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'brimobsport0713@gmail.com';
        $mail->Password   = 'hezbnarivymfddcn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // === Pengirim dan penerima ===
        $mail->setFrom('brimobsport0713@gmail.com', 'Brimob Sport');
        $mail->addAddress($to);

        // === Tentukan teks sapaan berdasarkan role ===
        $sapaan = ($role === 'admin')
            ? 'Halo, <strong>Admin Brimob Sport</strong>!'
            : 'Halo, <strong>Pengguna Brimob Sport</strong>!';

        // === Styling email elegan ===
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Brimob Sport';
        $mail->Body = "
        <div style='
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 40px 0;
            text-align: center;
        '>
            <div style='
                max-width: 500px;
                margin: auto;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                padding: 30px 25px;
            '>
                <h2 style='color: #111827; margin-bottom: 16px;'>🔒 Reset Password</h2>
                <p style='color: #374151; font-size: 15px; line-height: 1.6;'>
                    $sapaan<br><br>
                    Kami menerima permintaan untuk mengatur ulang password akun Anda.<br>
                    Silakan klik tombol di bawah ini untuk mengganti password baru.<br><br>
                </p>
                <a href='$resetLink' style='
                    display: inline-block;
                    background-color: #2563eb;
                    color: #ffffff;
                    text-decoration: none;
                    font-weight: bold;
                    padding: 12px 24px;
                    border-radius: 8px;
                    transition: background 0.3s ease;
                '>Atur Ulang Password</a>
                <p style='
                    color: #6b7280;
                    font-size: 13px;
                    margin-top: 24px;
                '>
                    Link ini hanya berlaku selama <strong>1 jam</strong>.<br>
                    Jika Anda tidak meminta reset password, abaikan email ini.
                </p>
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;'>
                <p style='color: #9ca3af; font-size: 12px;'>
                    © 2025 Brimob Sport. Semua Hak Dilindungi.
                </p>
            </div>
        </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Gagal kirim email: ' . $mail->ErrorInfo);
        return false;
    }
}
