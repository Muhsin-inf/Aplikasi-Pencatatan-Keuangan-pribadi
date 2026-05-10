<?php

require 'config.php';

// $email = $_POST['email'] ?? '';
$email = 'santri23.pro@gmail.com';

$otp = '756388';
// $otp = $_POST['otp'] ?? '';

$stmt = $conn->prepare("
    SELECT * FROM email_otps
    WHERE email = ?
    AND otp = ?
    AND verified = 0
    AND expired_at > NOW()
    LIMIT 1
");

$stmt->bind_param("ss", $email, $otp);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $data = $result->fetch_assoc();

    $update = $conn->prepare("
        UPDATE email_otps
        SET verified = 1
        WHERE id = ?
    ");

    $update->bind_param("i", $data['id']);

    $update->execute();

    echo "OTP valid";

} else {

    echo "OTP salah atau expired";
}