<?php
require_once 'api/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data user saat ini
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, photo_url FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$photo_url = $user['photo_url'] ? $user['photo_url'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=2563eb&color=fff';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; } </style>
</head>
<body class="antialiased text-gray-800 pb-24 lg:pb-10">

    <div class="bg-white px-6 py-4 shadow-sm flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-gray-400 hover:text-blue-600 transition-colors"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="text-xl font-extrabold text-gray-900">Pengaturan Profil</h1>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 mt-8 space-y-6">
        
        <div id="profileAlert" class="hidden text-sm font-bold p-4 rounded-xl text-center border"></div>

        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 flex flex-col items-center">
            <div class="relative group cursor-pointer" onclick="openPhotoModal()">
                <img id="profileImagePreview" src="<?= $photo_url ?>" class="w-32 h-32 rounded-full object-cover border-4 border-blue-50 shadow-lg group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <i class="fas fa-search-plus text-white text-2xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4 font-bold">Ketuk untuk melihat/ganti foto</p>
            
            <form id="photoForm" class="hidden">
                <input type="file" id="photoInput" name="photo" accept="image/png, image/jpeg, image/webp" onchange="uploadPhoto()">
            </form>
        </div>

        <div id="photoModal" class="fixed inset-0 z-[200] hidden flex items-center justify-center bg-gray-900/80 backdrop-blur-sm transition-opacity opacity-0">
            <div class="bg-white rounded-[32px] p-8 max-w-sm w-full mx-4 flex flex-col items-center relative transform scale-95 transition-transform duration-300" id="photoModalContent">
                <button onclick="closePhotoModal()" class="absolute top-5 right-5 text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                <h3 class="text-xl font-extrabold mb-6 text-gray-900">Foto Profil</h3>
                <img id="largePhotoPreview" src="<?= $photo_url ?>" class="w-48 h-48 rounded-full object-cover shadow-2xl border-4 border-blue-50 mb-8">
                <button onclick="document.getElementById('photoInput').click()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-blue-500/30 transition-all active:scale-95 flex items-center justify-center gap-2">
                    <i class="fas fa-camera"></i> Ganti Foto (WebP)
                </button>
            </div>
        </div>

        <div id="loadingOverlay" class="fixed inset-0 z-[250] hidden flex flex-col items-center justify-center bg-slate-900/80 backdrop-blur-md text-white transition-opacity">
            <i class="fas fa-circle-notch fa-spin text-5xl mb-4 text-blue-400"></i>
            <h3 class="font-extrabold text-xl tracking-tight">Memproses Gambar...</h3>
            <p class="text-sm text-gray-300 mt-2">Mengkompres & Mengunggah (Max 2MB)</p>
        </div>

        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-gray-900 mb-6 border-b pb-4"><i class="fas fa-user-edit text-blue-600 mr-2"></i> Informasi Pribadi</h2>
            <form id="nameForm">
                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap</label>
                    <div class="flex gap-3">
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-medium">
                        <button type="submit" id="btnSaveName" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 rounded-xl transition-colors whitespace-nowrap">Simpan</button>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled class="w-full bg-gray-100 text-gray-500 border border-gray-200 rounded-xl px-4 py-3 font-medium cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-2"><i class="fas fa-info-circle"></i> Email terikat pada OTP, tidak dapat diubah.</p>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-gray-900 mb-6 border-b pb-4"><i class="fas fa-shield-alt text-emerald-600 mr-2"></i> Keamanan Akun</h2>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="font-bold text-gray-800">Password Akun</h3>
                    <p class="text-sm text-gray-500 mt-1">Amankan akun Anda dengan mengganti password secara berkala.</p>
                </div>
                <button type="button" id="btnChangePass" onclick="requestPasswordChange()" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white font-bold px-6 py-3 rounded-xl transition-colors whitespace-nowrap">
                    <i class="fas fa-key mr-1"></i> Ubah Password
                </button>
            </div>
        </div>

    </div> <?php include 'components/modal.php'; ?>

    <script>
        const alertBox = document.getElementById('profileAlert');

        function showNotification(msg, isSuccess = true) {
            if (isSuccess) {
                showModal('success', 'Berhasil!', msg);
            } else {
                showModal('error', 'Gagal', msg);
            }
        }

        // Modal Foto
        function openPhotoModal() {
            const modal = document.getElementById('photoModal');
            const content = document.getElementById('photoModalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            }, 10);
        }

        function closePhotoModal() {
            const modal = document.getElementById('photoModal');
            const content = document.getElementById('photoModalContent');
            modal.classList.add('opacity-0');
            content.classList.add('scale-95');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        // Upload & AJAX
        function uploadPhoto() {
            const fileInput = document.getElementById('photoInput');
            if (fileInput.files.length === 0) return;

            closePhotoModal();
            const loader = document.getElementById('loadingOverlay');
            loader.classList.remove('hidden');

            const formData = new FormData();
            formData.append('photo', fileInput.files[0]);

            fetch('api/profile.php?action=update_photo', {
                method: 'POST',
                body: formData
            }).then(res => res.json()).then(data => {
                loader.classList.add('hidden');
                if (data.status === 'success') {
                    const newUrl = data.photo_url + '?t=' + new Date().getTime();
                    document.getElementById('profileImagePreview').src = newUrl;
                    document.getElementById('largePhotoPreview').src = newUrl;
                    showNotification(data.message, true);
                } else {
                    showNotification(data.message, false);
                }
            }).catch(() => { 
                loader.classList.add('hidden');
                showNotification("Terjadi kesalahan jaringan/server saat memproses gambar.", false); 
            });
            fileInput.value = "";
        }

        // Update Nama
        document.getElementById('nameForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveName');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            fetch('api/profile.php?action=update_name', {
                method: 'POST',
                body: new FormData(this)
            }).then(res => res.json()).then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                showNotification(data.message, data.status === 'success');
            }).catch(() => { 
                btn.innerHTML = originalText; btn.disabled = false;
                showNotification("Kesalahan jaringan.", false); 
            });
        });

        // Request Ganti Password
        function requestPasswordChange() {
            showModal('warning', 'Ubah Password', 'Sistem akan mengirimkan kode OTP ke email Anda. Lanjutkan?', function() {
                const btn = document.getElementById('btnChangePass');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim OTP...';
                btn.disabled = true;
        
                fetch('api/auth.php?action=request_change_password', {
                    method: 'POST'
                }).then(res => res.json()).then(data => {
                    if (data.status === 'success') {
                        showModal('success', 'OTP Terkirim', 'Silakan cek kotak masuk email Anda.', function() {
                            window.location.href = 'verifikasi.php';
                        });
                    } else {
                        showModal('error', 'Gagal', data.message);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                }).catch(() => { 
                    showModal('error', 'Kesalahan', "Terjadi masalah jaringan.");
                    btn.innerHTML = originalText; btn.disabled = false;
                });
            });
        }
    </script>
</body>
</html>