<?php
// Set Header JSON di paling atas
header('Content-Type: application/json');

require_once 'config.php'; 
require_once '../shared-mail/vendor/autoload.php';

// ==========================================
// 1. BLOKIR AKSES KOSONG (TANPA PARAMETER)
// Jika diketik langsung: https://keuangan.muhsin.my.id/api/auth.php
// ==========================================
if (!isset($_GET['action'])) {
    // Lempar kembali ke halaman utama
    header("Location: ../403");
    exit;
}

// ==========================================
// 2. PEMBATASAN METODE HTTP (METHOD RESTRICTION)
// Daftar aksi yang WAJIB menggunakan metode POST
// ==========================================
$post_actions = ['login', 'register_step1', 'forgot_password', 'verify_otp', 'reset_password','request_change_password'];

// Jika aksi butuh POST, tapi diakses menggunakan GET (lewat URL browser)
if (in_array($_GET['action'], $post_actions) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Lempar mereka ke halaman login seolah-olah tidak terjadi apa-apa
    header("Location: ../login.php");
    exit;
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmailOTP($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aa9a9b001@smtp-brevo.com';
        $mail->Password = 'SZ1JzVpmCaxBnTcy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('muhsin230105@gmail.com', 'SmartFinance Security');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP Anda - SmartFinance';

        $mail->Body = "
        <div style='max-width:600px; margin:auto; border:1px solid #eee; border-radius:12px; overflow:hidden; font-family:sans-serif;'>
            <img src='https://keuangan.muhsin.my.id/assets/img/banner-mail.png' style='width:100%; display:block;'>
            <div style='padding:30px; text-align:center;'>
                <h2 style='color:#1e40af; margin-top:0;'>Verifikasi Kode OTP</h2>
                <p style='color:#666;'>Gunakan kode berikut untuk melanjutkan proses Anda:</p>
                <div style='font-size:36px; font-weight:bold; color:#2563eb; letter-spacing:5px; margin:20px 0;'>$otp</div>
                <p style='font-size:12px; color:#999;'>Kode ini berlaku selama 5 menit. Jika bukan Anda yang meminta, abaikan email ini.</p>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) { 
        return false; 
    }
}

// ==========================================
// FUNGSI ANTI-SPAM: Cek Cooldown 60 Detik di Database
// ==========================================
function getCooldownTime($conn, $email) {
    $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_passed FROM email_otps WHERE email = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $passed = (int)$res->fetch_assoc()['seconds_passed'];
        if ($passed < 60) {
            return 60 - $passed; // Mengembalikan sisa detik
        }
    }
    return 0; // Aman, sudah lebih dari 60 detik
}


if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // --- 1. LOGIN ---
    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                if (isset($_POST['remember'])) {
                    setcookie('smartfinance_remember', base64_encode($user['id'] . '::' . $user['password']), time() + (86400 * 30), "/");
                }
                echo json_encode(["status" => "success"]);
            } else { echo json_encode(["status" => "error", "message" => "Password salah!"]); }
        } else { echo json_encode(["status" => "error", "message" => "Email tidak terdaftar!"]); }
    }

    // --- 2. REGISTER STEP 1 (CEK EMAIL & KIRIM OTP) ---
    elseif ($action === 'register_step1') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        // --- CEK SPAM BACKEND ---
        $cooldown = getCooldownTime($conn, $email);
        if ($cooldown > 0) {
            echo json_encode(["status" => "error", "message" => "Terlalu banyak permintaan. Tunggu $cooldown detik."]);
            exit;
        }
        // ------------------------
        $pass = $_POST['password'];
        $conf = $_POST['confirm_password'];

        if ($pass !== $conf) { echo json_encode(["status" => "error", "message" => "Konfirmasi password tidak cocok!"]); exit; }

        $cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) { echo json_encode(["status" => "error", "message" => "Email ini sudah terdaftar!"]); exit; }

        $otp = random_int(100000, 999999);
        $expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = $conn->prepare("INSERT INTO email_otps (email, otp, expired_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $otp, $expired);
        
        if ($stmt->execute() && sendEmailOTP($email, $otp)) {
            $_SESSION['temp_register'] = ['name' => $name, 'email' => $email, 'password' => password_hash($pass, PASSWORD_DEFAULT)];
            $_SESSION['otp_purpose'] = 'register';
            $_SESSION['last_otp_email'] = $email;
            echo json_encode(["status" => "success"]);
        } else { echo json_encode(["status" => "error", "message" => "Gagal mengirim OTP."]); }
    }

    // --- 3. FORGOT PASSWORD (CEK EMAIL & KIRIM OTP) ---
    elseif ($action === 'forgot_password') {
        $email = trim($_POST['email']);
        // --- CEK SPAM BACKEND ---
        $cooldown = getCooldownTime($conn, $email);
        if ($cooldown > 0) {
            echo json_encode(["status" => "error", "message" => "Terlalu banyak permintaan. Tunggu $cooldown detik."]);
            exit;
        }
        // ------------------------
        $cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        if ($cek->get_result()->num_rows === 0) { echo json_encode(["status" => "error", "message" => "Email tidak ditemukan!"]); exit; }

        $otp = random_int(100000, 999999);
        $expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = $conn->prepare("INSERT INTO email_otps (email, otp, expired_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $otp, $expired);
        
        if ($stmt->execute() && sendEmailOTP($email, $otp)) {
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp_purpose'] = 'reset';
            $_SESSION['last_otp_email'] = $email;
            echo json_encode(["status" => "success"]);
        } else { echo json_encode(["status" => "error", "message" => "Gagal mengirim OTP."]); }
    }

// --- 4. VERIFY OTP (Hanya Memvalidasi OTP) ---
    elseif ($action === 'verify_otp') {
        $otp = trim($_POST['otp']);
        $purpose = $_SESSION['otp_purpose'];
        $email = ($purpose === 'register') ? $_SESSION['temp_register']['email'] : $_SESSION['reset_email'];

        $stmt = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND otp = ? AND verified = 0 AND expired_at > NOW() LIMIT 1");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $otp_id = $res->fetch_assoc()['id'];
            $conn->query("UPDATE email_otps SET verified = 1 WHERE id = $otp_id");

            if ($purpose === 'register') {
                $u = $_SESSION['temp_register'];
                $ins = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $ins->bind_param("sss", $u['name'], $u['email'], $u['password']);
                $ins->execute();
                
                $new_id = $conn->insert_id;

                // --- TAMBAHAN: BUAT DOMPET DEFAULT ---
                $conn->query("INSERT INTO wallets (user_id, name, icon_name, color) VALUES ($new_id, 'Uang Tunai', 'wallet', '#3B82F6')");

                // --- TAMBAHAN: BUAT KATEGORI DEFAULT ---
                // Kita buat kategori 'Hutang' khusus agar bisa dipakai di api/debt.php nanti
                $default_categories = [
                    ['Gaji', 'income', 'cash-outline'],
                    ['Hutang & Pinjaman', 'income', 'card-outline'], // Ini yang akan kita pakai
                    ['Makanan', 'expense', 'fast-food-outline'],
                    ['Transportasi', 'expense', 'bus-outline'],
                    ['Belanja', 'expense', 'cart-outline']
                ];

                $stmt_cat = $conn->prepare("INSERT INTO categories (user_id, name, type, icon_name) VALUES (?, ?, ?, ?)");
                foreach ($default_categories as $cat) {
                    $stmt_cat->bind_param("isss", $new_id, $cat[0], $cat[1], $cat[2]);
                    $stmt_cat->execute();
                }

                // Auto Login
                $_SESSION['user_id'] = $new_id;
                $_SESSION['user_name'] = $u['name'];
                
                unset($_SESSION['temp_register'], $_SESSION['otp_purpose'], $_SESSION['last_otp_email']);
                echo json_encode(["status" => "success", "action" => "redirect", "target" => "index.php"]);
            }
        } else { 
            echo json_encode(["status" => "error", "message" => "Kode OTP salah atau sudah kadaluarsa!"]); 
        }
    }

    // --- 5. EKSEKUSI RESET PASSWORD (Setelah OTP Valid) ---
    elseif ($action === 'reset_password') {
        if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_email'])) {
            echo json_encode(["status" => "error", "message" => "Sesi verifikasi tidak valid!"]); 
            exit;
        }

        $email = $_SESSION['reset_email'];
        $new_pass = $_POST['new_password'];
        $confirm = $_POST['confirm_new_password'];

        if ($new_pass !== $confirm) {
            echo json_encode(["status" => "error", "message" => "Konfirmasi password baru tidak cocok!"]);
            exit;
        }

        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $upd->bind_param("ss", $hash, $email);
        $upd->execute();

        // Bersihkan sesi
        unset($_SESSION['reset_email'], $_SESSION['otp_purpose'], $_SESSION['reset_verified'], $_SESSION['last_otp_email']);
        echo json_encode(["status" => "success", "message" => "Password berhasil direset!", "target" => "login.php"]);
    }

    // --- 6. RESEND OTP ---
    elseif ($action === 'resend_otp') {
        $email = $_SESSION['last_otp_email'];
        
        // --- CEK SPAM BACKEND ---
        $cooldown = getCooldownTime($conn, $email);
        if ($cooldown > 0) {
            echo json_encode(["status" => "error", "message" => "Tunggu $cooldown detik lagi sebelum mengirim ulang."]);
            exit;
        }
        
        $otp = random_int(100000, 999999);
        $expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = $conn->prepare("INSERT INTO email_otps (email, otp, expired_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $otp, $expired);
        if ($stmt->execute() && sendEmailOTP($email, $otp)) {
            echo json_encode(["status" => "success", "message" => "Kode OTP baru telah dikirim!"]);
        } else { echo json_encode(["status" => "error", "message" => "Gagal mengirim ulang OTP."]); }
    }
    // --- 6. MINTA OTP UNTUK UBAH PASSWORD (DARI HALAMAN PROFIL) ---
    elseif ($action === 'request_change_password') {
        if (!isset($_SESSION['user_id'])) exit;
        
        // Ambil email user yang sedang login
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $email = $stmt->get_result()->fetch_assoc()['email'];

        // Cek Anti-Spam
        $cooldown = getCooldownTime($conn, $email);
        if ($cooldown > 0) {
            echo json_encode(["status" => "error", "message" => "Tunggu $cooldown detik lagi sebelum meminta OTP."]);
            exit;
        }

        $otp = random_int(100000, 999999);
        $expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = $conn->prepare("INSERT INTO email_otps (email, otp, expired_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $otp, $expired);
        
        if ($stmt->execute() && sendEmailOTP($email, $otp)) {
            // Kita gunakan purpose 'reset' agar verifikasi.php menampilkan form password baru
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp_purpose'] = 'reset';
            $_SESSION['last_otp_email'] = $email;
            echo json_encode(["status" => "success"]);
        } else { 
            echo json_encode(["status" => "error", "message" => "Gagal mengirim OTP ke email Anda."]); 
        }
    }
}