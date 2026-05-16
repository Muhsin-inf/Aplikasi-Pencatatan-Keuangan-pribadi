<?php
session_start();
require_once 'api/config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Ambil data dompet
$wallets = $conn->query("SELECT id, name FROM wallets WHERE user_id = $user_id");
$wallet_list = [];
while ($row = $wallets->fetch_assoc()) $wallet_list[] = $row;

// Ambil data kategori (untuk difilter via JS)
$categories = $conn->query("SELECT id, name, type FROM categories WHERE user_id = $user_id");
$category_list = [];
while ($row = $categories->fetch_assoc()) $category_list[] = $row;

// Ambil data jadwal berulang
$query = "SELECT r.*, w.name as wallet_name, c.name as category_name, c.icon_name, c.color 
          FROM recurring_transactions r
          JOIN wallets w ON r.wallet_id = w.id
          JOIN categories c ON r.category_id = c.id
          WHERE r.user_id = $user_id ORDER BY r.status ASC, r.next_run_date ASC";
$recurring = $conn->query($query);

// Helper Terjemahan Satuan Waktu
function translateUnit($unit) {
    $units = ['day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan', 'year' => 'Tahun'];
    return $units[$unit] ?? $unit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Berulang - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; } </style>
</head>
<body class="antialiased text-gray-800 pb-20 md:pb-0">

    <?php include 'navbar.php'; ?>
    <?php include 'components/modal.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8 mt-16 md:mt-0">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">Transaksi Berulang</h1>
                <p class="text-gray-500 text-sm">Otomatisasi pengeluaran & pemasukan rutin</p>
            </div>
            <button onclick="bukaModalTambah()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-robot"></i> <span class="hidden md:inline">Buat Jadwal</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php if($recurring->num_rows == 0): ?>
                <div class="col-span-1 md:col-span-2 bg-white p-10 rounded-[32px] shadow-sm text-center border border-gray-100">
                    <div class="w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                        <i class="fas fa-sync fa-spin-hover"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Belum Ada Otomatisasi</h3>
                    <p class="text-gray-500 text-sm">Buat jadwal untuk tagihan WiFi, Gaji, atau langganan lainnya.</p>
                </div>
            <?php else: ?>
                <?php while($row = $recurring->fetch_assoc()): 
                    $is_income = $row['type'] === 'income';
                    $color = $is_income ? 'emerald' : 'red';
                    $is_paused = $row['status'] === 'paused';
                    $card_opacity = $is_paused ? 'opacity-60 grayscale-[50%]' : '';
                ?>
                <div class="bg-white p-6 rounded-[24px] shadow-sm border border-gray-100 relative overflow-hidden transition-all hover:shadow-md <?= $card_opacity ?>">
                    
                    <div class="absolute top-4 right-4 flex gap-2">
                        <?php if($is_paused): ?>
                            <span class="bg-gray-100 text-gray-500 text-[10px] font-black px-3 py-1 rounded-full"><i class="fas fa-pause mr-1"></i> DIJEDA</span>
                        <?php else: ?>
                            <span class="bg-blue-50 text-blue-600 text-[10px] font-black px-3 py-1 rounded-full"><i class="fas fa-play mr-1"></i> AKTIF</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-4 mb-5 mt-2">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl text-white shadow-md" style="background-color: <?= $row['color'] ?>">
                            <ion-icon name="<?= $row['icon_name'] ?>"></ion-icon>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900"><?= htmlspecialchars($row['category_name']) ?></h3>
                            <p class="text-xs font-bold text-gray-400"><?= htmlspecialchars($row['note'] ?: 'Tanpa Catatan') ?></p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl mb-5 flex justify-between items-center border border-gray-100">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nominal <?= $is_income ? 'Masuk' : 'Keluar' ?></p>
                            <h4 class="text-lg font-black text-<?= $color ?>-600">Rp <?= number_format($row['amount'], 0, ',', '.') ?></h4>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Siklus</p>
                            <p class="text-sm font-bold text-gray-700">Tiap <?= $row['repeat_interval'] ?> <?= translateUnit($row['repeat_unit']) ?></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-bold <?= $is_paused ? 'text-gray-400' : 'text-blue-600' ?>">
                            <i class="far fa-calendar-check text-base"></i>
                            <div>
                                <p class="text-[9px] uppercase tracking-widest text-gray-400">Eksekusi Berikutnya</p>
                                <?= date('d M Y', strtotime($row['next_run_date'])) ?>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button onclick="toggleStatus(<?= $row['id'] ?>, '<?= $is_paused ? 'active' : 'paused' ?>')" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-blue-600 transition-colors" title="<?= $is_paused ? 'Lanjutkan Jadwal' : 'Jeda Jadwal' ?>">
                                <i class="fas fa-<?= $is_paused ? 'play' : 'pause' ?>"></i>
                            </button>
                            <button onclick="konfirmasiHapusJadwal(<?= $row['id'] ?>)" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors" title="Hapus Jadwal">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalFormTambah" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center p-4">
        <div class="bg-white rounded-[32px] w-full max-w-md p-8 shadow-2xl relative">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-gray-900">Buat Jadwal Baru</h2>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">Robot akan mengeksekusi otomatis</p>
                </div>
                <button onclick="document.getElementById('modalFormTambah').classList.add('hidden')" class="w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-times"></i></button>
            </div>
            
            <form id="formTambahJadwal" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Jenis</label>
                        <select name="type" id="inputType" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Dompet</label>
                        <select name="wallet_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                            <?php foreach($wallet_list as $w): ?> <option value="<?= $w['id'] ?>"><?= $w['name'] ?></option> <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Kategori</label>
                    <select name="category_id" id="inputCategory" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                        </select>
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Nominal Transaksi</label>
                    <input type="number" name="amount" required placeholder="Rp 0" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-black text-lg outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Catatan</label>
                    <input type="text" name="note" placeholder="Cth: Langganan WiFi, Gaji Bulanan..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Ulangi Tiap (Angka)</label>
                        <input type="number" name="repeat_interval" required min="1" value="1" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-black text-lg outline-none focus:border-blue-500 text-center">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Satuan Waktu</label>
                        <select name="repeat_unit" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                            <option value="month">Bulan</option>
                            <option value="day">Hari</option>
                            <option value="week">Minggu</option>
                            <option value="year">Tahun</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Mulai Eksekusi Pertama Pada</label>
                    <input type="date" name="next_run_date" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                </div>

                <button type="submit" id="btnSubmitTambah" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3.5 rounded-xl mt-4 transition-all active:scale-95">Simpan Jadwal</button>
            </form>
        </div>
    </div>
    <?php include 'navbar_hp.php'; ?>
    
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <script>
        // --- LOGIKA FILTER KATEGORI DINAMIS ---
        const allCategories = <?= json_encode($category_list) ?>;
        const inputType = document.getElementById('inputType');
        const inputCategory = document.getElementById('inputCategory');

        function updateCategoryOptions() {
            const selectedType = inputType.value;
            inputCategory.innerHTML = ''; // Kosongkan dulu
            
            const filtered = allCategories.filter(c => c.type === selectedType);
            if (filtered.length === 0) {
                inputCategory.innerHTML = '<option value="" disabled selected>Tidak ada kategori untuk jenis ini</option>';
                return;
            }

            filtered.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                inputCategory.appendChild(opt);
            });
        }

        // Jalankan saat pertama load & saat tipe diubah
        inputType.addEventListener('change', updateCategoryOptions);
        document.addEventListener('DOMContentLoaded', updateCategoryOptions);


        // --- FUNGSI ANTARMUKA ---
        function bukaModalTambah() {
            document.getElementById('modalFormTambah').classList.remove('hidden');
        }

        // --- FUNGSI AJAX REST API ---
        
        // 1. Tambah Data (POST)
        document.getElementById('formTambahJadwal').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitTambah');
            const originalText = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';

            // Convert FormData ke format JSON karena REST API butuh JSON
            const formData = new FormData(this);
            const payload = Object.fromEntries(formData.entries());

            fetch('api/recurring.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('modalFormTambah').classList.add('hidden');
                if (data.status === 'success') {
                    showModal('success', 'Tersimpan!', data.message, () => window.location.reload());
                } else {
                    showModal('error', 'Gagal Menyimpan', data.message, () => { btn.disabled = false; btn.innerHTML = originalText; });
                }
            })
            .catch(() => showModal('error', 'Kesalahan', 'Jaringan bermasalah.'));
        });

        // 2. Ubah Status Jeda / Aktif (PUT)
        function toggleStatus(id, newStatus) {
            fetch('api/recurring.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Refresh instan tanpa notifikasi bertele-tele
                    window.location.reload(); 
                } else {
                    showModal('error', 'Gagal', data.message);
                }
            });
        }

        // 3. Hapus Jadwal (DELETE)
        function konfirmasiHapusJadwal(id) {
            showConfirmModal(
                'warning',
                'Hapus Jadwal?',
                'Robot tidak akan lagi mengeksekusi tagihan ini. Riwayat transaksi yang sudah terjadi sebelumnya akan tetap aman di dompet Anda.',
                'Ya, Hapus Jadwal',
                'Batal',
                () => {
                    fetch('api/recurring.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showModal('success', 'Terhapus', data.message, () => window.location.reload());
                        } else {
                            showModal('error', 'Gagal', data.message);
                        }
                    });
                }
            );
        }
    </script>
</body>
</html>