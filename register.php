<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - SmartFinance</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F4F7FE;
        }

        .auth-card {
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
        }

        .input-error {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }

        .input-success {
            border-color: #22c55e !important;
            background-color: #f0fdf4 !important;
        }
    </style>
</head>

<body class="antialiased text-gray-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full py-10">

        <div class="text-center mb-8 flex flex-col items-center">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-cyan-400 rounded-2xl flex items-center justify-center text-white text-3xl mb-4 shadow-lg shadow-blue-500/30">
                <i class="fas fa-wallet"></i>
            </div>

            <h1 class="text-3xl md:text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-700 to-cyan-500 tracking-tight mb-1">
                Buat Akun Baru
            </h1>

            <p class="text-gray-500 font-medium mt-1">
                Mulai kelola keuanganmu sekarang juga.
            </p>
        </div>

        <div class="bg-white rounded-[32px] p-8 md:p-10 auth-card border border-gray-100">

            <form action="api/auth.php?action=register_step1" method="POST" id="registerForm" novalidate>

                <div class="space-y-5">

                    <!-- Nama -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Nama Lengkap
                        </label>

                        <div class="relative">
                            <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            <input 
                                type="text"
                                name="name"
                                id="name"
                                required
                                minlength="3"
                                autocomplete="name"
                                placeholder="Masukkan nama lengkap"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3.5 outline-none focus:border-blue-500 focus:bg-white transition-all font-medium"
                            >
                        </div>

                        <p id="nameError" class="text-red-500 text-sm mt-2 hidden">
                            Nama minimal 3 karakter
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            Alamat Email
                        </label>

                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            <input 
                                type="email"
                                name="email"
                                id="email"
                                required
                                autocomplete="email"
                                placeholder="nama@email.com"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3.5 outline-none focus:border-blue-500 focus:bg-white transition-all font-medium"
                            >
                        </div>

                        <p id="emailError" class="text-red-500 text-sm mt-2 hidden">
                            Format email tidak valid
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="text-sm font-bold text-gray-700 mb-2 block">
                            Password
                        </label>

                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            <input 
                                type="password"
                                name="password"
                                id="password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-12 py-3.5 outline-none focus:border-blue-500 focus:bg-white transition-all font-medium password-input"
                            >

                            <button 
                                type="button"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition toggle-password"
                                aria-label="Lihat password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        <p id="passwordError" class="text-red-500 text-sm mt-2 hidden">
                            Password minimal 8 karakter
                        </p>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div>
                        <label class="text-sm font-bold text-gray-700 mb-2 block">
                            Konfirmasi Password
                        </label>

                        <div class="relative">
                            <i class="fas fa-check-circle absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            <input 
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                required
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Ulangi password di atas"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-12 py-3.5 outline-none focus:border-blue-500 focus:bg-white transition-all font-medium password-input"
                            >

                            <button 
                                type="button"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition toggle-password"
                                aria-label="Lihat password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        <p id="confirmPasswordError" class="text-red-500 text-sm mt-2 hidden">
                            Konfirmasi password tidak sama
                        </p>
                    </div>

                    <!-- Tombol -->
                    <button 
                        type="submit"
                        id="submitBtn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-blue-500/30 transition-all transform hover:-translate-y-1 active:scale-95"
                    >
                        Lanjut Verifikasi Email
                    </button>

                </div>
            </form>
        </div>

        <p class="text-center mt-8 text-gray-500 font-medium">
            Sudah punya akun?
            <a href="login.php" class="text-blue-600 font-bold hover:underline">
                Masuk di sini
            </a>
        </p>
    </div>

    <script>

        // Toggle show/hide password
        document.querySelectorAll('.toggle-password').forEach(button => {

            button.addEventListener('click', function () {

                const input = this.parentElement.querySelector('.password-input');
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';

                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');

                } else {

                    input.type = 'password';

                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });


        // Form validation
        const form = document.getElementById('registerForm');

        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        function setError(input, errorId) {

            input.classList.add('input-error');
            input.classList.remove('input-success');

            document.getElementById(errorId).classList.remove('hidden');
        }

        function setSuccess(input, errorId) {

            input.classList.remove('input-error');
            input.classList.add('input-success');

            document.getElementById(errorId).classList.add('hidden');
        }

        function validateName() {

            const value = nameInput.value.trim();

            if (value.length < 3) {
                setError(nameInput, 'nameError');
                return false;
            }

            setSuccess(nameInput, 'nameError');
            return true;
        }

        function validateEmail() {

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(emailInput.value.trim())) {
                setError(emailInput, 'emailError');
                return false;
            }

            setSuccess(emailInput, 'emailError');
            return true;
        }

        function validatePassword() {

            if (passwordInput.value.length < 8) {
                setError(passwordInput, 'passwordError');
                return false;
            }

            setSuccess(passwordInput, 'passwordError');
            return true;
        }

        function validateConfirmPassword() {

            if (
                confirmPasswordInput.value !== passwordInput.value ||
                confirmPasswordInput.value === ''
            ) {

                setError(confirmPasswordInput, 'confirmPasswordError');
                return false;
            }

            setSuccess(confirmPasswordInput, 'confirmPasswordError');
            return true;
        }

        // realtime validation
        nameInput.addEventListener('input', validateName);

        emailInput.addEventListener('input', validateEmail);

        passwordInput.addEventListener('input', () => {
            validatePassword();
            validateConfirmPassword();
        });

        confirmPasswordInput.addEventListener('input', validateConfirmPassword);

        // submit validation
        // submit validation & AJAX Request
        form.addEventListener('submit', function (e) {
            
            // 1. Wajib hentikan aksi default form agar halaman TIDAK reload
            e.preventDefault(); 

            const isNameValid = validateName();
            const isEmailValid = validateEmail();
            const isPasswordValid = validatePassword();
            const isConfirmPasswordValid = validateConfirmPassword();

            // 2. Jika ada yang tidak valid secara format, hentikan proses
            if (!isNameValid || !isEmailValid || !isPasswordValid || !isConfirmPasswordValid) {
                return; 
            }

            // 3. Jika format valid, mulai proses AJAX ke server
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            
            // Ubah tombol jadi loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';

            fetch('api/auth.php?action=register_step1', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(res => res.json()) // Mengambil respon JSON dari server
            .then(data => {
                if (data.status === 'success') {
                    // Jika sukses, arahkan ke halaman verifikasi OTP
                    window.location.href = 'verifikasi.php';
                } else {
                    // Jika gagal (Misal: Email sudah terdaftar)
                    // Tampilkan pesan error di input email
                    setError(emailInput, 'emailError');
                    document.getElementById('emailError').textContent = data.message;
                    document.getElementById('emailError').classList.remove('hidden');
                    
                    // Kembalikan tombol seperti semula
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                alert("Kesalahan jaringan. Silakan coba lagi.");
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });

    </script>

</body>
</html>