<?php
session_start();
require_once 'api/config.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Ambil daftar dompet untuk dropdown
$wallets = $conn->query("SELECT id, name FROM wallets WHERE user_id = $user_id");
$wallet_list = [];
while ($row = $wallets->fetch_assoc()) {
    $wallet_list[] = $row;
}

// Ambil data hutang & piutang
$debts = $conn->query("SELECT * FROM debts WHERE user_id = $user_id ORDER BY status ASC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hutang & Paylater - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F4F7FE;
        }

        .modal-active {
            overflow: hidden;
        }
    </style>
</head>

<body class="antialiased text-gray-800 pb-20 md:pb-0">

    <?php include 'navbar.php'; ?>
    <?php include 'components/modal.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8 mt-16 md:mt-0">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">Hutang & Paylater</h1>
                <p class="text-gray-500 text-sm">Monitor cicilan dan jatuh tempo tagihan</p>
            </div>
            <button onclick="document.getElementById('modalFormTambah').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg shadow-blue-500/30 transition-all active:scale-95 flex items-center gap-2">
                <i class="fas fa-plus"></i> <span>Tambah</span>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-5">
            <?php if ($debts->num_rows == 0): ?>
                <div class="bg-white p-10 rounded-[32px] shadow-sm text-center border border-gray-100">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-invoice-dollar text-gray-300 text-3xl"></i>
                    </div>
                    <p class="text-gray-500 font-bold">Belum ada catatan hutang.</p>
                </div>
            <?php else: ?>
                <?php while ($row = $debts->fetch_assoc()):
                    $is_payable = $row['type'] === 'payable';
                    $color = $is_payable ? 'red' : 'emerald';
                    $label = $is_payable ? 'Tagihan Hutang / Paylater ke:' : 'Piutang / Pinjaman ke:';
                    $progress = (($row['total_amount'] - $row['remaining_amount']) / $row['total_amount']) * 100;

                    // Hitung Jatuh Tempo
                    $today = new DateTime();
                    $today->setTime(0, 0, 0);
                    $due = new DateTime($row['due_date']);
                    $diff = $today->diff($due);
                    $days_left = (int)$diff->format('%R%a');
                ?>
                    <div class="bg-white p-6 rounded-[32px] shadow-sm border border-gray-100 relative overflow-hidden">
                        <?php if ($row['status'] === 'paid'): ?>
                            <div class="absolute top-4 right-[-35px] bg-emerald-500 text-white text-[10px] font-black py-1 px-10 transform rotate-45 shadow-sm">LUNAS</div>
                        <?php endif; ?>

                        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-<?= $color ?>-50 text-<?= $color ?>-600 flex items-center justify-center text-2xl shadow-inner">
                                    <i class="fas fa-<?= $is_payable ? 'credit-card' : 'hand-holding-usd' ?>"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= $label ?></p>
                                    <h3 class="text-lg font-black text-gray-900"><?= htmlspecialchars($row['name']) ?></h3>
                                    <p class="text-xs font-bold <?= $days_left < 0 && $row['status'] !== 'paid' ? 'text-red-500' : 'text-gray-500' ?>">
                                        <i class="far fa-calendar-alt mr-1"></i> Jatuh Tempo: <?= date('d M Y', strtotime($row['due_date'])) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-gray-400 mb-1">Sisa Pembayaran</p>
                                <h3 class="text-xl font-black text-<?= $color ?>-600">Rp <?= number_format($row['remaining_amount'], 0, ',', '.') ?></h3>
                                <p class="text-[10px] text-gray-500 font-bold">Total: Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></p>
                            </div>
                        </div>

                        <?php if ($row['status'] === 'active'): ?>
                            <?php if ($days_left < 0): ?>
                                <div class="mt-4 bg-red-50 text-red-600 text-[11px] font-bold p-3 rounded-xl border border-red-100 flex items-center gap-2">
                                    <i class="fas fa-exclamation-circle text-sm"></i> TELAT <?= abs($days_left) ?> HARI! Segera lakukan pembayaran.
                                </div>
                            <?php elseif ($days_left <= 3): ?>
                                <div class="mt-4 bg-orange-50 text-orange-600 text-[11px] font-bold p-3 rounded-xl border border-orange-100 flex items-center gap-2">
                                    <i class="fas fa-clock text-sm"></i> Hampir Jatuh Tempo (<?= $days_left ?> hari lagi).
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="mt-6">
                            <div class="flex justify-between text-[10px] font-black text-gray-400 mb-2 uppercase tracking-widest">
                                <span>Progress Pelunasan</span>
                                <span><?= round($progress) ?>%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-<?= $color ?>-500 h-full rounded-full transition-all duration-700" style="width: <?= $progress ?>%"></div>
                            </div>

                            <?php if ($row['status'] === 'active'): ?>
                                <div class="flex justify-end mt-4">
                                    <button onclick="bukaModalBayar(<?= $row['id'] ?>, <?= $row['remaining_amount'] ?>, '<?= htmlspecialchars($row['name']) ?>')" class="mx-2 bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold py-2 px-4 rounded-xl text-xs transition-all flex items-center gap-2">
                                        <i class="fas fa-money-bill-wave"></i> Bayar Sebagian
                                    </button>
                                    <button onclick="konfirmasiHapusHutang(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')" class="mx-4 text-red-500 hover:text-red-700 ml-4">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalFormTambah" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center p-4">
        <div class="bg-white rounded-[32px] w-full max-w-md p-8 shadow-2xl relative">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-gray-900">Catat Hutang/Paylater</h2>
                <button onclick="document.getElementById('modalFormTambah').classList.add('hidden')" class="w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-times"></i></button>
            </div>
            <form id="formTambahHutang" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Jenis</label>
                    <select name="type" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                        <option value="payable">Saya Meminjam (Masuk Dompet)</option>
                        <option value="receivable">Memberi Pinjaman (Keluar Dompet)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Nama Pihak / Layanan</label>
                    <input type="text" name="name" required placeholder="Contoh: Shopee Paylater, Budi..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Dompet Tujuan / Sumber Saldo</label>
                    <select name="wallet_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                        <?php foreach ($wallet_list as $w): ?> <option value="<?= $w['id'] ?>"><?= $w['name'] ?></option> <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Nominal Pokok</label>
                        <input type="number" name="principal" id="inputPokok" required placeholder="Cair ke dompet" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 font-black text-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Bunga / Admin</label>
                        <input type="number" name="interest" id="inputBunga" placeholder="0 jika tidak ada" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 font-black text-sm outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Total Tanggungan</label>
                    <div class="w-full bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 font-black text-lg text-blue-700" id="tampilTotal">
                        Rp 0
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1">*Otomatis dihitung (Pokok + Bunga)</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Tanggal Jatuh Tempo</label>
                    <input type="date" name="due_date" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-blue-500">
                </div>
                <button type="submit" id="btnSubmitTambah" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3.5 rounded-xl mt-4 transition-all active:scale-95">Simpan Data</button>
            </form>
        </div>
    </div>

    <div id="modalFormBayar" class="fixed inset-0 z-[100] hidden bg-gray-900/60 backdrop-blur-sm flex justify-center items-center p-4">
        <div class="bg-white rounded-[32px] w-full max-w-md p-8 shadow-2xl relative">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-gray-900">Bayar Cicilan / Lunas</h2>
                <button onclick="document.getElementById('modalFormBayar').classList.add('hidden')" class="w-10 h-10 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-times"></i></button>
            </div>
            <form id="formBayarHutang" class="space-y-4">
                <input type="hidden" name="debt_id" id="pay_debt_id">

                <div class="bg-blue-50 p-5 rounded-2xl mb-4 text-center border border-blue-100">
                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest" id="pay_target_name">Pihak: -</p>
                    <p class="text-sm font-bold text-gray-700 mt-2">Sisa Tanggungan:</p>
                    <p class="text-3xl font-black text-gray-900 my-1" id="pay_remaining_text">Rp 0</p>
                    <p class="text-xs text-gray-500 font-medium">Anda bisa mencicil sebagian atau melunasi.</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Nominal Pembayaran</label>
                    <input type="number" name="amount" id="pay_amount_input" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-4 font-black text-xl outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Sumber Dompet</label>
                    <select name="wallet_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-bold outline-none focus:border-emerald-500">
                        <?php foreach ($wallet_list as $w): ?> <option value="<?= $w['id'] ?>"><?= $w['name'] ?></option> <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" id="btnSubmitBayar" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold py-4 rounded-xl mt-6 transition-all active:scale-95">Proses Pembayaran</button>
            </form>
        </div>
    </div>
    <?php include 'navbar_hp.php'; ?>
    
    <script>
        // 1. Hitung Total Otomatis di Form Tambah
        const inputPokok = document.getElementById('inputPokok');
        const inputBunga = document.getElementById('inputBunga');
        const tampilTotal = document.getElementById('tampilTotal');

        function hitungTotal() {
            const pokok = parseFloat(inputPokok.value) || 0;
            const bunga = parseFloat(inputBunga.value) || 0;
            const total = pokok + bunga;
            tampilTotal.textContent = "Rp " + new Intl.NumberFormat('id-ID').format(total);
        }

        inputPokok.addEventListener('input', hitungTotal);
        inputBunga.addEventListener('input', hitungTotal);

        // 2. Buka Modal Bayar (Set data dari tombol)
        function bukaModalBayar(id, remaining, name) {
            document.getElementById('pay_debt_id').value = id;
            document.getElementById('pay_target_name').textContent = "Pihak: " + name;
            document.getElementById('pay_remaining_text').textContent = "Rp " + new Intl.NumberFormat('id-ID').format(remaining);

            // Set max atribut agar tidak bayar lebih dari sisa
            document.getElementById('pay_amount_input').max = remaining;
            document.getElementById('pay_amount_input').value = '';

            document.getElementById('modalFormBayar').classList.remove('hidden');
        }

        // 3. AJAX TAMBAH HUTANG (Dengan Komponen Modal Anda)
        document.getElementById('formTambahHutang').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitTambah');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';

            fetch('api/debt.php?action=create', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('modalFormTambah').classList.add('hidden');
                    if (data.status === 'success') {
                        showModal('success', 'Berhasil!', data.message, () => {
                            window.location.reload();
                        });
                    } else {
                        showModal('error', 'Gagal', data.message, () => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        });
                    }
                }).catch(() => {
                    showModal('error', 'Kesalahan', 'Jaringan bermasalah.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });

        // 4. AJAX BAYAR CICILAN (Dengan Komponen Modal Anda)
        document.getElementById('formBayarHutang').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitBayar');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';

            fetch('api/debt.php?action=pay', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('modalFormBayar').classList.add('hidden');
                    if (data.status === 'success') {
                        showModal('success', 'Pembayaran Sukses', data.message, () => {
                            window.location.reload();
                        });
                    } else {
                        showModal('warning', 'Peringatan', data.message, () => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        });
                    }
                }).catch(() => {
                    showModal('error', 'Kesalahan', 'Jaringan bermasalah.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
        
        function konfirmasiHapusHutang(id, name) {
            showConfirmModal(
                'warning',
                'Hapus Pinjaman?',
                `Apakah Anda yakin ingin menghapus <b>${name}</b>?<br>Semua riwayat pembayarannya akan ikut terhapus permanen.`,
                'Ya, Hapus!',
                'Batal',
                () => {
                    const formData = new FormData();
                    formData.append('debt_id', id);

                    fetch('api/debt.php?action=delete', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showModal('success', 'Terhapus!', data.message, () => {
                                window.location.reload(); 
                            });
                        } else {
                            showModal('error', 'Gagal Menghapus', data.message);
                        }
                    })
                    .catch(err => {
                        showModal('error', 'Kesalahan', 'Jaringan bermasalah.');
                    });
                }
            );
        }
        
        
    </script>
</body>

</html>