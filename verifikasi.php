<?php
require_once 'api/config.php'; // Memuat config.php karena kita butuh koneksi DB untuk cek timer

if (!isset($_SESSION['otp_purpose'])) {
    header("Location: login.php");
    exit;
}
$purpose = $_SESSION['otp_purpose'];
$email_target = ($purpose === 'register') ? $_SESSION['temp_register']['email'] : $_SESSION['reset_email'];

// --- HITUNG WAKTU COOLDOWN DARI DATABASE ---
$cooldown_left = 0;
// Menghitung selisih detik dari OTP terakhir yang dikirim ke email tersebut
$stmt = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_passed FROM email_otps WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $email_target);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $passed = (int)$row['seconds_passed'];
    if ($passed < 60) {
        $cooldown_left = 60 - $passed; // Sisa waktu jika belum 1 menit
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; } </style>
</head>
<body class="antialiased text-gray-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-[32px] p-8 md:p-10 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-gray-100 text-center relative overflow-hidden">
            
            <div id="iconHeader" class="w-20 h-20 bg-gradient-to-br from-blue-600 to-cyan-400 rounded-full flex items-center justify-center text-white text-3xl mx-auto mb-6 shadow-lg shadow-blue-500/30 transition-all">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 id="pageTitle" class="text-2xl font-extrabold text-gray-900 mb-2">Verifikasi Kode OTP</h2>
            <p id="pageSubtitle" class="text-gray-500 text-sm mb-6">Kode 6 digit telah dikirim ke <span class="font-bold text-gray-800"><?= htmlspecialchars($email_target) ?></span>. Berlaku 5 menit.</p>
            
            <div id="alertBox" class="hidden text-sm font-bold p-4 rounded-xl mb-4 text-center border"></div>

            <form id="verifyForm" class="text-left">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2 text-center">Kode OTP</label>
                    <input type="text" name="otp" required maxlength="6" pattern="\d{6}" placeholder="• • • • • •" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-4 text-center text-2xl tracking-[0.5em] font-black outline-none focus:border-blue-500 transition-all">
                </div>
                <button type="submit" id="verifyBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-4 rounded-2xl shadow-lg transition-all active:scale-95">
                    Validasi OTP
                </button>
            </form>

            <form id="resetPasswordForm" class="text-left hidden">
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="text-sm font-bold text-gray-700 mb-2 block">Password Baru</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="new_password" required placeholder="Minimal 8 karakter" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3.5 outline-none focus:border-blue-500 font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-gray-700 mb-2 block">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <i class="fas fa-check-circle absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="confirm_new_password" required placeholder="Ulangi password baru" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3.5 outline-none focus:border-blue-500 font-medium">
                        </div>
                    </div>
                </div>
                <button type="submit" id="resetBtn" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-emerald-500/30 transition-all active:scale-95">
                    Simpan Password Baru
                </button>
            </form>

            <div id="resendSection" class="mt-6 text-sm text-gray-500 text-center">
                Tidak menerima kode? <br>
                <span id="resendContainer" class="hidden">
                    Tunggu <span id="timer" class="font-bold text-blue-600">0</span> detik
                </span>
                <button type="button" id="resendBtn" onclick="resendOTP()" class="hidden font-bold text-blue-600 hover:underline mt-1">Kirim Ulang OTP</button>
            </div>
        </div>
    </div>

    <script>
        const alertBox = document.getElementById('alertBox');
        
        function showAlert(msg, isSuccess = false) {
            alertBox.textContent = msg;
            alertBox.className = isSuccess 
                ? 'bg-emerald-50 text-emerald-600 text-sm font-bold p-4 rounded-xl mb-4 text-center border border-emerald-100'
                : 'bg-red-50 text-red-600 text-sm font-bold p-4 rounded-xl mb-4 text-center border border-red-100';
            alertBox.classList.remove('hidden');
        }

        // --- 1. LOGIKA TIMER BERDASARKAN DATABASE ---
        // PHP menginject nilai cooldown_left ke dalam Javascript
        let timeLeft = <?php echo $cooldown_left; ?>; 
        const timerEl = document.getElementById('timer');
        const resendContainer = document.getElementById('resendContainer');
        const resendBtn = document.getElementById('resendBtn');

        function startTimer() {
            if (timeLeft > 0) {
                resendContainer.classList.remove('hidden');
                resendBtn.classList.add('hidden');
                timerEl.textContent = timeLeft;
                
                const interval = setInterval(() => {
                    timeLeft--;
                    timerEl.textContent = timeLeft;
                    if (timeLeft <= 0) {
                        clearInterval(interval);
                        resendContainer.classList.add('hidden');
                        resendBtn.classList.remove('hidden');
                    }
                }, 1000);
            } else {
                resendContainer.classList.add('hidden');
                resendBtn.classList.remove('hidden');
            }
        }
        
        // Panggil timer saat halaman dibuka
        startTimer();

        function resendOTP() {
            // 1. Langsung matikan tombol agar tidak bisa diklik dobel!
            resendBtn.disabled = true; 
            resendBtn.innerHTML = 'Mengirim...';
            resendBtn.classList.add('opacity-50', 'cursor-not-allowed');

            fetch('api/auth.php?action=resend_otp')
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    showAlert(data.message, true);
                    timeLeft = 60; 
                    startTimer();
                } else {
                    showAlert(data.message, false);
                    // 2. Jika gagal/cooldown, nyalakan kembali tombolnya
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = 'Kirim Ulang OTP';
                    resendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });
        }

        // --- 2. AJAX FORM OTP (Tahap 1) ---
        document.getElementById('verifyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('verifyBtn');
            alertBox.classList.add('hidden');
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';

            fetch('api/auth.php?action=verify_otp', {
                method: 'POST',
                body: new FormData(this)
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') {
                    if (data.action === 'redirect') {
                        // Jika register, langsung masuk index
                        window.location.href = data.target; 
                    } else if (data.action === 'show_reset_form') {
                        // Jika Lupa Password, OTP benar, sembunyikan Form OTP, munculkan Form Reset
                        alertBox.classList.add('hidden');
                        document.getElementById('verifyForm').classList.add('hidden');
                        document.getElementById('resendSection').classList.add('hidden');
                        
                        document.getElementById('iconHeader').className = 'w-20 h-20 bg-gradient-to-br from-emerald-500 to-teal-400 rounded-full flex items-center justify-center text-white text-3xl mx-auto mb-6 shadow-lg';
                        document.getElementById('iconHeader').innerHTML = '<i class="fas fa-unlock-alt"></i>';
                        document.getElementById('pageTitle').textContent = 'Buat Password Baru';
                        document.getElementById('pageSubtitle').textContent = 'OTP Valid! Silakan buat password baru Anda.';
                        
                        document.getElementById('resetPasswordForm').classList.remove('hidden');
                    }
                } else {
                    showAlert(data.message, false);
                    btn.disabled = false; btn.innerHTML = 'Validasi OTP';
                }
            });
        });

        // --- 3. AJAX FORM PASSWORD BARU (Tahap 2) ---
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('resetBtn');
            alertBox.classList.add('hidden');
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            fetch('api/auth.php?action=reset_password', {
                method: 'POST',
                body: new FormData(this)
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') {
                    showAlert(data.message, true);
                    setTimeout(() => { window.location.href = data.target; }, 1500); // Tunggu 1.5 detik lalu ke login
                } else {
                    showAlert(data.message, false);
                    btn.disabled = false; btn.innerHTML = 'Simpan Password Baru';
                }
            });
        });
    </script>
</body>
</html>