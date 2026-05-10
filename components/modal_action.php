<input type="file" accept="image/*" capture="environment" id="cameraScannerInput" class="hidden" onchange="processReceipt(event)">

<div id="actionMenuModal" class="fixed inset-0 z-[150] flex items-end md:items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeActionMenu()"></div>
    
    <div id="actionMenuContent" class="bg-white w-full md:w-[400px] md:rounded-3xl rounded-t-3xl p-6 relative z-10 transform translate-y-full md:translate-y-0 md:scale-95 transition-all duration-300 pb-10 md:pb-6 shadow-2xl">
        
        <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mb-6 md:hidden"></div>
        
        <h3 class="text-xl font-extrabold text-gray-900 mb-6 text-center">Metode Pencatatan</h3>
        
        <div class="grid grid-cols-2 gap-4">
            
            <button onclick="pilihManual()" class="flex flex-col items-center justify-center p-5 bg-blue-50 hover:bg-blue-100 border border-blue-100 rounded-2xl transition-all group active:scale-95 cursor-pointer">
                <div class="w-14 h-14 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl mb-3 group-hover:scale-110 transition-transform shadow-md shadow-blue-500/30">
                    <i class="fas fa-pen-nib"></i>
                </div>
                <span class="font-extrabold text-blue-900 text-sm">Tulis Manual</span>
                <span class="text-[10px] text-blue-600/70 font-medium mt-1">Ketik sendiri</span>
            </button>
            
            <button onclick="pilihKamera()" class="flex flex-col items-center justify-center p-5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 rounded-2xl transition-all group active:scale-95 cursor-pointer">
                <div class="w-14 h-14 bg-emerald-500 text-white rounded-full flex items-center justify-center text-2xl mb-3 group-hover:scale-110 transition-transform shadow-md shadow-emerald-500/30">
                    <i class="fas fa-camera"></i>
                </div>
                <span class="font-extrabold text-emerald-900 text-sm">Scan Struk</span>
                <span class="text-[10px] text-emerald-600/70 font-medium mt-1">Otomatis deteksi</span>
            </button>

        </div>
    </div>
</div>

<script>
    // Fungsi Buka Menu
    function openActionMenu() {
        const modal = document.getElementById('actionMenuModal');
        const content = document.getElementById('actionMenuContent');
        
        modal.classList.remove('hidden'); 
        void modal.offsetWidth; // Trigger reflow
        modal.classList.remove('opacity-0');
        
        // Animasi geser dari bawah (HP) atau membesar (PC)
        if(window.innerWidth < 768) {
            content.classList.remove('translate-y-full');
        } else {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }
    }

    // Fungsi Tutup Menu
    function closeActionMenu() {
        const modal = document.getElementById('actionMenuModal');
        const content = document.getElementById('actionMenuContent');
        
        modal.classList.add('opacity-0');
        if(window.innerWidth < 768) {
            content.classList.add('translate-y-full');
        } else {
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
        }
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    // Aksi 1: Jika Pilih Manual
    function pilihManual() {
        closeActionMenu();
        // Tunggu animasi menu tertutup (300ms), lalu buka form transaksi
        setTimeout(() => {
            if(typeof openTransactionModal === 'function') openTransactionModal();
        }, 300);
    }

    // Aksi 2: Jika Pilih Kamera
    function pilihKamera() {
        closeActionMenu();
        // Panggil input file rahasia yang akan membuka kamera HP
        setTimeout(() => {
            document.getElementById('cameraScannerInput').click();
        }, 300);
    }

    // Aksi 3: Proses Hasil Foto Struk
    function processReceipt(event) {
        const file = event.target.files[0];
        if (file) {
            // Tampilkan loading pintar (sementara kita buat alert sebelum integrasi AI OCR)
            showModal('success', 'Menganalisis Struk...', 'Foto berhasil diambil! Fitur deteksi teks (OCR) akan diproses di sini.', () => {
                // Di masa depan, di sini Anda bisa mengirim 'file' ke API Tesseract.js atau Google Vision API
                // Lalu otomatis memanggil openTransactionModal() dengan nominal yang sudah terisi!
            });
            // Reset input agar bisa jepret foto lagi nanti
            event.target.value = ''; 
        }
    }
</script>