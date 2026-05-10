<?php

require '../shared-mail/vendor/autoload.php';
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// $email = $_POST['email'] ?? '';
$email = 'santri23.pro@gmail.com';

if (!$email) {
    die("Email wajib diisi");
}

$otp = random_int(100000, 999999);

$expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));

$stmt = $conn->prepare("
    INSERT INTO email_otps(email, otp, expired_at)
    VALUES (?, ?, ?)
");

$stmt->bind_param("sss", $email, $otp, $expired);
$stmt->execute();

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();

    $mail->Host = 'smtp-relay.brevo.com';

    $mail->SMTPAuth = true;
    
    $mail->Username = 'aa9a9b001@smtp-brevo.com';
    
    $mail->Password = 'SZ1JzVpmCaxBnTcy';
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    
    $mail->Port = 587;

    $mail->setFrom(
    'muhsin230105@gmail.com',
    'SmartFinance'
    );

    $mail->addAddress($email);

    $mail->isHTML(true);

    $mail->Subject = 'Kode OTP Anda';

    $mail->Body = "
    <div style='
        max-width:600px;
        margin:auto;
        background:#ffffff;
        border-radius:12px;
        overflow:hidden;
        font-family:Arial;
        border:1px solid #ddd;
    '>
    
        <img 
            src='https://keuangan.muhsin.my.id/assets/img/banner-mail.png'
            style='width:100%;display:block;'
        >
    
        <div style='padding:30px;text-align:center;'>
    
            <h2 style='margin:0;color:#333;'>
                Verifikasi OTP
            </h2>
    
            <p style='color:#666;'>
                Gunakan kode berikut:
            </p>
    
            <div style='
                font-size:40px;
                font-weight:bold;
                letter-spacing:5px;
                margin:20px 0;
                color:#2b6cff;
            '>
                $otp
            </div>
    
            <p style='color:#999;font-size:14px;'>
                Berlaku selama 5 menit
            </p>
    
        </div>
    
    </div>
    ";

    $mail->send();

    echo "OTP berhasil dikirim";

} catch (Exception $e) {

    echo "Gagal kirim OTP";
}