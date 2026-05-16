<div id="globalModal" class="fixed inset-0 z-[150] flex items-center justify-center hidden opacity-0 transition-opacity duration-300 font-['Plus_Jakarta_Sans']">
    <div id="modalBackdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-md transition-all" onclick="closeModal()"></div>

    <div id="modalContent" class="bg-white rounded-[32px] p-8 md:p-10 max-w-sm w-full mx-4 relative z-10 transform scale-95 transition-transform duration-300 shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-white/50 flex flex-col items-center">
        
        <div id="modalIconBg" class="mx-auto flex items-center justify-center h-24 w-24 rounded-full mb-6 shadow-lg">
            <i id="modalIcon" class="fas fa-check text-4xl text-white"></i>
        </div>
        
        <h3 id="modalTitle" class="text-2xl font-extrabold text-center text-gray-900 mb-2 tracking-tight"></h3>
        <p id="modalMessage" class="text-center text-gray-500 text-sm font-medium mb-8 leading-relaxed"></p>

        <div id="modalActionArea" class="w-full flex gap-3"></div>
    </div>
</div>

<script>
    let isModalActive = false;
    let modalActionCallback = null;
    let autoCloseTimer = null;

    /**
     * Fungsi pemanggil Modal
     * @param {string} type - 'success', 'error', atau 'warning'
     * @param {string} title - Judul modal
     * @param {string} message - Pesan detail
     * @param {function} callback - (Opsional) Fungsi eksekusi
     */
    function showModal(type, title, message, callback = null) {
        const modal = document.getElementById('globalModal');
        const content = document.getElementById('modalContent');
        
        const iconBg = document.getElementById('modalIconBg');
        const icon = document.getElementById('modalIcon');
        const actionArea = document.getElementById('modalActionArea');
        
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalMessage').innerText = message;
        
        modalActionCallback = callback;
        isModalActive = true;
        clearTimeout(autoCloseTimer);

        // Reset class ikon ke default
        iconBg.className = "mx-auto flex items-center justify-center h-24 w-24 rounded-full mb-6 shadow-lg transition-all duration-300";
        icon.className = "fas text-4xl text-white transition-transform transform hover:scale-110";

        // Konfigurasi berdasarkan tipe modal
        if (type === 'success') {
            // Tema Success: Gradasi Emerald ke Teal
            iconBg.classList.add('bg-gradient-to-br', 'from-emerald-400', 'to-teal-500', 'shadow-emerald-500/30');
            icon.classList.add('fa-check');
            
            actionArea.innerHTML = `
                <button id="primaryModalBtn" onclick="executeModalAction()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-emerald-500/30 transition-all transform hover:-translate-y-1 active:scale-95">
                    Luar Biasa!
                </button>
            `;
            
            // Auto close 2.5 detik
            autoCloseTimer = setTimeout(() => { executeModalAction(); }, 2500);

        } else if (type === 'error') {
            // Tema Error: Gradasi Rose ke Red
            iconBg.classList.add('bg-gradient-to-br', 'from-rose-500', 'to-red-600', 'shadow-red-500/30');
            icon.classList.add('fa-times');
            
            actionArea.innerHTML = `
                <button id="primaryModalBtn" onclick="closeModal()" class="w-full bg-rose-500 hover:bg-rose-600 text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-rose-500/30 transition-all transform hover:-translate-y-1 active:scale-95">
                    Tutup
                </button>
            `;
            
        } else if (type === 'warning') {
            // Tema Warning: Gradasi Amber ke Orange
            iconBg.classList.add('bg-gradient-to-br', 'from-amber-400', 'to-orange-500', 'shadow-orange-500/30');
            icon.classList.add('fa-exclamation-triangle');
            
            actionArea.innerHTML = `
                <button onclick="closeModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold py-4 rounded-2xl transition-colors active:scale-95">
                    Batal
                </button>
                <button id="primaryModalBtn" onclick="executeModalAction()" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-orange-500/30 transition-all transform hover:-translate-y-1 active:scale-95">
                    Lanjutkan
                </button>
            `;
        }

        // Tampilkan Modal dengan Animasi Pop-up Halus
        modal.classList.remove('hidden');
        void modal.offsetWidth; // Trigger reflow
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }

    function executeModalAction() {
        // 1. Tangkap tombol primary di dalam modal
        const primaryBtn = document.getElementById('primaryModalBtn');
        // 2. Jika tombol sudah dalam status 'disabled' (sedang diproses), tolak klik kedua!
        if (primaryBtn && primaryBtn.disabled) {
            return; 
        }
        // 3. Matikan tombol seketika saat klik pertama terjadi
        if (primaryBtn) {
            primaryBtn.disabled = true;
        }
        // 4. Eksekusi perintah (callback) hanya jika masih ada di memori
        if (typeof modalActionCallback === 'function') {
            modalActionCallback();
            // 5. Hapus memori perintah agar mustahil dieksekusi 2 kali
            modalActionCallback = null; 
        }
        // 6. Tutup modal
        closeModal();
    }

    function closeModal() {
        if (!isModalActive) return;
        isModalActive = false;
        clearTimeout(autoCloseTimer);

        const modal = document.getElementById('globalModal');
        const content = document.getElementById('modalContent');

        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('modalActionArea').innerHTML = ''; 
        }, 300);
    }

    document.addEventListener('keydown', function(event) {
        if (isModalActive && event.key === 'Enter') {
            event.preventDefault(); 
            const primaryBtn = document.getElementById('primaryModalBtn');
            if (primaryBtn) primaryBtn.click(); 
        }
    });
</script>