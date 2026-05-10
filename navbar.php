<?php
// Deteksi halaman aktif secara dinamis
$current_page = basename($_SERVER['PHP_SELF'], ".php");
if ($current_page == '' || $current_page == 'index.php') {
    $current_page = 'index';
}

// Ambil data user dari database secara dinamis
$nav_user_name = "Guest";
$nav_photo_url = "https://ui-avatars.com/api/?name=Guest&background=2563eb&color=fff";
$nav_user_role = "Member Baru";

if (isset($_SESSION['user_id'])) {
    global $conn; // Menggunakan koneksi dari config.php
    $user_id = $_SESSION['user_id'];
    
    $stmt_nav = $conn->prepare("SELECT name, photo_url, created_at FROM users WHERE id = ?");
    $stmt_nav->bind_param("i", $user_id);
    $stmt_nav->execute();
    $res_nav = $stmt_nav->get_result();
    
    if ($res_nav->num_rows > 0) {
        $user_data = $res_nav->fetch_assoc();
        $nav_user_name = $user_data['name'];
        
        // Cek apakah punya foto custom, jika tidak pakai UI-Avatars
        if (!empty($user_data['photo_url'])) {
            $nav_photo_url = $user_data['photo_url'];
        } else {
            $nav_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($nav_user_name) . '&background=2563eb&color=fff&bold=true';
        }

        // Tentukan role/label simpel berdasarkan tahun gabung
        $join_date = date('d F Y', strtotime($user_data['created_at']));
        $nav_user_role = $join_date;
    }
    $stmt_nav->close();
}
?>

<nav class="bg-white/80 backdrop-blur-md border-b border-gray-100 sticky top-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <a href="index.php" class="text-2xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-700 to-cyan-500 tracking-tight">SmartFinance</a>
            </div>

            <div class="hidden md:flex space-x-8">
                <a href="index.php" class="<?= ($current_page == 'index') ? 'text-blue-600 font-bold border-b-2 border-blue-600' : 'text-gray-400 hover:text-gray-800 font-medium' ?> transition-colors px-1 py-2">Beranda</a>
                <a href="manajemen.php" class="<?= ($current_page == 'manajemen') ? 'text-blue-600 font-bold border-b-2 border-blue-600' : 'text-gray-400 hover:text-gray-800 font-medium' ?> transition-colors px-1 py-2">Manajemen</a>
                <a href="riwayat.php" class="<?= ($current_page == 'riwayat') ? 'text-blue-600 font-bold border-b-2 border-blue-600' : 'text-gray-400 hover:text-gray-800 font-medium' ?> transition-colors px-1 py-2">Riwayat</a>
            </div>

            <div class="flex items-center gap-5">
                <button class="relative p-2 text-gray-400 hover:text-blue-600 transition-colors rounded-full hover:bg-blue-50">
                    <i class="far fa-bell text-xl"></i>
                    <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border-2 border-white"></span>
                    </span>
                </button>
                
                <div class="hidden md:block text-right">
                    <p class="text-sm font-bold text-gray-800 truncate max-w-[120px]"><?= htmlspecialchars($nav_user_name) ?></p>
                    <p class="text-xs text-gray-500 font-medium"><?= htmlspecialchars($nav_user_role) ?></p>
                </div>
                
                <div class="relative">
                    <img id="navProfileBtn" class="h-11 w-11 rounded-full object-cover border-2 border-white shadow-md cursor-pointer hover:scale-105 transition-transform" src="<?= htmlspecialchars($nav_photo_url) ?>" alt="Profil">
                    
                    <div id="navProfileMenu" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] border border-gray-100 overflow-hidden transform origin-top-right transition-all z-50">
                        <div class="md:hidden px-5 py-3 border-b border-gray-50 bg-gray-50">
                            <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($nav_user_name) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($nav_user_role) ?></p>
                        </div>
                        <a href="profil.php" class="flex items-center px-5 py-3.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors border-b border-gray-50">
                            <i class="fas fa-cog text-gray-900 w-6"></i> Pengaturan
                        </a>
                        <a href="api/auth.php?action=logout" class="flex items-center px-5 py-3.5 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt text-red-600 w-6"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<?php include_once 'components/modal.php'; ?>
<?php include_once 'components/modal_transaksi.php'; ?>
<?php include_once 'components/modal_kategori.php'; ?>
<?php include_once 'components/modal_action.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('navProfileBtn');
        const profileMenu = document.getElementById('navProfileMenu');

        if (profileBtn && profileMenu) {
            // Tampilkan/Sembunyikan menu saat foto diklik
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Mencegah event klik menjalar ke document
                profileMenu.classList.toggle('hidden');
            });

            // Sembunyikan menu saat klik di luar kotak dropdown
            document.addEventListener('click', function(e) {
                if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.classList.add('hidden');
                }
            });
        }
    });
</script>