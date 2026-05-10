<!-- File: components/modal_kategori.php -->
<div id="globalCategoryModal" class="fixed inset-0 z-[130] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <!-- Backdrop (Lebih gelap agar membedakan tumpukan) -->
    <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="closeCategoryModal()"></div>
    
    <div class="bg-white rounded-[24px] p-8 max-w-md w-full mx-4 relative z-10 transform scale-95 transition-transform duration-300" id="categoryModalContent">
        <h3 class="text-xl font-extrabold text-gray-900 mb-6" id="categoryModalTitle">Buat Kategori Baru</h3>
        
        <form id="globalCategoryForm" onsubmit="submitCategoryForm(event)">
            <!-- Hidden inputs -->
            <input type="hidden" name="id" id="editCategoryId" value="">
            <input type="hidden" name="type" id="newCategoryType" value="expense">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-600 mb-2">Nama Kategori</label>
                <input type="text" name="name" required placeholder="Misal: Uang Jajan, SPP..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 font-bold">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-600 mb-2">Pilih Ikon</label>
                <div class="grid grid-cols-5 gap-3 h-32 overflow-y-auto p-2 bg-gray-50 rounded-xl border border-gray-200">
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="wallet" class="peer sr-only" checked><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-wallet"></i></div></label>
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="shopping-cart" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-shopping-cart"></i></div></label>
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="utensils" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-utensils"></i></div></label>
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="car" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-car"></i></div></label>
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="graduation-cap" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-graduation-cap"></i></div></label>
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="bolt" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-bolt"></i></div></label>
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="heartbeat" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-heartbeat"></i></div></label>
                    <label class="cursor-pointer"><input type="radio" name="icon_name" value="gamepad" class="peer sr-only"><div class="flex items-center justify-center h-10 rounded-lg bg-white border peer-checked:border-blue-500 peer-checked:text-blue-500 text-gray-400 shadow-sm transition-all"><i class="fas fa-gamepad"></i></div></label>
                </div>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-600 mb-2">Warna Khas</label>
                <input type="color" name="color" value="#3B82F6" class="w-full h-12 rounded-xl cursor-pointer border-0 p-0">
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeCategoryModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition-colors">Batal</button>
                <button type="submit" id="catSubmitBtn" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-blue-500/30">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<script>
    // FUNGSI BUKA MODAL (Mendukung mode 'add' dan 'edit')
    function openCategoryModal(mode = 'add', type = 'expense', id = '', name = '', icon = 'wallet', color = '#3B82F6') {
        const modal = document.getElementById('globalCategoryModal');
        const content = document.getElementById('categoryModalContent');
        const form = document.getElementById('globalCategoryForm');
        const title = document.getElementById('categoryModalTitle');
        const submitBtn = document.getElementById('catSubmitBtn');
        
        // Reset form setiap kali dibuka
        form.reset();
        document.getElementById('newCategoryType').value = type;
        document.getElementById('editCategoryId').value = id;

        if (mode === 'edit') {
            title.innerText = 'Edit Kategori';
            submitBtn.innerText = 'Simpan Perubahan';
            
            // Isi form dengan data lama
            form.querySelector('input[name="name"]').value = name;
            form.querySelector('input[name="color"]').value = color;
            
            // Ceklist radio button ikon yang sesuai
            const iconRadio = form.querySelector(`input[name="icon_name"][value="${icon}"]`);
            if(iconRadio) iconRadio.checked = true;

        } else {
            // Mode Tambah Baru
            title.innerText = type === 'expense' ? 'Buat Kategori Pengeluaran' : 'Buat Kategori Pemasukan';
            submitBtn.innerText = 'Simpan Kategori';
        }

        modal.classList.remove('hidden'); void modal.offsetWidth;
        modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100');
    }

    function closeCategoryModal() {
        const modal = document.getElementById('globalCategoryModal');
        const content = document.getElementById('categoryModalContent');
        modal.classList.add('opacity-0'); content.classList.remove('scale-100'); content.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function submitCategoryForm(e) {
        e.preventDefault();
        const btn = document.getElementById('catSubmitBtn');
        const originalText = btn.innerHTML;
        const formData = new FormData(document.getElementById('globalCategoryForm'));
        const payload = Object.fromEntries(formData.entries());
        
        // PERBAIKAN: Jika ID terisi, gunakan PUT (Update). Jika kosong, gunakan POST (Insert).
        const methodType = payload.id ? 'PUT' : 'POST';
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; 
        btn.disabled = true;

        fetch('api/category.php', { 
            method: methodType, 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload) 
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeCategoryModal();
                
                // Refresh daftar kategori di modal transaksi tanpa me-reload halaman
                if (typeof fetchTxData === 'function') {
                    fetchTxData(); 
                }
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(err => {
            alert('Terjadi kesalahan jaringan.');
        })
        .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    }
</script>