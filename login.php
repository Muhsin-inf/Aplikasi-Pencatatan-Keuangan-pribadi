<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; }
        .auth-card { box-shadow: 0 20px 50px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="antialiased text-gray-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <div class="text-center mb-8 flex flex-col items-center">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-cyan-400 rounded-2xl flex items-center justify-center text-white text-3xl mb-4 shadow-lg shadow-blue-500/30">
                <i class="fas fa-wallet"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-700 to-cyan-500 tracking-tight">SmartFinance</h1>
            <p class="text-gray-500 font-medium mt-2">Kelola keuanganmu lebih cerdas.</p>
        </div>

        <div class="bg-white rounded-[32px] p-8 md:p-10 auth-card border border-gray-100">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Selamat Datang!</h2>
            
            <div id="errorMessage" class="hidden bg-red-50 text-red-600 text-sm font-bold p-4 rounded-xl mb-6 text-center border border-red-100"></div>
            
            <form id="loginForm">
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" required placeholder="nama@email.com" 
                                pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" 
                                title="Masukkan email dengan domain yang valid"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3.5 outline-none focus:border-blue-500 focus:bg-white transition-all font-medium">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-bold text-gray-700 mb-2 block">Password</label>
                        <div class="relative mb-3">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="password" id="passwordInput" name="password" required placeholder="••••••••" 
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-12 py-3.5 outline-none focus:border-blue-500 focus:bg-white transition-all font-medium">
                            
                            <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 focus:outline-none transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <label class="flex items-center text-sm font-medium text-gray-700 cursor-pointer group">
                                <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 transition-colors mr-2 cursor-pointer">
                                <span class="group-hover:text-blue-600 transition-colors">Ingat Saya</span>
                            </label>
                            <a href="lupa_password.php" class="text-xs font-bold text-blue-600 hover:underline">Lupa Password?</a>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-blue-500/30 transition-all transform hover:-translate-y-1 active:scale-95 mt-2 flex justify-center items-center">
                        Masuk Sekarang
                    </button>
                </div>
            </form>

            <script>
                // 1. Logika Mata (Show/Hide Password)
                const togglePassword = document.getElementById('togglePassword');
                const passwordInput = document.getElementById('passwordInput');

                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    // Ganti ikon coret / normal
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });

                // 2. Logika AJAX Form Login
                const loginForm = document.getElementById('loginForm');
                const errorMessage = document.getElementById('errorMessage');
                const submitBtn = document.getElementById('submitBtn');

                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // Mencegah halaman reload
                    
                    // Reset state
                    errorMessage.classList.add('hidden');
                    const originalBtnContent = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
                    submitBtn.disabled = true;

                    const formData = new FormData(this);

                    fetch('api/auth.php?action=login', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Jika berhasil, arahkan ke dashboard
                            window.location.href = 'index.php';
                        } else {
                            // Jika gagal, tampilkan error dan kembalikan tombol
                            errorMessage.textContent = data.message;
                            errorMessage.classList.remove('hidden');
                            submitBtn.innerHTML = originalBtnContent;
                            submitBtn.disabled = false;
                            
                            // Kosongkan kolom password saja, email biarkan utuh
                            passwordInput.value = '';
                            passwordInput.focus();
                        }
                    })
                    .catch(error => {
                        errorMessage.textContent = 'Terjadi kesalahan jaringan. Coba lagi.';
                        errorMessage.classList.remove('hidden');
                        submitBtn.innerHTML = originalBtnContent;
                        submitBtn.disabled = false;
                    });
                });
            </script>

            <div class="flex items-center my-8">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="px-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Atau</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            <button class="w-full bg-white border border-gray-200 text-gray-700 font-bold py-3.5 rounded-2xl flex items-center justify-center gap-3 hover:bg-gray-50 transition-colors">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/action/google.svg" class="w-5 h-5" alt="Google">
                Masuk dengan Google
            </button>
        </div>

        <p class="text-center mt-8 text-gray-500 font-medium">
            Belum punya akun? <a href="register.php" class="text-blue-600 font-bold hover:underline">Daftar Gratis</a>
        </p>
    </div>

</body>
</html>