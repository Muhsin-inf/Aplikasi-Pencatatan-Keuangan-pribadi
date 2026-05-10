<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; } </style>
</head>
<body class="antialiased text-gray-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-[32px] p-8 md:p-10 shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-gray-100">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-3xl mb-6">
                <i class="fas fa-key"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Lupa Password?</h2>
            <p class="text-gray-500 text-sm mb-6">Masukkan email yang terdaftar. Kami akan mengirimkan 6 digit kode OTP untuk mereset password Anda.</p>
            
            <div id="forgotError" class="hidden bg-red-50 text-red-600 text-sm font-bold p-4 rounded-xl mb-6 text-center border border-red-100"></div>

            <form id="forgotForm">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" required placeholder="nama@email.com" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3.5 outline-none focus:border-blue-500 transition-all font-medium">
                    </div>
                </div>
                <button type="submit" id="forgotBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-4 rounded-2xl shadow-lg transition-all active:scale-95">
                    Kirim Kode OTP
                </button>
            </form>

            <script>
                document.getElementById('forgotForm').addEventListener('submit', function(e) {
                    e.preventDefault(); // Mencegah reload
                    const btn = document.getElementById('forgotBtn');
                    const errBox = document.getElementById('forgotError');
                    
                    errBox.classList.add('hidden');
                    btn.disabled = true; 
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengirim...';

                    fetch('api/auth.php?action=forgot_password', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.href = 'verifikasi.php';
                        } else {
                            errBox.textContent = data.message;
                            errBox.classList.remove('hidden');
                            btn.disabled = false; 
                            btn.innerHTML = 'Kirim Kode OTP';
                        }
                    })
                    .catch(err => {
                        errBox.textContent = "Terjadi kesalahan jaringan.";
                        errBox.classList.remove('hidden');
                        btn.disabled = false; 
                        btn.innerHTML = 'Kirim Kode OTP';
                    });
                });
            </script>
            <div class="mt-6 text-center">
                <a href="login.php" class="text-sm font-bold text-gray-500 hover:text-blue-600"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Login</a>
            </div>
        </div>
    </div>
</body>
</html>