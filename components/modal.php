<div id="globalModal" class="fixed inset-0 z-[150] flex items-center justify-center hidden opacity-0 transition-opacity duration-300 font-['Plus_Jakarta_Sans']">
    <div id="modalBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-md transition-all" onclick="closeModal()"></div>

    <div id="modalContent" class="bg-white rounded-[32px] p-8 md:p-10 max-w-sm w-full mx-4 relative z-10 transform scale-95 transition-transform duration-300 shadow-2xl border border-white/50 flex flex-col items-center text-center">
        
        <div id="modalIconBg" class="mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5 shadow-inner transition-colors duration-300">
            <i id="modalIcon" class="text-4xl text-white"></i>
        </div>
        
        <h3 id="modalTitle" class="text-xl font-extrabold text-gray-900 mb-2 tracking-tight"></h3>
        <p id="modalMessage" class="text-gray-500 text-sm font-medium mb-8 leading-relaxed"></p>

        <div id="modalActionArea" class="w-full flex gap-3"></div>
    </div>
</div>

<script>
    let isModalActive = false;
    let activeCallback = null;
    let successTimer = null;

    function setupModalAppearance(type, title, message) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').innerHTML = message;
        
        const iconBg = document.getElementById('modalIconBg');
        const icon = document.getElementById('modalIcon');
        
        iconBg.className = 'mx-auto flex items-center justify-center h-20 w-20 rounded-full mb-5 shadow-inner transition-colors duration-300';
        icon.className = 'text-4xl text-white';

        if (type === 'success') {
            iconBg.classList.add('bg-emerald-500');
            icon.classList.add('fas', 'fa-check');
        } else if (type === 'error') {
            iconBg.classList.add('bg-red-500');
            icon.classList.add('fas', 'fa-times');
        } else if (type === 'warning') {
            iconBg.classList.add('bg-orange-500');
            icon.classList.add('fas', 'fa-exclamation-triangle');
        } else {
            iconBg.classList.add('bg-blue-500');
            icon.classList.add('fas', 'fa-info');
        }
    }

    function openModalAnimation() {
        isModalActive = true;
        const modal = document.getElementById('globalModal');
        const content = document.getElementById('modalContent');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    // Fungsi tutup yang aman untuk mengeksekusi callback
    function forceCloseAndCallback() {
        if (!isModalActive) return;
        isModalActive = false;
        clearTimeout(successTimer); // Matikan timer jika user klik luar duluan
        
        const modal = document.getElementById('globalModal');
        const content = document.getElementById('modalContent');
        
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('modalActionArea').innerHTML = ''; 
            
            // Eksekusi reload/callback SETELAH modal tertutup
            if (typeof activeCallback === 'function') {
                activeCallback();
                activeCallback = null;
            }
        }, 300);
    }

    // Ubah fungsi klik backdrop (area hitam) agar menggunakan forceClose
    document.getElementById('modalBackdrop').onclick = forceCloseAndCallback;

    // ==========================================
    // 1. MODAL STANDAR (Info / Error / Sukses)
    // ==========================================
    function showModal(type, title, message, callback = null) {
        setupModalAppearance(type, title, message);
        activeCallback = callback; 
        
        const actionArea = document.getElementById('modalActionArea');
        actionArea.innerHTML = '';
        
        if (type === 'success') {
            // JIKA SUKSES: Jangan buat tombol apapun. Biarkan kosong.
            openModalAnimation();
            
            // Otomatis tutup dan reload setelah 1.5 detik
            successTimer = setTimeout(() => {
                forceCloseAndCallback();
            }, 1500);

        } else {
            // JIKA ERROR / WARNING: Buat tombol untuk menutup
            const btn = document.createElement('button');
            
            if (type === 'error') {
                btn.className = 'w-full bg-red-600 hover:bg-red-700 text-white font-extrabold py-3.5 rounded-xl transition-all shadow-lg shadow-red-500/30 active:scale-95';
                btn.textContent = 'Tutup';
            } else if (type === 'warning') {
                btn.className = 'w-full bg-orange-600 hover:bg-orange-700 text-white font-extrabold py-3.5 rounded-xl transition-all shadow-lg shadow-orange-500/30 active:scale-95';
                btn.textContent = 'Mengerti';
            } else {
                btn.className = 'w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3.5 rounded-xl transition-all shadow-lg shadow-blue-500/30 active:scale-95';
                btn.textContent = 'OK';
            }

            btn.onclick = forceCloseAndCallback;
            actionArea.appendChild(btn);
            openModalAnimation();
        }
    }

    // ==========================================
    // 2. MODAL KONFIRMASI (2 TOMBOL)
    // ==========================================
    function showConfirmModal(type, title, message, confirmText, cancelText, confirmCallback) {
        setupModalAppearance(type, title, message);
        activeCallback = null; // Konfirmasi punya callback sendiri di tombol Ya
        
        const actionArea = document.getElementById('modalActionArea');
        actionArea.innerHTML = '';
        
        // Tombol Batal
        const btnCancel = document.createElement('button');
        btnCancel.className = 'w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold py-3.5 rounded-xl transition-all active:scale-95';
        btnCancel.textContent = cancelText;
        btnCancel.onclick = forceCloseAndCallback;

        // Tombol Ya (Aksi)
        const btnConfirm = document.createElement('button');
        if (type === 'warning' || type === 'error') {
            btnConfirm.className = 'w-full bg-red-600 hover:bg-red-700 text-white font-extrabold py-3.5 rounded-xl transition-all shadow-lg shadow-red-500/30 active:scale-95';
        } else {
            btnConfirm.className = 'w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3.5 rounded-xl transition-all shadow-lg shadow-blue-500/30 active:scale-95';
        }
        btnConfirm.textContent = confirmText;
        
        btnConfirm.onclick = () => {
            btnConfirm.disabled = true;
            btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; 
            btnCancel.disabled = true;
            
            if (typeof confirmCallback === 'function') {
                confirmCallback(); 
            }
        };

        actionArea.appendChild(btnCancel);
        actionArea.appendChild(btnConfirm);
        openModalAnimation();
    }
</script>