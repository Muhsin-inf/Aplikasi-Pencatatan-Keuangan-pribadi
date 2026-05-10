<?php
require_once 'api/config.php';

// Jika tidak ada sesi user_id, tendang kembali ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manajemen - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; -webkit-tap-highlight-color: transparent; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .tab-active { color: #2563eb; border-bottom: 3px solid #2563eb; }
        @media (max-width: 768px) { body { padding-bottom: 90px; } }
    </style>
</head>
<body class="antialiased text-gray-800">

    <?php include 'navbar.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8 mt-4 md:mt-0">
        
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">Manajemen Data</h1>
            <p class="text-gray-500 font-medium mt-1 text-sm md:text-base">Atur sumber dana dan pengelompokan transaksi Anda.</p>
        </div>

        <div class="flex border-b border-gray-200 mb-6 md:mb-8 bg-white rounded-t-2xl px-2 md:px-4">
            <button onclick="switchTab('wallet')" id="tabWallet" class="flex-1 py-3 md:py-4 font-bold text-xs md:text-sm transition-all tab-active">
                <i class="fas fa-wallet mr-2"></i>Dompet
            </button>
            <button onclick="switchTab('category')" id="tabCategory" class="flex-1 py-3 md:py-4 font-bold text-xs md:text-sm text-gray-400 transition-all">
                <i class="fas fa-border-all mr-2"></i>Kategori
            </button>
        </div>

        <div id="sectionWallet">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-extrabold text-base md:text-lg text-gray-800">Daftar Dompet</h2>
                <button onclick="openWalletModal('add')" class="text-xs md:text-sm bg-blue-600 hover:bg-blue-700 transition-colors text-white px-4 py-2 rounded-xl font-bold shadow-md shadow-blue-500/20">
                    <i class="fas fa-plus md:mr-1"></i> <span class="hidden md:inline">Dompet</span>
                </button>
            </div>
            <div id="walletGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <p class="text-gray-400 text-sm italic col-span-full"><i class="fas fa-spinner fa-spin"></i> Memuat dompet...</p>
            </div>
        </div>

        <div id="sectionCategory" class="hidden">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-extrabold text-base md:text-lg text-gray-800">Daftar Kategori</h2>
                <div class="flex gap-2">
                    <select id="catTypeFilter" onchange="fetchCategoriesManajemen()" class="text-xs font-bold bg-white border border-gray-200 rounded-lg px-2 py-1 outline-none cursor-pointer">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                    <button onclick="openCategoryModal('add', document.getElementById('catTypeFilter').value)" class="text-xs md:text-sm bg-emerald-600 hover:bg-emerald-700 transition-colors text-white px-4 py-2 rounded-xl font-bold shadow-md shadow-emerald-500/20">
                        <i class="fas fa-plus md:mr-1"></i> <span class="hidden md:inline">Kategori</span>
                    </button>
                </div>
            </div>
            <div id="categoryGrid" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <p class="text-gray-400 text-sm italic col-span-full"><i class="fas fa-spinner fa-spin"></i> Memuat kategori...</p>
            </div>
        </div>

    </main>

    <div id="walletModal" class="fixed inset-0 z-[110] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeWalletModal()"></div>
        <div class="bg-white rounded-[24px] p-6 md:p-8 max-w-md w-full mx-4 relative z-10 transform scale-95 transition-transform duration-300" id="walletModalContent">
            <h3 class="text-xl font-extrabold text-gray-900 mb-6">Tambah Dompet</h3>
            
            <form id="walletForm" onsubmit="submitWallet(event)">
                <input type="hidden" name="id" id="editWalletId" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-600 mb-2">Nama Dompet / Bank</label>
                    <input type="text" name="name" required placeholder="Misal: Cash, Bank BCA, Gopay" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-600 mb-2">Pilih Ikon</label>
                    <div class="grid grid-cols-5 gap-3 h-40 overflow-y-auto p-2 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="wallet" class="peer sr-only" checked><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm"><i class="fas fa-wallet text-lg"></i></div></label>
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="building-columns" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm"><i class="fas fa-building-columns text-lg"></i></div></label>
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="money-bill-wave" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm"><i class="fas fa-money-bill-wave text-lg"></i></div></label>
                        
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="assets/icon/bca.svg" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 shadow-sm p-1.5"><img src="assets/icon/bca.svg" onerror="this.style.display='none'" class="w-full h-full object-contain"></div></label>
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="assets/icon/mandiri.svg" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 shadow-sm p-1.5"><img src="assets/icon/mandiri.svg" onerror="this.style.display='none'" class="w-full h-full object-contain"></div></label>
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="assets/icon/bri.svg" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 shadow-sm p-1.5"><img src="assets/icon/bri.svg" onerror="this.style.display='none'" class="w-full h-full object-contain"></div></label>
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="assets/icon/gopay.svg" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 shadow-sm p-1.5"><img src="assets/icon/gopay.svg" onerror="this.style.display='none'" class="w-full h-full object-contain"></div></label>
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="assets/icon/shopeepay.svg" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 shadow-sm p-1.5"><img src="assets/icon/shopeepay.svg" onerror="this.style.display='none'" class="w-full h-full object-contain"></div></label>
                        <label class="cursor-pointer"><input type="radio" name="icon_name" value="assets/icon/dana.svg" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-xl bg-white border-2 border-transparent peer-checked:border-blue-500 shadow-sm p-1.5"><img src="assets/icon/dana.svg" onerror="this.style.display='none'" class="w-full h-full object-contain"></div></label>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-600 mb-2">Warna Latar Belakang</label>
                    <input type="color" name="color" value="#3B82F6" class="w-full h-12 rounded-xl cursor-pointer border-0 p-0">
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeWalletModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition-colors">Batal</button>
                    <button type="submit" id="walletSubmitBtn" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-colors">Simpan Dompet</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'navbar_hp.php'; ?>

    <script>
        const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);

        // ==========================================
        // TAB LOGIC
        // ==========================================
        function switchTab(target) {
            const tabW = document.getElementById('tabWallet');
            const tabC = document.getElementById('tabCategory');
            const secW = document.getElementById('sectionWallet');
            const secC = document.getElementById('sectionCategory');

            if (target === 'wallet') {
                tabW.classList.add('tab-active'); tabW.classList.remove('text-gray-400');
                tabC.classList.remove('tab-active'); tabC.classList.add('text-gray-400');
                secW.classList.remove('hidden'); secC.classList.add('hidden');
                fetchWalletsManajemen();
            } else {
                tabC.classList.add('tab-active'); tabC.classList.remove('text-gray-400');
                tabW.classList.remove('tab-active'); tabW.classList.add('text-gray-400');
                secC.classList.remove('hidden'); secW.classList.add('hidden');
                fetchCategoriesManajemen();
            }
        }

        // ==========================================
        // WALLET CRUD LOGIC
        // ==========================================
        function fetchWalletsManajemen() {
            fetch('api/wallet.php').then(res => res.json()).then(data => {
                const grid = document.getElementById('walletGrid');
                grid.innerHTML = '';
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.forEach(w => {
                        const safeIcon = w.icon_name || 'wallet';
                        const iconHtml = safeIcon.includes('.') 
                            ? `<img src="${safeIcon}" class="w-6 h-6 object-contain" onerror="this.style.display='none'">`
                            : `<i class="fas fa-${safeIcon}"></i>`;

                        grid.innerHTML += `
                            <div class="bg-white p-4 rounded-2xl border border-gray-100 flex items-center justify-between shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:-translate-y-1 transition-transform">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0" style="background-color: ${w.color}">${iconHtml}</div>
                                    <div class="overflow-hidden">
                                        <p class="font-bold text-gray-800 text-sm truncate">${w.name}</p>
                                        <p class="text-xs text-gray-500 font-extrabold">${formatRupiah(w.balance)}</p>
                                    </div>
                                </div>
                                <div class="flex gap-1 shrink-0 ml-2">
                                    <button onclick="openWalletModal('edit', ${w.id}, '${w.name}', '${safeIcon}', '${w.color}')" class="w-8 h-8 flex items-center justify-center text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-lg transition-colors"><i class="fas fa-pencil-alt text-xs"></i></button>
                                    <button onclick="deleteWallet(${w.id})" class="w-8 h-8 flex items-center justify-center text-red-600 bg-red-50 hover:bg-red-600 hover:text-white rounded-lg transition-colors"><i class="fas fa-trash text-xs"></i></button>
                                </div>
                            </div>`;
                    });
                } else {
                    grid.innerHTML = `<p class="text-gray-400 text-sm py-5 col-span-full">Belum ada data dompet.</p>`;
                }
            });
        }

        function openWalletModal(mode, id='', name='', icon='wallet', color='#3B82F6') {
            const modal = document.getElementById('walletModal');
            const content = document.getElementById('walletModalContent');
            const form = document.getElementById('walletForm');
            
            document.querySelector('#walletModalContent h3').innerText = mode === 'edit' ? 'Edit Dompet' : 'Tambah Dompet';
            document.getElementById('editWalletId').value = id;
            
            if(mode === 'edit') {
                form.querySelector('input[name="name"]').value = name;
                form.querySelector('input[name="color"]').value = color;
                const iconRadio = form.querySelector(`input[name="icon_name"][value="${icon}"]`);
                if(iconRadio) iconRadio.checked = true;
            } else {
                form.reset();
            }

            modal.classList.remove('hidden'); void modal.offsetWidth;
            modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100');
        }

        function closeWalletModal() {
            const modal = document.getElementById('walletModal');
            const content = document.getElementById('walletModalContent');
            modal.classList.add('opacity-0'); content.classList.remove('scale-100'); content.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function submitWallet(e) {
            e.preventDefault();
            const btn = document.getElementById('walletSubmitBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; btn.disabled = true;

            const payload = Object.fromEntries(new FormData(document.getElementById('walletForm')).entries());
            const methodType = payload.id ? 'PUT' : 'POST';

            fetch('api/wallet.php', { method: methodType, headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    closeWalletModal(); 
                    if (typeof showModal === 'function') showModal('success', 'Berhasil', data.message, fetchWalletsManajemen);
                    else { alert('Berhasil!'); fetchWalletsManajemen(); }
                } else {
                    if (typeof showModal === 'function') showModal('error', 'Gagal', data.message);
                    else alert('Gagal: ' + data.message);
                }
            }).finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
        }

        function deleteWallet(id) {
            if (typeof showModal === 'function') {
                showModal('warning', 'Hapus Dompet?', 'Jika dompet dihapus, tidak boleh ada riwayat transaksi yang terikat padanya.', () => {
                    executeDeleteWallet(id);
                });
            } else {
                if(confirm("Yakin hapus dompet ini?")) executeDeleteWallet(id);
            }
        }

        function executeDeleteWallet(id) {
            fetch('api/wallet.php', { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id}) })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    if (typeof showModal === 'function') showModal('success', 'Terhapus', data.message, fetchWalletsManajemen);
                    else { alert("Terhapus"); fetchWalletsManajemen(); }
                } else {
                    if (typeof showModal === 'function') showModal('error', 'Gagal', data.message);
                    else alert("Gagal: " + data.message);
                }
            });
        }

        // ==========================================
        // CATEGORY CRUD LOGIC
        // ==========================================
        function fetchCategoriesManajemen() {
            const type = document.getElementById('catTypeFilter').value;
            fetch('api/category.php').then(res => res.json()).then(data => {
                const grid = document.getElementById('categoryGrid');
                grid.innerHTML = '';
                if (data.status === 'success') {
                    const filtered = data.data.filter(c => c.type === type);
                    if (filtered.length > 0) {
                        filtered.forEach(c => {
                            const safeIcon = c.icon_name || 'tag';
                            const iconHtml = safeIcon.includes('.') 
                                ? `<img src="${safeIcon}" class="w-6 h-6 md:w-8 md:h-8 object-contain" onerror="this.style.display='none'">`
                                : `<i class="fas fa-${safeIcon} text-lg md:text-xl"></i>`;

                            grid.innerHTML += `
                                <div class="bg-white p-4 rounded-2xl border border-gray-100 flex flex-col items-center text-center relative group shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:-translate-y-1 transition-transform">
                                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center mb-2" style="background-color: ${c.color}20; color: ${c.color}">${iconHtml}</div>
                                    <p class="font-extrabold text-gray-800 text-[10px] md:text-xs truncate w-full">${c.name}</p>
                                    
                                    <div class="absolute -top-2 -right-2 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button onclick="openCategoryModal('edit', '${type}', '${c.id}', '${c.name}', '${safeIcon}', '${c.color}')" class="w-7 h-7 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center text-[10px] shadow-lg"><i class="fas fa-pencil-alt"></i></button>
                                        <button onclick="deleteCategory(${c.id})" class="w-7 h-7 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center text-[10px] shadow-lg"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>`;
                        });
                    } else {
                        grid.innerHTML = `<p class="text-gray-400 text-sm py-5 col-span-full text-center">Belum ada kategori ${type === 'income' ? 'pemasukan' : 'pengeluaran'}.</p>`;
                    }
                }
            });
        }

        // Panggil fetchWalletsManajemen saat pertama kali load
        document.addEventListener('DOMContentLoaded', fetchWalletsManajemen);
    </script>
</body>
</html>