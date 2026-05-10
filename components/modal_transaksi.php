<!-- File: components/modal_transaksi.php -->
<div id="globalTransactionModal" class="fixed inset-0 z-[120] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" onclick="closeTransactionModal()"></div>
    
    <!-- Modal Box -->
    <div id="transactionModalContent" class="bg-white rounded-[32px] p-6 sm:p-8 max-w-2xl w-full mx-4 relative z-10 transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto hide-scrollbar">
        
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-extrabold text-gray-900">Catat Transaksi</h2>
            <button onclick="closeTransactionModal()" class="w-10 h-10 bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-500 rounded-full transition-colors flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="globalTransactionForm" onsubmit="submitGlobalTransaction(event)">
            
            <!-- PERBAIKAN TOGGLE: Warna Solid Super Jelas -->
            <div class="flex gap-4 mb-8 bg-gray-100 p-2 rounded-2xl">
                <label class="flex-1 cursor-pointer relative">
                    <input type="radio" name="type" value="expense" class="peer sr-only" checked onchange="switchTxType('expense')">
                    <div class="p-3 rounded-xl peer-checked:bg-red-500 peer-checked:shadow-lg peer-checked:shadow-red-500/30 text-center transition-all duration-300">
                        <p class="font-bold text-gray-500 peer-checked:text-white"><i class="fas fa-arrow-up mr-2"></i>Pengeluaran</p>
                    </div>
                </label>
                <label class="flex-1 cursor-pointer relative">
                    <input type="radio" name="type" value="income" class="peer sr-only" onchange="switchTxType('income')">
                    <div class="p-3 rounded-xl peer-checked:bg-green-500 peer-checked:shadow-lg peer-checked:shadow-green-500/30 text-center transition-all duration-300">
                        <p class="font-bold text-gray-500 peer-checked:text-white"><i class="fas fa-arrow-down mr-2"></i>Pemasukan</p>
                    </div>
                </label>
            </div>

            <!-- Input Nominal -->
            <div class="mb-8 text-center bg-gray-50/50 py-6 rounded-3xl border border-gray-100">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Nominal</label>
                <div class="flex justify-center items-center gap-3">
                    <span id="txCurrencySign" class="text-3xl font-extrabold text-red-500 transition-colors">- Rp</span>
                    <input type="text" id="txAmountInput" placeholder="0" required autocomplete="off"
                        class="w-2/3 text-4xl sm:text-5xl font-black text-red-500 bg-transparent border-b-2 border-transparent focus:border-red-200 outline-none pb-1 transition-all text-center">
                </div>
            </div>

            <!-- Pilihan Dompet (KODE BARU: Desain Grid Horizontal) -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-3">Sumber Dana (Dompet)</label>
                <div id="txWalletContainer" class="flex gap-3 overflow-x-auto pb-3 hide-scrollbar">
                    <p class="text-gray-400 text-sm italic"><i class="fas fa-spinner fa-spin"></i> Memuat dompet...</p>
                </div>
            </div>

            <!-- Pilihan Kategori -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label>
                <div id="txCategoryContainer" class="flex gap-3 overflow-x-auto pb-2 hide-scrollbar">
                    <p class="text-gray-400 text-sm"><i class="fas fa-spinner fa-spin"></i> Memuat...</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required
                        class="w-full bg-white border-2 border-gray-100 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500 text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Catatan</label>
                    <input type="text" name="note" placeholder="Opsional" autocomplete="off"
                        class="w-full bg-white border-2 border-gray-100 rounded-xl px-4 py-3 font-medium outline-none focus:border-blue-500 text-gray-700">
                </div>
            </div>

            <button type="submit" id="txSubmitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-lg py-4 rounded-2xl shadow-xl shadow-blue-500/20 transition-transform transform hover:-translate-y-1">
                Simpan Transaksi
            </button>
        </form>
    </div>
</div>

<script>
    let txCategories = [];
    let currentTxType = 'expense';
    let isTxDataLoaded = false; // Flag efisiensi (Lazy Loading)

    // Format input angka otomatis
    document.getElementById('txAmountInput').addEventListener('input', function(e) {
        let rawValue = this.value.replace(/[^0-9]/g, ''); 
        this.value = rawValue ? new Intl.NumberFormat('id-ID').format(rawValue) : '';
    });

    // Buka Modal & Lazy Load Data
    function openTransactionModal() {
        const modal = document.getElementById('globalTransactionModal');
        const content = document.getElementById('transactionModalContent');
        
        modal.classList.remove('hidden'); void modal.offsetWidth;
        modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100');

        // EFISIENSI: Hanya fetch data jika belum pernah di-load saat modal dibuka pertama kali
        if (!isTxDataLoaded) {
            fetchTxData();
        }
    }

    function closeTransactionModal() {
        const modal = document.getElementById('globalTransactionModal');
        const content = document.getElementById('transactionModalContent');
        modal.classList.add('opacity-0'); content.classList.remove('scale-100'); content.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function fetchTxData() {
        // Fetch Wallets
        // Fetch Wallets (Diubah menjadi tampilan Card Horizontal)
        fetch('api/wallet.php').then(res => res.json()).then(data => {
            const container = document.getElementById('txWalletContainer');
            container.innerHTML = '';
            
            if (data.status === 'success' && data.data.length > 0) {
                data.data.forEach((w, index) => {
                    // Pilih dompet pertama secara otomatis
                    const isChecked = index === 0 ? 'checked' : '';
                    
                    // Logika pendeteksi gambar atau FontAwesome (sama seperti kategori)
                    const safeIconName = w.icon_name ? w.icon_name : 'wallet';
                    const isImage = safeIconName.includes('.');
                    
                    const iconHtml = isImage 
                        ? `<img src="${safeIconName}" class="w-6 h-6 object-contain" alt="Icon" onerror="this.style.display='none'">`
                        : `<i class="fas fa-${safeIconName} text-white"></i>`;

                    // Format saldo menjadi Rupiah
                    const formattedBalance = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(w.balance);

                    container.innerHTML += `
                        <label class="cursor-pointer shrink-0 relative group">
                            <input type="radio" name="wallet_id" value="${w.id}" class="peer sr-only" ${isChecked} required>
                            
                            <!-- Desain Kartu Dompet -->
                            <div class="w-auto min-w-[160px] h-[72px] px-4 flex items-center gap-3 rounded-2xl bg-white border-2 border-gray-100 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-sm transition-all hover:border-blue-300">
                                
                                <!-- Kotak Ikon Warna-Warni -->
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-inner shrink-0 bg-white" style="background-color: ${w.color}">
                                    ${iconHtml}
                                </div>
                                
                                <!-- Teks Nama & Saldo -->
                                <div class="flex flex-col justify-center overflow-hidden">
                                    <span class="text-[11px] font-bold text-gray-500 peer-checked:text-blue-700 leading-tight truncate w-full">${w.name}</span>
                                    <span class="text-xs font-extrabold text-gray-900 peer-checked:text-blue-800 leading-tight mt-1 truncate">${formattedBalance}</span>
                                </div>

                            </div>
                        </label>
                    `;
                });
            } else {
                container.innerHTML = '<p class="text-sm text-red-500 font-bold bg-red-50 px-4 py-2 rounded-xl">Belum ada dompet aktif.</p>';
            }
        });

        // Fetch Categories
        fetch('api/category.php').then(res => res.json()).then(data => {
            if (data.status === 'success') {
                txCategories = data.data;
                renderTxCategories(currentTxType);
                isTxDataLoaded = true; // Set flag menjadi true
            }
        });
    }
    

    function renderTxCategories(type) {
        const container = document.getElementById('txCategoryContainer');
        container.innerHTML = '';
        const filtered = txCategories.filter(c => c.type === type);
        
        filtered.forEach((c, index) => {
            const isChecked = index === 0 ? 'checked' : ''; 
            const safeIconName = c.icon_name ? c.icon_name : 'wallet';
            const isImage = safeIconName.includes('.');
            
            const iconHtml = isImage 
                ? `<img src="${safeIconName}" class="w-7 h-7 object-contain mb-1" alt="Icon">`
                : `<i class="fas fa-${safeIconName} text-xl mb-1 transition-transform peer-checked:scale-110" style="color: ${c.color}"></i>`;

            container.innerHTML += `
                <div class="shrink-0 relative group">
                    <label class="cursor-pointer block">
                        <input type="radio" name="category_id" value="${c.id}" class="peer sr-only" ${isChecked} required>
                        <div class="w-20 h-20 flex flex-col items-center justify-center gap-1 rounded-2xl bg-white border-2 border-gray-100 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-sm transition-all hover:border-blue-300">
                            ${iconHtml}
                            <span class="text-[10px] font-bold text-gray-500 peer-checked:text-blue-700 text-center px-1 w-full truncate leading-tight">${c.name}</span>
                        </div>
                    </label>
                    
                    <div class="absolute -top-2 -right-1 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); openCategoryModal('edit', '${type}', '${c.id}', '${c.name}', '${safeIconName}', '${c.color}')" 
                            class="bg-blue-600 text-white border border-white rounded-full w-6 h-6 flex items-center justify-center text-[9px] shadow-lg">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); deleteCategory(${c.id})" 
                            class="bg-red-600 text-white border border-white rounded-full w-6 h-6 flex items-center justify-center text-[9px] shadow-lg">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        // Tombol Buat Baru tetap ada di paling ujung
        container.innerHTML += `
            <div class="cursor-pointer shrink-0" onclick="openCategoryModal('add', '${type}')">
                <div class="w-20 h-20 flex flex-col items-center justify-center gap-1 rounded-2xl bg-gray-50 border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 text-gray-400 hover:text-blue-600 transition-all">
                    <i class="fas fa-plus text-lg"></i>
                    <span class="text-[10px] font-bold text-center leading-tight">Buat<br>Baru</span>
                </div>
            </div>
        `;
    }

    // FUNGSI HAPUS KATEGORI YANG SUDAH DIPERBAIKI
    function deleteCategory(id) {
        // 1. Cek langsung apakah sistem modal bawaan tersedia
        if (typeof showModal === 'function') {
            showModal('warning', 'Hapus Kategori?', 'Transaksi yang sudah ada dengan kategori ini mungkin akan kehilangan label kategorinya.', () => {
                executeDeleteCategory(id);
            });
        } else {
            // Fallback aman jika modal.php gagal dimuat
            if (confirm("Yakin ingin menghapus kategori ini? Transaksi terkait mungkin kehilangan labelnya.")) {
                executeDeleteCategory(id);
            }
        }
    }

    // Eksekutor API Delete Kategori
    function executeDeleteCategory(id) {
        fetch('api/category.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (typeof showModal === 'function') {
                    showModal('success', 'Berhasil', 'Kategori telah dihapus.', fetchTxData);
                } else {
                    alert('Kategori telah dihapus.');
                    fetchTxData();
                }
            } else {
                if (typeof showModal === 'function') {
                    showModal('error', 'Gagal', data.message);
                } else {
                    alert('Gagal: ' + data.message);
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert("Kesalahan jaringan saat menghapus kategori.");
        });
    }
    
    function switchTxType(type) {
        currentTxType = type;
        const amountInput = document.getElementById('txAmountInput');
        const currencySign = document.getElementById('txCurrencySign');
        
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
        renderTxCategories(type);
    }

    function submitGlobalTransaction(e) {
        e.preventDefault();
        const btn = document.getElementById('txSubmitBtn');
        const originalText = btn.innerHTML;
        
        const formData = new FormData(document.getElementById('globalTransactionForm'));
        const payload = Object.fromEntries(formData.entries());
        payload.amount = document.getElementById('txAmountInput').value.replace(/\./g, '');

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
                // 1. Tutup modal form transaksi
                closeTransactionModal();
                
                // 2. Panggil animasi Modal Sukses Otomatis
                showAutoCloseSuccess('Transaksi Berhasil!');

                // 3. Reload halaman secara otomatis setelah 1.5 detik
                setTimeout(() => {
                    window.location.reload();
                }, 1000);

            } else {
                // Jika error, tetap pakai modal error bawaan / alert
                if (typeof showModal === 'function') {
                    showModal('error', 'Gagal', data.message);
                } else {
                    alert('Gagal: ' + data.message);
                }
                btn.innerHTML = originalText; 
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert('Kesalahan Server! Gagal memproses transaksi.');
            btn.innerHTML = originalText; 
            btn.disabled = false;
        });
    }

    // ==========================================
    // FUNGSI BARU: Modal Sukses Otomatis Hilang
    // ==========================================
    function showAutoCloseSuccess(message) {
        // Buat elemen div untuk modal
        const toast = document.createElement('div');
        toast.className = 'fixed inset-0 z-[200] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 opacity-0';
        toast.innerHTML = `
            <div class="bg-white rounded-[32px] p-8 flex flex-col items-center shadow-2xl transform scale-90 transition-transform duration-300" id="toastContent">
                <div class="w-24 h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center text-5xl mb-5 shadow-inner">
                    <i class="fas fa-check"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900">${message}</h3>
                <p class="text-gray-500 mt-2 font-medium">Memperbarui dashboard...</p>
            </div>
        `;
        
        // Masukkan ke dalam HTML
        document.body.appendChild(toast);

        // Jalankan animasi muncul (Fade In & Scale Up)
        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0');
            toast.querySelector('#toastContent').classList.remove('scale-90');
            toast.querySelector('#toastContent').classList.add('scale-100');
        });
    }
</script>