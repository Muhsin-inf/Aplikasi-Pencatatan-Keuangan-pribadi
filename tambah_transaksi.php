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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; }
        .form-card { box-shadow: 0 20px 40px rgba(0,0,0,0.03); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Hilangkan panah up/down di input number bawaan browser jika ada */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="antialiased text-gray-800 pb-20">

    <?php include 'navbar.php'; ?>
    <?php include 'components/modal.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-[32px] p-6 sm:p-10 form-card border border-gray-100">
                
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">Catat Transaksi</h1>
                    <a href="index" class="text-sm font-bold text-gray-400 hover:text-red-500 transition-colors bg-gray-50 hover:bg-red-50 px-4 py-2 rounded-xl">
                        <i class="fas fa-times mr-1"></i> Batal
                    </a>
                </div>

                <form id="transactionForm" onsubmit="submitTransaction(event)">
                    
                    <!-- Toggle Pemasukan & Pengeluaran -->
                    <div class="flex gap-4 mb-10 bg-gray-50 p-2 rounded-2xl">
                        <label class="flex-1 cursor-pointer relative">
                            <input type="radio" name="type" value="expense" class="peer sr-only" checked onchange="switchType('expense')">
                            <div class="p-3 rounded-xl peer-checked:bg-white peer-checked:shadow-sm text-center transition-all">
                                <p class="font-bold text-gray-400 peer-checked:text-red-500"><i class="fas fa-arrow-up mr-2"></i>Pengeluaran</p>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer relative">
                            <input type="radio" name="type" value="income" class="peer sr-only" onchange="switchType('income')">
                            <div class="p-3 rounded-xl peer-checked:bg-white peer-checked:shadow-sm text-center transition-all">
                                <p class="font-bold text-gray-400 peer-checked:text-green-500"><i class="fas fa-arrow-down mr-2"></i>Pemasukan</p>
                            </div>
                        </label>
                    </div>

                    <!-- Input Nominal dengan Format Otomatis -->
                    <div class="mb-10 text-center bg-gray-50/50 py-8 rounded-3xl border border-gray-100">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Nominal</label>
                        <div class="flex justify-center items-center gap-3">
                            <span id="currencySign" class="text-3xl sm:text-4xl font-extrabold text-red-500 transition-colors">- Rp</span>
                            <!-- Diubah dari type="number" menjadi type="text" agar bisa diberi titik -->
                            <input type="text" id="amountInput" placeholder="0" required autocomplete="off"
                                class="w-1/2 sm:w-2/3 text-4xl sm:text-6xl font-black text-red-500 bg-transparent border-b-2 border-transparent focus:border-red-200 outline-none pb-1 transition-all text-center">
                        </div>
                    </div>

                    <!-- Pilihan Sumber Dana (Dompet) -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-3">Sumber Dana (Dompet)</label>
                        <select name="wallet_id" id="walletSelect" required class="w-full bg-white border-2 border-gray-100 rounded-xl px-4 py-4 font-bold text-gray-700 outline-none focus:border-blue-500 transition-colors cursor-pointer appearance-none">
                            <option value="" disabled selected>Memuat dompet...</option>
                            <!-- Diisi oleh JS -->
                        </select>
                    </div>

                    <!-- Pilihan Kategori (Grid Ikon) -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-3">Pilih Kategori</label>
                        <div id="categoryContainer" class="flex gap-3 overflow-x-auto pb-4 hide-scrollbar">
                            <p class="text-gray-400 text-sm italic"><i class="fas fa-spinner fa-spin"></i> Memuat kategori...</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">Tanggal</label>
                            <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required
                                class="w-full bg-white border-2 border-gray-100 rounded-xl px-4 py-3.5 font-bold outline-none focus:border-blue-500 transition-colors text-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">Catatan (Opsional)</label>
                            <input type="text" name="note" placeholder="Misal: Beli makan siang" autocomplete="off"
                                class="w-full bg-white border-2 border-gray-100 rounded-xl px-4 py-3.5 font-medium outline-none focus:border-blue-500 transition-colors text-gray-700">
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-lg py-4 rounded-2xl shadow-[0_10px_20px_rgba(37,99,235,0.2)] transition-all transform hover:-translate-y-1">
                        Konfirmasi & Simpan
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        let allCategories = [];
        let currentType = 'expense';

        // 1. Script Otomatisasi Format Titik Rupiah (Anti-Inject Non-Angka di Frontend)
        const amountInput = document.getElementById('amountInput');
        amountInput.addEventListener('input', function(e) {
            // Hapus semua karakter yang bukan angka (Anti Huruf/Simbol)
            let rawValue = this.value.replace(/[^0-9]/g, ''); 
            
            if (rawValue) {
                // Tambahkan format titik standar Indonesia
                this.value = new Intl.NumberFormat('id-ID').format(rawValue);
            } else {
                this.value = '';
            }
        });

        // 2. Load Data Kategori & Dompet secara bersamaan saat halaman dibuka
        document.addEventListener('DOMContentLoaded', () => {
            fetchCategories();
            fetchWallets();
        });

        function fetchWallets() {
            fetch('api/wallet.php')
                .then(res => res.json())
                .then(data => {
                    const select = document.getElementById('walletSelect');
                    if (data.status === 'success' && data.data.length > 0) {
                        select.innerHTML = '<option value="" disabled selected>-- Pilih Dompet --</option>';
                        data.data.forEach(w => {
                            select.innerHTML += `<option value="${w.id}">${w.name} (Saldo: ${new Intl.NumberFormat('id-ID').format(w.balance)})</option>`;
                        });
                    } else {
                        select.innerHTML = '<option value="" disabled>Belum ada dompet, buat di menu Kelola Dompet</option>';
                    }
                });
        }

        function fetchCategories() {
            fetch('api/category.php')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        allCategories = data.data;
                        renderCategories(currentType);
                    }
                });
        }

        // Render UI Kategori 
        function renderCategories(type) {
            const container = document.getElementById('categoryContainer');
            container.innerHTML = '';
            const filtered = allCategories.filter(c => c.type === type);
            
            filtered.forEach((c, index) => {
                const isChecked = index === 0 ? 'checked' : ''; 
                
                // Mendukung ikon gambar .png atau fontawesome seperti di halaman dompet
                const isImage = c.icon_name.includes('.');
                const iconHtml = isImage 
                    ? `<img src="${c.icon_name}" class="w-8 h-8 object-contain mb-1" alt="Icon">`
                    : `<i class="fas fa-${c.icon_name} text-2xl mb-1 transition-transform peer-checked:scale-110" style="color: ${c.color}"></i>`;

                container.innerHTML += `
                    <label class="cursor-pointer shrink-0 relative group">
                        <input type="radio" name="category_id" value="${c.id}" class="peer sr-only" ${isChecked} required>
                        <div class="w-24 h-24 flex flex-col items-center justify-center gap-1 rounded-2xl bg-white border-2 border-gray-100 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-sm transition-all hover:border-gray-300">
                            ${iconHtml}
                            <span class="text-[11px] font-bold text-gray-500 peer-checked:text-blue-700 text-center px-1 w-full truncate leading-tight">${c.name}</span>
                        </div>
                    </label>
                `;
            });

            if(filtered.length === 0) {
                container.innerHTML = `<p class="text-gray-400 text-sm py-4">Belum ada kategori untuk tipe ini. Buat terlebih dahulu.</p>`;
            }
        }

        // Ganti Tipe Pengeluaran/Pemasukan (Ubah Warna)
        function switchType(type) {
            currentType = type;
            const currencySign = document.getElementById('currencySign');
            
            if (type === 'expense') {
                amountInput.classList.replace('text-green-500', 'text-red-500');
                amountInput.classList.replace('focus:border-green-200', 'focus:border-red-200');
                currencySign.classList.replace('text-green-500', 'text-red-500');
                currencySign.innerText = '- Rp';
            } else {
                amountInput.classList.replace('text-red-500', 'text-green-500');
                amountInput.classList.replace('focus:border-red-200', 'focus:border-green-200');
                currencySign.classList.replace('text-red-500', 'text-green-500');
                currencySign.innerText = '+ Rp';
            }
            renderCategories(type);
        }

        // Eksekusi Submit Transaksi via API RESTful
        function submitTransaction(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            
            // Ambil data form
            const formData = new FormData(document.getElementById('transactionForm'));
            const payload = Object.fromEntries(formData.entries());
            
            // Hapus titik dari nominal sebelum dikirim ke server (contoh: "1.500.000" jadi "1500000")
            payload.amount = amountInput.value.replace(/\./g, '');

            if(payload.amount <= 0 || payload.amount === '') {
                showModal('error', 'Gagal', 'Nominal transaksi tidak valid!');
                return;
            }

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            btn.disabled = true;

            fetch('api/transaction.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showModal('success', 'Berhasil!', 'Transaksi telah dicatat.', () => {
                        window.location.href = 'index'; // Kembali ke dashboard
                    });
                } else {
                    showModal('error', 'Gagal', data.message);
                    btn.innerHTML = originalText; btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                showModal('error', 'Kesalahan Server', 'Gagal terhubung ke API.');
                btn.innerHTML = originalText; btn.disabled = false;
            });
        }
    </script>
</body>
</html>